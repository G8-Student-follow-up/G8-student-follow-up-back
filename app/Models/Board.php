<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Board extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'title',
        'description',
        'color',
        'background',
        'is_favorite',
        'is_archived'
    ];

    protected $casts = [
        'is_favorite' => 'boolean',
        'is_archived' => 'boolean',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function columns()
    {
        return $this->hasMany(Column::class);
    }

    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    public function labels()
    {
        return $this->hasMany(Label::class);
    }

    public function members()
    {
        return $this->hasMany(BoardMember::class);
    }

    public function invitations()
    {
        return $this->hasMany(BoardInvitation::class);
    }
}
