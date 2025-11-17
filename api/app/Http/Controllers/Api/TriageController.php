<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TriageSuggestionResource;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Triage\TriageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
}
