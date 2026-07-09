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
        'avatar'
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

    public function getAvatarUrlAttribute(){
        if (!$this -> avatar){
            return null;
        }

        return asset('storage/' . $this -> avatar);
    }
    public function workspaces()
    {
        return $this->hasMany(Workspace::class, 'created_by');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'trainer_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
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
