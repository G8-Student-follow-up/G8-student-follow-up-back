<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'card_id',
        'file_path',
        'file_type',
        'file_size'
    ];

    protected $appends = ['file_name', 'file_url'];

    public function getFileNameAttribute(): string
    {
        return basename($this->file_path);
    }

    public function getFileUrlAttribute(): ?string
    {
        if ($this->file_path) {
            return asset('storage/' . $this->file_path);
        }
        return null;
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}
