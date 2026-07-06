<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'file_path',
        'file_type',
        'file_size'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
