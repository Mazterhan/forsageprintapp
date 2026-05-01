<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPasswordResetLog extends Model
{
    protected $fillable = [
        'user_id',
        'reset_by_user_id',
        'reason',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resetBy()
    {
        return $this->belongsTo(User::class, 'reset_by_user_id');
    }
}
