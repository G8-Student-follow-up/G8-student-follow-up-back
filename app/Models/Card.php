<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Card extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'board_id',
        'column_id',
        'student_id',
        'title',
        'description',
        'position',
        'priority',
        'status',
        'follow_up_date',
        'due_date',
        'created_by',
        'trainer_id',
        'cover_attachment_id',
    ];

       protected $casts = [
        'follow_up_date' => 'date:Y-m-d',
        'due_date' => 'date:Y-m-d',
    ];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function column()
    {
        return $this->belongsTo(Column::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'card_id');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'card_id');
    }

    public function coverAttachment()
    {
        return $this->belongsTo(Attachment::class, 'cover_attachment_id');
    }

    public function labels()
    {
        return $this->belongsToMany(Label::class, 'card_labels');
    }

    public function checklists()
    {
        return $this->hasMany(Checklist::class);
    }
}
