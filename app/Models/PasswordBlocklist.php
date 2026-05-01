<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordBlocklist extends Model
{
    protected $fillable = [
        'password',
    ];
}
