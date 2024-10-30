<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Content extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tree_id',
        'accessibility',
        'data',
    ];
    public function tree(){
        return $this->belongsTo(Tree::class)->with('user');
    }
}
