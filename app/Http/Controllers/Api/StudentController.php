<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class StudentController extends Controller
{
    #[OA\Get(
        path: "/api/students",
        summary: "Get all students",
        tags: ["Students"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful response",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/Student")
                )
            )
        ]
    )]
    public function index()
    {
        return Student::with(['classroom', 'trainer', 'labels'])->get();
    }

    #[OA\Post(
        path: "/api/students",
        summary: "Create a new student",
        tags: ["Students"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["class_id", "trainer_id", "name", "priority", "status"],
                properties: [
                    new OA\Property(property: "class_id", type: "integer", example: 1),
                    new OA\Property(property: "trainer_id", type: "integer", example: 1),
                    new OA\Property(property: "name", type: "string", example: "John Doe"),
                    new OA\Property(property: "photo", type: "string", nullable: true, example: null),
                    new OA\Property(property: "description", type: "string", nullable: true, example: null),
                    new OA\Property(property: "follow_up_date", type: "string", format: "date", nullable: true, example: "2026-08-01"),
                    new OA\Property(property: "priority", type: "string", enum: ["Low", "Medium", "High"], example: "Medium"),
                    new OA\Property(property: "status", type: "string", enum: ["Pending", "In Progress", "Completed", "Archived"], example: "Pending"),
                    new OA\Property(property: "position", type: "integer", example: 0),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Student created",
                content: new OA\JsonContent(ref: "#/components/schemas/Student")
            ),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => ['required', 'integer', Rule::exists('classes', 'id')],
            'trainer_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'follow_up_date' => ['nullable', 'date'],
            'priority' => ['required', Rule::in(['Low', 'Medium', 'High'])],
            'status' => ['required', Rule::in(['Pending', 'In Progress', 'Completed', 'Archived'])],
            'position' => ['nullable', 'integer'],
        ]);

        $student = Student::create($validated);

        return response()->json($student, 201);
    }

    #[OA\Get(
        path: "/api/students/{id}",
        summary: "Get a single student",
        tags: ["Students"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful response",
                content: new OA\JsonContent(ref: "#/components/schemas/Student")
            ),
            new OA\Response(response: 404, description: "Student not found")
        ]
    )]
    public function show(Student $student)
    {
        return $student->load(['classroom', 'trainer', 'comments', 'attachments', 'labels']);
    }

    #[OA\Put(
        path: "/api/students/{id}",
        summary: "Update a student",
        tags: ["Students"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "class_id", type: "integer", example: 1),
                    new OA\Property(property: "trainer_id", type: "integer", example: 1),
                    new OA\Property(property: "name", type: "string", example: "John Doe"),
                    new OA\Property(property: "photo", type: "string", nullable: true),
                    new OA\Property(property: "description", type: "string", nullable: true),
                    new OA\Property(property: "follow_up_date", type: "string", format: "date", nullable: true),
                    new OA\Property(property: "priority", type: "string", enum: ["Low", "Medium", "High"]),
                    new OA\Property(property: "status", type: "string", enum: ["Pending", "In Progress", "Completed", "Archived"]),
                    new OA\Property(property: "position", type: "integer"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Student updated",
                content: new OA\JsonContent(ref: "#/components/schemas/Student")
            ),
            new OA\Response(response: 404, description: "Student not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'class_id' => ['sometimes', 'integer', Rule::exists('classes', 'id')],
            'trainer_id' => ['sometimes', 'integer', Rule::exists('users', 'id')],
            'name' => ['sometimes', 'string', 'max:255'],
            'photo' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'follow_up_date' => ['nullable', 'date'],
            'priority' => ['sometimes', Rule::in(['Low', 'Medium', 'High'])],
            'status' => ['sometimes', Rule::in(['Pending', 'In Progress', 'Completed', 'Archived'])],
            'position' => ['nullable', 'integer'],
        ]);

        $student->update($validated);

        return response()->json($student);
    }

    #[OA\Delete(
        path: "/api/students/{id}",
        summary: "Delete a student",
        tags: ["Students"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Student deleted"),
            new OA\Response(response: 404, description: "Student not found")
        ]
    )]
    public function destroy(Student $student)
    {
        $student->delete();

        return response()->json(null, 204);
    }
}
