<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the comment into the shape expected by the frontend.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => [
                'name' => $this->user?->name ?? 'Unknown',
                'avatar_url' => $this->user?->avatar_url ?? null,
                'position' => $this->user?->position ?? null,
            ],
            'message' => $this->message,
            'created_at' => $this->created_at?->toIso8601String(),
            'is_pinned' => (bool) $this->is_pinned,
        ];
    }
}
