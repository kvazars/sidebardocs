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
        "position",
    ];
    public function child()
    {
        return $this->hasOne(Content::class, );
    }
    public function parent()
    {
        return $this->belongsTo(Tree::class, "tree_id","id");
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function available()
    {
        return $this->hasMany(Available::class);
    }
}
