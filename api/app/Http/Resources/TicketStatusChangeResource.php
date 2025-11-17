<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketStatusChangeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'old_status' => $this->old_status,
            'new_status' => $this->new_status,
            'changed_at' => $this->changed_at,
            'changed_by' => $this->whenLoaded('changedBy', function () {
                return $this->changedBy ? [
                    'id' => $this->changedBy->id,
                    'name' => $this->changedBy->name,
                    'email' => $this->changedBy->email,
                ] : null;
            }),
        ];
    }
}
