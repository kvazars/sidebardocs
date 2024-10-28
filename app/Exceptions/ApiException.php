<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    public function __construct($code = 422, $message = 'Validation error', $errors = [])
    {
        $data = [
            'error' => [
                'message' => $message,
            ]
        ];
        if (count($errors) > 0) {
            $data['error']['errors'] = $errors;
        }

        $response = response()->json($data, $code)->setStatusCode($code);
        return parent::__construct(json_encode($response->original), $code);
    }
}
