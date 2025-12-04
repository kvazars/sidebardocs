<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Test extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'timeLimit',
        'settings',
        'tree_id',
        'grading',
        'correctOrder',
    ];


    protected $casts = [
        'settings' => 'array',
        'grading' => 'array'
    ];
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(TestResult::class);
    }
}
