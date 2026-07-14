<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->workspaces()->with('boards')->get();
    }

    public function store(Request $request) {
        $data = $request->validate(['name' => 'required|string|max:225']);
        $workspace = Workspace::create([
            ...$data,
            'owner_id' => $request->user()->id,
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json($workspace, 201);
    }

    public function show(Workspace $workspace) {
        return $workspace->load('boards');
    }

    public function update(Request $request, Workspace $workspace) {
        $workspace->update($request->validate(['name' => 'sometimes|string|max:225']));
        return response()->json($workspace);
    }

    public function destroy(Workspace $workspace) {
        $workspace->delete();

        return response()->json(['message' => 'Workspace deleted']);
    }

    public function inviteTrainer(Request $request, Workspace $workspace) {
        $data = $request->validate(['user_id' => 'required|exists:users,id']);

        WorkspaceMember::firstOrCreate([
            'workspace_id' => $workspace->id,
            'user_id' => $data['user_id'],
        ]);

        return response()->json(['message' => 'Trainer invited']);
    }

    public function removeMember(Workspace $workspace, $userId) {
        WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $userId)
            ->delete();

        return response()->json(['message' => 'Member removed']);
    }
}
