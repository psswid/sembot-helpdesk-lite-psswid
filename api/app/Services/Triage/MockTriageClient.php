<?php

namespace App\Services\Triage;

use App\Models\Ticket;
use App\Models\User;

/**
 * Mock implementation that derives suggestions from simple keyword heuristics.
 */
class MockTriageClient implements TriageClient
{
    public function suggest(Ticket $ticket, User $user): array
    {
        $text = strtolower(($ticket->title ?? '') . ' ' . ($ticket->description ?? ''));

        $priority = 'low';
        $tags = [];
        $confidence = 0.55;
        $reasonParts = [];

        $boost = function (float $base, float $inc): float {
            return min(1.0, $base + $inc);
        };

        $keywordMap = [
            'crash' => ['priority' => 'high', 'tag' => 'crash', 'conf' => 0.25],
            'error' => ['priority' => 'high', 'tag' => 'error', 'conf' => 0.15],
            'urgent' => ['priority' => 'high', 'tag' => 'urgent', 'conf' => 0.2],
            'billing' => ['priority' => 'medium', 'tag' => 'billing', 'conf' => 0.15],
            'payment' => ['priority' => 'medium', 'tag' => 'billing', 'conf' => 0.15],
            'slow' => ['priority' => 'medium', 'tag' => 'performance', 'conf' => 0.1],
            'timeout' => ['priority' => 'medium', 'tag' => 'performance', 'conf' => 0.1],
        ];

        foreach ($keywordMap as $needle => $meta) {
            if (str_contains($text, $needle)) {
                if ($meta['priority'] === 'high') {
                    $priority = 'high';
                } elseif ($priority !== 'high') {
                    $priority = $meta['priority'];
                }
                $tags[] = $meta['tag'];
                $confidence = $boost($confidence, $meta['conf']);
                $reasonParts[] = "Keyword '{$needle}' suggests {$meta['priority']} priority.";
            }
        }

        $tags = array_values(array_unique($tags));
        if (empty($tags)) {
            $tags[] = 'general';
            $reasonParts[] = 'No strong keywords found; defaulting to general low-impact.';
        }

        $assigneeHint = $priority === 'high' ? 'agent' : null;

        return [
            'priority' => $priority,
            'tags' => $tags,
            'assignee_hint' => $assigneeHint,
            'reasoning' => implode(' ', $reasonParts),
            'confidence' => round($confidence, 2),
            'driver' => 'mock',
        ];
    }
}
