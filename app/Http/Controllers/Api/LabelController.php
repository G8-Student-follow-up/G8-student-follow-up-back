<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Traits\ChecksBoardAccess;
use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Label;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    use ChecksBoardAccess;

    public function index(Board $board)
    {
        $this->ensureBoardAccess($board, request()->user());

        return response()->json(['labels' => $board->labels]);
    }

    public function store(Request $request, Board $board)
    {
        $this->ensureBoardAccess($board, $request->user());

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:20',
        ]);

        $label = $board->labels()->create($validated);

        return response()->json(['label' => $label], 201);
    }

    public function update(Request $request, Label $label)
    {
        $this->ensureBoardAccess($label->board, $request->user());

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'color' => 'sometimes|string|max:20',
        ]);

        $label->update($validated);

        return response()->json(['label' => $label]);
    }

    public function destroy(Request $request, Label $label)
    {
        $this->ensureBoardAccess($label->board, $request->user());

        $label->delete();

        return response()->json(null, 204);
    }
}
