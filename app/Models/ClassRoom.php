<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class ClassRoom extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'board_id',
        'title',
        'position'
    ];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }
}
