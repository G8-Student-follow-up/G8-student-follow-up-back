<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ClassRoom",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "board_id", type: "integer", example: 1),
        new OA\Property(property: "title", type: "string", example: "Morning Class"),
        new OA\Property(property: "position", type: "integer", example: 0),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
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
        return $this->hasMany(Student::class, 'class_id')->orderBy('position');
    }
}
