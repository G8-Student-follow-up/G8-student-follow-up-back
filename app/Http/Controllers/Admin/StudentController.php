<?php

namespace App\Http\Controllers\Admin;

use App\Models\Student;
use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;

class StudentController extends Controller
{
    #[OA\Get(
        path: "/api/students",
        summary: "Get all students",
        tags: ["Students"],
        responses: [
            new OA\Response(response: 200, description: "Successful response")
        ]
    )]
    public function index()
    {
        return Student::all();
    }

    // ... rest of your existing methods
}