<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    use HasFactory;

    protected $fillable = [
        'tree_id',
        'accessibility',
        'data',
    ];
    public function tree(){
        return $this->belongsTo(Tree::class);
    }
}
