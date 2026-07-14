<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkspaceMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'user_id',
    ];

    protected $appends = ['name', 'email', 'avatar', 'role'];

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

    public function getRoleAttribute(): ?string
    {
        return $this->user->role ?? null;
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
