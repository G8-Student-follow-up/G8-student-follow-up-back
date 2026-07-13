<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BoardMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'board_id',
        'user_id',
        'role',
    ];

    protected $appends = ['name', 'email', 'avatar'];

    public function getNameAttribute(): ?string
    {
        return $this->user->name ?? null;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->user->email ?? null;
    }

    public function getAvatarAttribute(): ?string
    {
        return $this->user->avatar ?? null;
    }

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
