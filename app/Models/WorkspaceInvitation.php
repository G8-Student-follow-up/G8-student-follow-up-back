<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class WorkspaceInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'user_id',
        'invited_by',
        'status',
        'token',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $invitation) {
            if (empty($invitation->token)) {
                $invitation->token = Str::random(64);
            }
        });
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function accept(): void
    {
        $this->update(['status' => 'accepted']);
        WorkspaceMember::firstOrCreate([
            'workspace_id' => $this->workspace_id,
            'user_id' => $this->user_id,
        ]);
    }

    public function decline(): void
    {
        $this->update(['status' => 'declined']);
    }
}
