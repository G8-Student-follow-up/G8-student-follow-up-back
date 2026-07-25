<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class BoardInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'board_id',
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

    public function board()
    {
        return $this->belongsTo(Board::class);
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

    public function accept(): void
    {
        $this->update(['status' => 'accepted']);
        BoardMember::firstOrCreate([
            'board_id' => $this->board_id,
            'user_id' => $this->user_id,
        ]);
    }

    public function decline(): void
    {
        $this->update(['status' => 'declined']);
    }
}
