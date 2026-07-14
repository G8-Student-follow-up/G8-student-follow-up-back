<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    protected $appends = ['user_name', 'description', 'type'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function getUserNameAttribute(): string
    {
        return $this->user?->name ?? 'Unknown';
    }

    public function getDescriptionAttribute(): string
    {
        if ($this->changes && isset($this->changes['description'])) {
            return $this->changes['description'];
        }
        $subject = $this->subject_type === 'card' ? 'card' : $this->subject_type;
        return ucfirst($this->action) . ' ' . $subject;
    }

    public function getTypeAttribute(): string
    {
        return match ($this->action) {
            'create' => 'create',
            'update' => 'edit',
            'complete' => 'complete',
            default => 'edit',
        };
    }
}
