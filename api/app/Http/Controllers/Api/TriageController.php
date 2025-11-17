<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Triage\AcceptTriageRequest;
use App\Http\Requests\Triage\RejectTriageRequest;
use App\Http\Resources\TicketResource;
use App\Http\Resources\TriageSuggestionResource;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Triage\TriageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TriageController extends Controller
{
    /**
     * POST /api/tickets/{ticket}/triage-suggest
     * Returns LLM-backed triage suggestion for the given ticket.
     */
    public function suggest(Request $request, Ticket $ticket, TriageService $triageService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $correlationId = (string) Str::uuid();

        // Authorization is enforced by route middleware: can:update,ticket
        Log::info('triage.suggest.start', [
            'ticket_id' => $ticket->id,
            'user_id' => $user?->id,
            'correlation_id' => $correlationId,
            'driver' => config('services.llm.driver'),
        ]);

        try {
            $suggestion = $triageService->suggestFor($ticket, $user);

            Log::info('triage.suggest.success', [
                'ticket_id' => $ticket->id,
                'user_id' => $user?->id,
                'correlation_id' => $correlationId,
                'driver' => $suggestion['driver'] ?? 'unknown',
            ]);

            return (new TriageSuggestionResource($suggestion))
                ->response()
                ->setStatusCode(200);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('triage.suggest.timeout', [
                'ticket_id' => $ticket->id,
                'user_id' => $user?->id,
                'correlation_id' => $correlationId,
                'error' => 'timeout',
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'triage_timeout',
                'message' => 'The triage service timed out.',
                'correlation_id' => $correlationId,
            ], 504);
        } catch (\Throwable $e) {
            Log::error('triage.suggest.error', [
                'ticket_id' => $ticket->id,
                'user_id' => $user?->id,
                'correlation_id' => $correlationId,
                'error' => 'upstream_failure',
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'triage_unavailable',
                'message' => 'The triage service is currently unavailable.',
                'correlation_id' => $correlationId,
            ], 503);
        }
    }

    /**
     * POST /api/tickets/{ticket}/triage-accept
     * Applies accepted triage suggestion to the ticket (priority, tags, optional assignee).
     */
    public function accept(AcceptTriageRequest $request, Ticket $ticket): TicketResource
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $correlationId = (string) ($validated['correlation_id'] ?? Str::uuid());

        Log::info('triage.accept.start', [
            'ticket_id' => $ticket->id,
            'user_id' => $user?->id,
            'correlation_id' => $correlationId,
        ]);

        $oldStatus = $ticket->getOriginal('status');

        DB::transaction(function () use ($ticket, $validated, $oldStatus, $user) {
            // Priority (required)
            if (isset($validated['priority'])) {
                $ticket->priority = $validated['priority'];
            }

            // Tags (optional)
            if (array_key_exists('tags', $validated)) {
                $tags = is_array($validated['tags']) ? $validated['tags'] : [];
                $ticket->tags = array_values(array_unique(array_map(fn ($t) => strtolower($t), $tags)));
            }

            // Assignee (optional; only agents/admins may set)
            if (array_key_exists('assignee_id', $validated)) {
                if ($user->hasRole(['agent', 'admin'])) {
                    $ticket->assignee_id = $validated['assignee_id']; // may be null
                }
            }

            // Optional status change with history logging
            if (array_key_exists('status', $validated)) {
                $newStatus = $validated['status'];
                $ticket->status = $newStatus;

                if ($newStatus !== $oldStatus) {
                    $ticket->statusChanges()->create([
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'changed_by_user_id' => $user->id,
                        'changed_at' => now(),
                    ]);
                }
            }

            $ticket->save();
        });

        $ticket->load(['assignee', 'reporter']);

        Log::info('triage.accept.success', [
            'ticket_id' => $ticket->id,
            'user_id' => $user?->id,
            'correlation_id' => $correlationId,
        ]);

        return new TicketResource($ticket);
    }

    /**
     * POST /api/tickets/{ticket}/triage-reject
     * Records a rejection event for analytics/observability.
     */
    public function reject(RejectTriageRequest $request, Ticket $ticket): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();
        $correlationId = (string) ($validated['correlation_id'] ?? Str::uuid());

        Log::info('triage.reject', [
            'ticket_id' => $ticket->id,
            'user_id' => $user?->id,
            'correlation_id' => $correlationId,
            'reason' => $validated['reason'] ?? null,
        ]);

        return response()->json(null, 204);
    }
}
