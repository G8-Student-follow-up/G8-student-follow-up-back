<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Student",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "class_id", type: "integer", example: 1),
        new OA\Property(property: "trainer_id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "John Doe"),
        new OA\Property(property: "photo", type: "string", nullable: true, example: null),
        new OA\Property(property: "description", type: "string", nullable: true, example: null),
        new OA\Property(property: "follow_up_date", type: "string", format: "date", nullable: true, example: "2026-08-01"),
        new OA\Property(property: "priority", type: "string", enum: ["Low", "Medium", "High"], example: "Medium"),
        new OA\Property(property: "status", type: "string", enum: ["Pending", "In Progress", "Completed", "Archived"], example: "Pending"),
        new OA\Property(property: "position", type: "integer", example: 0),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
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
