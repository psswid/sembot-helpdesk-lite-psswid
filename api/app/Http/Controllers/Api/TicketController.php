<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\IndexTicketRequest;
use App\Http\Resources\TicketCollection;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
