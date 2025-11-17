<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Ticket extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'priority',
        'status',
        'assignee_id',
        'reporter_id',
        'tags',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'tags' => 'array',
        ];
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function statusChanges()
    {
        return $this->hasMany(TicketStatusChange::class);
    }

    /**
     * Scope: tickets visible to the given user according to role rules.
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->hasRole(['admin', 'agent'])) {
            return $query;
        }

        if ($user->hasRole('reporter')) {
            return $query->where('reporter_id', $user->id);
        }

        // No role: return empty result set for safety
        return $query->whereRaw('1 = 0');
    }

    /**
     * Scope: eager load default relations for list views.
     */
    public function scopeWithListRelations(Builder $query): Builder
    {
        return $query->with(['assignee', 'reporter']);
    }

    /**
     * Scope: apply common filter parameters.
     *
     * @param  array{
     *     status?: string,
     *     priority?: string,
     *     assignee_id?: int,
     *     tags?: array<int,string>
     * }  $filters
     */
    public function scopeApplyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['assignee_id'])) {
            $query->where('assignee_id', $filters['assignee_id']);
        }

        if (! empty($filters['tags']) && is_array($filters['tags'])) {
            foreach ($filters['tags'] as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        return $query;
    }
}
