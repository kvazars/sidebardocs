<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'user_name',
        'total_score',
        'max_score',
        'percentage',
        'grade',
        'time_spent',
        'settings',
        'question_results'
    ];

    protected $casts = [
        'settings' => 'array',
        'question_results' => 'array'
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }
}
