<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkspaceInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'invited_by',
        'email',
        'user_id',
        'name',
        'token',
        'role',
        'trainerstatus',
        'expires_at',
        'accepted_at',
    ];

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
        return $this->trainerstatus === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->trainerstatus === 'accepted';
    }

    public function accept(): void
    {
        $this->update(['trainerstatus' => 'accepted', 'accepted_at' => now()]);
        WorkspaceMember::firstOrCreate([
            'workspace_id' => $this->workspace_id,
            'user_id' => $this->user_id,
        ]);
    }

    public function decline(): void
    {
        $this->update(['trainerstatus' => 'declined']);
    }
}
