<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGroups extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'group_id'];
    public function user()
    {
        return User::withTrashed()->find($this->user_id);
    } 
}
