<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketStatusChange extends Model
{
    use HasFactory;

    protected $table = 'ticket_status_changes';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_id',
        'old_status',
        'new_status',
        'changed_by_user_id',
        'changed_at',
    ];

    public $timestamps = false; // using explicit changed_at

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
