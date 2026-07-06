<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'trainer_id',
        'name',
        'photo',
        'description',
        'follow_up_date',
        'priority',
        'status',
        'position'
    ];

    protected $casts = [
        'follow_up_date' => 'date',
    ];

    public function classroom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function labels()
    {
        return $this->belongsToMany(Label::class, 'student_labels');
    }
}
