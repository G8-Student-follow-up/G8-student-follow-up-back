<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Board extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'title',
        'color',
        'description',
        'is_favorite',
        'is_archived'
    ];

    protected $casts = [
        'is_favorite' => 'boolean',
        'is_archived' => 'boolean',
    ];

    protected $attributes = [
        'is_favorite' => false,
        'is_archived' => false,
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function classes()
    {
        return $this->hasMany(ClassRoom::class, 'board_id');
    }
}
