<?php

namespace App\Services\Triage;

use App\Models\Ticket;
use App\Models\User;

/**
 * Facade-like service orchestrating triage client driver calls and ensuring
 * normalized output shape.
 */
class TriageService
{
    public function __construct(public TriageClient $client) {}

    /**
     * Suggest triage data for a ticket & user.
     *
     * @return array{priority:string,tags:array<int,string>,assignee_hint:?string,reasoning:string,confidence:float,driver:string}
     */
    public function suggestFor(Ticket $ticket, User $user): array
    {
        $suggestion = $this->client->suggest($ticket, $user);

        $normalizedDefaults = [
            'priority' => 'medium',
            'tags' => ['general'],
            'assignee_hint' => null,
            'reasoning' => 'No reasoning provided.',
            'confidence' => 0.5,
            'driver' => 'unknown',
        ];

        $merged = array_merge($normalizedDefaults, $suggestion);

        // Clamp confidence
        $merged['confidence'] = max(0.0, min(1.0, (float) $merged['confidence']));

        // Ensure tags is array of strings
        if (!is_array($merged['tags'])) {
            $merged['tags'] = ['general'];
        } else {
            $merged['tags'] = array_values(array_filter(array_map(fn($t) => is_string($t) ? strtolower($t) : null, $merged['tags'])));
            if (empty($merged['tags'])) {
                $merged['tags'] = ['general'];
            }
        }

        // Sanitize priority
        $allowed = ['low', 'medium', 'high'];
        if (!in_array($merged['priority'], $allowed, true)) {
            $merged['priority'] = 'medium';
        }

        return $merged;
    }
}
