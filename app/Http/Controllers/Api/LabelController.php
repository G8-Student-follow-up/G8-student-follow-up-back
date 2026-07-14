<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Label;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    public function index(Board $board)
    {
        return response()->json(['labels' => $board->labels]);
    }

    public function store(Request $request, Board $board)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:20',
        ]);

        $label = $board->labels()->create($validated);

        return response()->json(['label' => $label], 201);
    }

    public function update(Request $request, Label $label)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'color' => 'sometimes|string|max:20',
        ]);

        $label->update($validated);

        return response()->json(['label' => $label]);
    }

    public function destroy(Label $label)
    {
        $label->delete();

        return response()->json(null, 204);
    }
}
