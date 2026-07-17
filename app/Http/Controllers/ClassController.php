<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClassController extends Controller
{
    public function index()
    {
        return ClassRoom::with('board')->orderBy('position')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'board_id' => ['required', 'integer', Rule::exists('boards', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        if (!isset($validated['position'])) {
            $maxPosition = ClassRoom::where('board_id', $validated['board_id'])->max('position');
            $validated['position'] = ($maxPosition ?? -1) + 1;
        }

        $class = ClassRoom::create($validated);

        return response()->json($class, 201);
    }

    public function show(ClassRoom $classRoom)
    {
        return $classRoom->load(['board', 'students']);
    }

    public function update(Request $request, ClassRoom $classRoom)
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
            'board_id' => ['sometimes', 'integer', Rule::exists('boards', 'id')],
        ]);

        $classRoom->update($validated);

        return response()->json($classRoom);
    }

    public function destroy(ClassRoom $classRoom)
    {
        $classRoom->delete();

        return response()->json(['message' => 'Class deleted successfully'], 200);
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'classes' => ['required', 'array'],
            'classes.*.id' => ['required', 'integer', Rule::exists('classes', 'id')],
            'classes.*.position' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['classes'] as $item) {
            ClassRoom::where('id', $item['id'])->update(['position' => $item['position']]);
        }

        return response()->json(['message' => 'Classes reordered'], 200);
    }
}
