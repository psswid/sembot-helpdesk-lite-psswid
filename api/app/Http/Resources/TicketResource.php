<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $priority = $this->priority;
        $status = $this->status;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'priority' => is_object($priority) && method_exists($priority, 'value') ? $priority->value : $priority,
            'status' => is_object($status) && method_exists($status, 'value') ? $status->value : $status,
            'assignee' => $this->whenLoaded('assignee', function () {
                return $this->assignee ? [
                    'id' => $this->assignee->id,
                    'name' => $this->assignee->name,
                    'email' => $this->assignee->email,
                ] : null;
            }),
            'reporter' => $this->whenLoaded('reporter', function () {
                return $this->reporter ? [
                    'id' => $this->reporter->id,
                    'name' => $this->reporter->name,
                    'email' => $this->reporter->email,
                ] : null;
            }),
            'tags' => $this->tags ?? [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
