<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tree extends Model
{
    use HasFactory;
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
