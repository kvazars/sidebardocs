<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tree extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        "name",
        "tree_id",
        "user_id",
        "type",
    ];
    public function child() {
        return $this->hasMany(Content::class);
    }
}
