<?php

namespace App\Services\Triage;

use App\AiAgents\TriageAgent;
use App\Models\Ticket;
use App\Models\User;
use Throwable;

/**
 * Real LLM-backed implementation using LarAgent + OpenRouter-compatible models.
 */
class OpenAiTriageClient implements TriageClient
{
    public function suggest(Ticket $ticket, User $user): array
    {
        $prompt = $this->buildPrompt($ticket, $user);

        try {
            $response = TriageAgent::for('ticket_'.$ticket->id.'_user_'.$user->id)
                ->responseSchema($this->schema())
                ->respond($prompt);

            // Ensure structure & fallbacks
            $normalized = [
                'priority' => $response['priority'] ?? 'medium',
                'tags' => $response['tags'] ?? ['general'],
                'assignee_hint' => $response['assignee_hint'] ?? null,
                'reasoning' => $response['reasoning'] ?? 'LLM response missing reasoning.',
                'confidence' => isset($response['confidence']) ? (float) $response['confidence'] : 0.65,
                'driver' => 'openai',
            ];

            return $normalized;
        } catch (Throwable $e) {
            // Fallback to mock heuristics on any failure
            $fallback = (new MockTriageClient())->suggest($ticket, $user);
            $fallback['reasoning'] .= ' (Fallback after LLM error: '.$e->getMessage().')';
            return $fallback;
        }
    }

    protected function buildPrompt(Ticket $ticket, User $user): string
    {
        $assigneeId = $ticket->assignee_id ?: 'none';
        $description = $ticket->description ?? '';
        $priority = method_exists($ticket->priority, 'value') ? $ticket->priority->value : (string) $ticket->priority;
        $status = method_exists($ticket->status, 'value') ? $ticket->status->value : (string) $ticket->status;

        return <<<PROMPT
You are a helpdesk triage assistant. Analyze the ticket and propose priority, tags, assignee hint, reasoning and confidence.
Return ONLY structured JSON per the provided schema.

Ticket ID: {$ticket->id}
Title: {$ticket->title}
Description: {$description}
Current Priority: {$priority}
Current Status: {$status}
Reporter ID: {$ticket->reporter_id}
Assignee ID: {$assigneeId}

Guidelines:
- High priority if affecting many users, data loss, crash, security or payment failure.
- Tags should be concise lowercase words (1-2 words each).
- assignee_hint may be 'agent', 'admin', 'unassigned', or 'reporter'. Use 'agent' for complex or high priority items.
- Confidence 0..1; be conservative without strong indicators.
PROMPT;
    }

    /**
     * JSON Schema used for strict structured output.
     */
    protected function schema(): array
    {
        return [
            'name' => 'triage_suggestion',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'priority' => [
                        'type' => 'string',
                        'enum' => ['low', 'medium', 'high'],
                    ],
                    'tags' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'default' => [],
                    ],
                    'assignee_hint' => [
                        'type' => ['string', 'null'],
                        'enum' => ['agent', 'admin', 'unassigned', 'reporter', null],
                        'default' => null,
                    ],
                    'reasoning' => [
                        'type' => 'string',
                    ],
                    'confidence' => [
                        'type' => 'number',
                        'minimum' => 0,
                        'maximum' => 1,
                    ],
                ],
                'required' => ['priority', 'tags', 'reasoning', 'confidence'],
            ],
        ];
    }
}
