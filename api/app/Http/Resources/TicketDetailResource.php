<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketDetailResource extends JsonResource
{
    /**
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
            'status_history' => $this->whenLoaded('statusChanges', function () {
                return $this->statusChanges->map(function ($change) {
                    return [
                        'old_status' => $change->old_status,
                        'new_status' => $change->new_status,
                        'changed_at' => $change->changed_at,
                        'changed_by' => $change->relationLoaded('changedBy') && $change->changedBy ? [
                            'id' => $change->changedBy->id,
                            'name' => $change->changedBy->name,
                            'email' => $change->changedBy->email,
                        ] : null,
                    ];
                })->values();
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
