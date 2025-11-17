<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\IndexTicketRequest;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Http\Resources\TicketCollection;
use App\Http\Resources\TicketResource;
use App\Http\Resources\TicketDetailResource;
use App\Http\Resources\TicketStatusChangeResource;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    /**
     * GET /api/tickets — List tickets with filtering and pagination.
     */
    public function index(IndexTicketRequest $request): TicketCollection
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();
        $filters = array_intersect_key($validated, array_flip(['status', 'priority', 'assignee_id', 'tags']));

        $query = Ticket::query()
            ->visibleTo($user)
            ->withListRelations()
            ->applyFilters($filters)
            ->latest('created_at');

        $perPage = (int) ($validated['per_page'] ?? 15);

        return new TicketCollection($query->paginate($perPage)->appends($request->query()));
    }

    /**
     * POST /api/tickets — Create a new ticket.
     */
    public function store(StoreTicketRequest $request): \Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        $ticket = Ticket::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'],
            'status' => TicketStatus::Open->value,
            'reporter_id' => $user->id,
            'tags' => $validated['tags'] ?? null,
        ]);

        $ticket->load(['assignee', 'reporter']);

        return (new TicketResource($ticket))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/tickets/{ticket} — Show single ticket with relations and status history.
     */
    public function show(Ticket $ticket): TicketDetailResource
    {
        $ticket->load([
            'assignee',
            'reporter',
            'statusChanges' => function ($q) {
                $q->orderBy('changed_at');
            },
            'statusChanges.changedBy',
        ]);

        return new TicketDetailResource($ticket);
    }

    /**
     * PUT/PATCH /api/tickets/{ticket} — Update ticket and record status changes.
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket): TicketResource
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        // Capture old status (raw string) before any changes
        $oldStatus = $ticket->getOriginal('status');

        DB::transaction(function () use ($ticket, $validated, $oldStatus, $user) {
            // Only fill provided keys to avoid unintended overwrites
            $ticket->fill($validated);
            $ticket->save();

            // If status provided and changed, record history
            if (array_key_exists('status', $validated)) {
                $newStatus = $validated['status'];
                if ($newStatus !== $oldStatus) {
                    $ticket->statusChanges()->create([
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'changed_by_user_id' => $user->id,
                        'changed_at' => now(),
                    ]);
                }
            }
        });

        $ticket->load(['assignee', 'reporter']);

        return new TicketResource($ticket);
    }

    /**
     * DELETE /api/tickets/{ticket} — Soft delete a ticket (admin-only via policy).
     */
    public function destroy(Ticket $ticket): \Illuminate\Http\JsonResponse
    {
        $ticket->delete();

        return response()->json(null, 204);
    }

    /**
     * GET /api/tickets/{ticket}/status-history — List status changes chronologically.
     */
    public function statusHistory(Ticket $ticket)
    {
        $changes = $ticket->statusChanges()
            ->with('changedBy')
            ->orderBy('changed_at')
            ->get();

        return TicketStatusChangeResource::collection($changes);
    }
}
