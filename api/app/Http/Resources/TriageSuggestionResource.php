<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TriageSuggestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'priority' => $this->resource['priority'] ?? 'medium',
            'tags' => $this->resource['tags'] ?? ['general'],
            'assignee_hint' => $this->resource['assignee_hint'] ?? null,
            'reasoning' => $this->resource['reasoning'] ?? '',
            'confidence' => $this->resource['confidence'] ?? 0.5,
            'driver' => $this->resource['driver'] ?? 'unknown',
        ];
    }
}
