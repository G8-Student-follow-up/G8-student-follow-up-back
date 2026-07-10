<?php

namespace App\Http\Controllers;

use App\Models\Board;
use Illuminate\Http\Request;
use App\Models\Workspace;

class BoardController extends Controller
{
    public function index( Workspace $workspace)
    {
        return $workspace->boards;
    }

    public function store(Request $request, Workspace $workspace)
    {
       $validate = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        return response()->json($workspace->boards()->create($validate), 201);
    }

    public function show(Board $board)
    {
        return $board->load('classes', 'labels');
    }

    public function update(Request $request, Board $board)
    {
        $board->update($request->validate([
            'title' => 'sometimes|string|max:255',
            'is_favorite' => 'sometimes|boolean',
            'is_archived' => 'sometimes|boolean',
        ]));

        return response()->json($board);
    }

    public function destroy(Board $board)
    {
        $board->delete();

        return response()->json(['message' => 'Board deleted successfully'], 200);
    }
 
}
