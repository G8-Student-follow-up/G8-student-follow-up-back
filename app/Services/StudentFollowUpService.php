<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Activity;


// app/Services/StudentFollowUpService.php
class StudentFollowUpService
{
    public function createStudent(array $data): Student
    {
        $student = Student::create($data);
        $this->logActivity('created student', $student);
        return $student;
    }

    public function moveStudent(Student $student, int $classId, int $position): Student
    {
        $student->update(['class_id' => $classId, 'position' => $position]);
        $this->logActivity('moved student', $student);
        return $student;
    }

    public function assignTrainer(Student $student, int $trainerId): Student
    {
        $student->update(['trainer_id' => $trainerId]);
        $this->logActivity('assigned trainer', $student);
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