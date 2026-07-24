<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Traits\ChecksBoardAccess;
use App\Http\Controllers\Controller;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use Illuminate\Http\Request;

class ChecklistController extends Controller
{
    use ChecksBoardAccess;

    public function addItem(Request $request, Checklist $checklist)
    {
        $this->ensureBoardAccess($checklist->card->board, $request->user());

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'position' => 'nullable|integer',
        ]);

        $item = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'title' => $validated['title'],
            'position' => $validated['position'] ?? 0,
        ]);

        return response()->json(['item' => $item], 201);
    }

    public function updateItem(Request $request, ChecklistItem $checklistItem)
    {
        $this->ensureBoardAccess($checklistItem->checklist->card->board, $request->user());

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'is_completed' => 'sometimes|boolean',
            'position' => 'nullable|integer',
        ]);

        $checklistItem->update($validated);

        return response()->json(['item' => $checklistItem]);
    }

    public function destroyItem(Request $request, ChecklistItem $checklistItem)
    {
        $this->ensureBoardAccess($checklistItem->checklist->card->board, $request->user());

        $checklistItem->delete();

        return response()->json(null, 204);
    }
}
