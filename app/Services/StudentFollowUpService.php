<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Activity;

class StudentFollowUpService
{
    public function createStudent(array $data): Student
    {
        $student = Student::create($data);
        $this->logActivity('created student', $student);
        return $student;
    }

    public function logActivity(string $action, Student $subject): void
    {
        Activity::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => Student::class,
            'subject_id' => $subject->id,
            'changes' => $subject->getChanges(),
        ]);
    }
}