<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'text',
        'type',
        'points',
        'image',
        'options',
        'correct_answers',
        'pairs',
        'correct_answer',
        'order'
    ];

    protected $casts = [
        'options' => 'array',
        'correct_answers' => 'array',
        'pairs' => 'array'
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }
}
