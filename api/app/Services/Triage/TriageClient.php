<?php

namespace App\Services\Triage;

use App\Models\Ticket;
use App\Models\User;

/**
 * Contract for triage suggestion drivers.
 * Implementations must return a normalized associative array:
 * [
 *   'priority' => string low|medium|high,
 *   'tags' => string[],
 *   'assignee_hint' => string|null,
 *   'reasoning' => string,
 *   'confidence' => float 0..1
 * ]
 */
interface TriageClient
{
    /**
     * Generate a triage suggestion for the given ticket and user.
     */
    public function suggest(Ticket $ticket, User $user): array;
}
