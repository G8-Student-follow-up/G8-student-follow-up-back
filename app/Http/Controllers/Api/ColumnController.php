<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Traits\ChecksBoardAccess;
use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Column;
use Illuminate\Http\Request;

class ColumnController extends Controller
{
    use ChecksBoardAccess;

    public function store(Request $request)
    {
        $validated = $request->validate([
            'board_id' => 'required|exists:boards,id',
            'title' => 'required|string|max:255',
            'position' => 'nullable|integer',
            'color' => 'nullable|string|max:20',
        ]);

        $board = Board::findOrFail($validated['board_id']);
        $this->ensureBoardAccess($board, $request->user());

        $column = Column::create($validated);

        return response()->json(['column' => $column], 201);
    }

    public function update(Request $request, Column $column)
    {
        $this->ensureBoardAccess($column->board, $request->user());

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'position' => 'nullable|integer',
        ]);

        $column->update($validated);

        return response()->json(['column' => $column]);
    }

    public function destroy(Request $request, Column $column)
    {
        $this->ensureBoardAccess($column->board, $request->user());

        $column->delete();

        return response()->json(null, 204);
    }

    public function reorder(Request $request, Board $board)
    {
        $this->ensureBoardAccess($board, $request->user());

        $validated = $request->validate([
            'columns' => 'required|array',
            'columns.*.id' => 'required|exists:columns,id',
            'columns.*.position' => 'required|integer',
        ]);

        foreach ($validated['columns'] as $item) {
            Column::where('id', $item['id'])
                ->where('board_id', $board->id)
                ->update(['position' => $item['position']]);
        }

        return response()->json(null, 200);
    }
}
