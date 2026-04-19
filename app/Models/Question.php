<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'stable_key',
        'text',
        'type',
        'points',
        'image',
        'options',
        'order'
    ];

    protected $casts = [
        'options' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Question $question) {
            if (!$question->stable_key) {
                $question->stable_key = (string) Str::uuid();
            }
        });
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }
}
