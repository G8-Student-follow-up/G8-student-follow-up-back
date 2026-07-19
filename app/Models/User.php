<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'phone',
        'telegram',
        'provider',
        'provider_id',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'avatar_url'
    ];
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getAvatarUrlAttribute()
    {
        if (!$this->avatar) {
            return null;
        }

        // If it's already a full URL (e.g., from social login), return as-is
        if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
            return $this->avatar;
        }

        return asset('storage/' . $this->avatar);
    }
    public function workspaces()
    {
        return $this->hasMany(Workspace::class, 'owner_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'trainer_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function cards()
    {
        return $this->hasMany(Card::class, 'created_by');
    }

    public function boardMemberships()
    {
        return $this->hasMany(BoardMember::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function pendingWorkspaceInvitations()
    {
        return $this->hasMany(WorkspaceInvitation::class, 'user_id')
            ->where('status', 'pending')
            ->with('workspace', 'invitedBy');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTrainer(): bool
    {
        return $this->role === 'trainer';
    }
}
