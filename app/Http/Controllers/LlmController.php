<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

class LlmController extends Controller
{
    public function streamEditorContent(Request $request)
    {
        $timeout = max(60, (int) config('services.llm.timeout', 60));
        @set_time_limit($timeout + 5);

        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:10000'],
        ]);

        $endpoint = config('services.llm.chat_stream_url');

        if (!$endpoint) {
            return response()->json([
                'success' => false,
                'message' => 'LLM endpoint is not configured',
            ], 500);
        }

        try {
            $upstream = Http::withHeaders([
                'Accept' => 'text/event-stream, application/json, text/plain',
            ])
                ->connectTimeout((int) config('services.llm.connect_timeout', 10))
                ->timeout($timeout)
                ->withOptions(['stream' => true])
                ->send('POST', $endpoint, [
                    'json' => [
                        'prompt' => $this->buildEditorPrompt($validated['prompt']),
                    ],
                ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось связаться с LLM-сервисом',
            ], 502);
        }

        if (!$upstream->successful()) {
            return response()->json([
                'success' => false,
                'message' => $this->extractErrorMessage($upstream->body()),
            ], 502);
        }

        $body = $upstream->toPsrResponse()->getBody();

        return response()->stream(function () use ($body) {
            $buffer = '';
            $mode = 'unknown';

            try {
                while (!$body->eof()) {
                    $chunk = $body->read(4096);

                    if ($chunk === '') {
                        usleep(50000);
                        continue;
                    }

                    $buffer .= $chunk;
                    $text = $this->drainStreamBuffer($buffer, $mode, false);

                    if ($text === '') {
                        continue;
                    }

                    echo $text;
                    $this->flushStream();
                }

                $tail = $this->drainStreamBuffer($buffer, $mode, true);
                if ($tail !== '') {
                    echo $tail;
                    $this->flushStream();
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function buildEditorPrompt(string $prompt): string
    {
        return trim(
            "Ты создаешь содержимое документа для редактора Editor.js.\n" .
                "Отвечай приоритетно на русском языке. Английский допускается частично, когда это уместно для терминов, названий, ссылок или отдельных формулировок.\n" .
                "Верни только HTML-фрагмент без markdown-оберток, без комментариев и без пояснений до или после HTML.\n" .
                "Используй только безопасные теги содержимого: p, h2, h3, h4, ul, ol, li, a, strong, em, b, i, u, code, pre, blockquote, table, thead, tbody, tr, th, td, br.\n" .
                "Если уместно, используй списки, заголовки, ссылки, таблицы и цитаты.\n" .
                "Не добавляй html, body, script, style, iframe.\n\n" .
                "Запрос пользователя:\n" .
                trim($prompt)
        );
    }

    private function extractErrorMessage(string $body): string
    {
        $decoded = json_decode($body, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $message = $this->extractTextFromDecoded($decoded);

            if ($message !== '') {
                return $message;
            }

            if (is_array($decoded) && isset($decoded['message']) && is_string($decoded['message'])) {
                return $decoded['message'];
            }
        }

        return 'LLM-сервис вернул ошибку';
    }

    private function drainStreamBuffer(string &$buffer, string &$mode, bool $final): string
    {
        if ($mode === 'unknown') {
            $mode = $this->detectStreamMode($buffer, $final);
        }

        if ($mode === 'unknown') {
            return '';
        }

        if ($mode === 'raw') {
            $output = $buffer;
            $buffer = '';

            return $output;
        }

        $output = '';

        while (($position = strpos($buffer, "\n")) !== false) {
            $line = substr($buffer, 0, $position);
            $buffer = substr($buffer, $position + 1);
            $output .= $this->extractTextFromLine(rtrim($line, "\r"));
        }

        if ($final && $buffer !== '') {
            $output .= $this->extractTextFromLine(rtrim($buffer, "\r"));
            $buffer = '';
        }

        return $output;
    }

    private function detectStreamMode(string $buffer, bool $final): string
    {
        $trimmed = ltrim($buffer);

        if ($trimmed === '') {
            return $final ? 'raw' : 'unknown';
        }

        if (str_starts_with($trimmed, 'data:')) {
            return 'sse';
        }

        $firstChar = $trimmed[0];
        if ($firstChar === '{' || $firstChar === '[') {
            return (str_contains($buffer, "\n") || $final)
                ? 'jsonl'
                : 'unknown';
        }

        if (str_contains($trimmed, '<') || strlen($buffer) >= 64 || $final) {
            return 'raw';
        }

        return 'unknown';
    }

    private function extractTextFromLine(string $line): string
    {
        if ($line === '' || str_starts_with($line, ':')) {
            return '';
        }

        if (
            str_starts_with($line, 'event:') ||
            str_starts_with($line, 'id:') ||
            str_starts_with($line, 'retry:')
        ) {
            return '';
        }

        $payload = str_starts_with($line, 'data:')
            ? ltrim(substr($line, 5))
            : $line;

        if ($payload === '' || $payload === '[DONE]') {
            return '';
        }

        $decoded = json_decode($payload, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->extractTextFromDecoded($decoded);
        }

        return $payload;
    }

    private function extractTextFromDecoded(mixed $payload): string
    {
        if (is_string($payload)) {
            return $payload;
        }

        if (!is_array($payload)) {
            return '';
        }

        foreach (['html', 'content', 'text', 'response', 'answer', 'token'] as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }

            $text = $this->extractTextFromDecoded($payload[$key]);
            if ($text !== '') {
                return $text;
            }
        }

        foreach (['delta', 'message', 'data'] as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }

            $text = $this->extractTextFromDecoded($payload[$key]);
            if ($text !== '') {
                return $text;
            }
        }

        if (isset($payload['choices']) && is_array($payload['choices'])) {
            $parts = [];
            foreach ($payload['choices'] as $choice) {
                $text = $this->extractTextFromDecoded($choice);
                if ($text !== '') {
                    $parts[] = $text;
                }
            }

            if ($parts !== []) {
                return implode('', $parts);
            }
        }

        if (array_is_list($payload)) {
            $parts = [];
            foreach ($payload as $item) {
                $text = $this->extractTextFromDecoded($item);
                if ($text !== '') {
                    $parts[] = $text;
                }
            }

            return implode('', $parts);
        }

        return '';
    }

    private function flushStream(): void
    {
        if (function_exists('ob_flush')) {
            @ob_flush();
        }

        flush();
    }
}
