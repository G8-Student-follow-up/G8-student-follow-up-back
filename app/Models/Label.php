<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Label extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color'
    ];

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_labels');
    }
}