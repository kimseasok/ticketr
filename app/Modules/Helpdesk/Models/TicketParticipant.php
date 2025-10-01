<?php

namespace App\Modules\Helpdesk\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use App\Modules\Helpdesk\Models\Contact;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

class TicketParticipant extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'brand_id',
        'ticket_id',
        'last_message_id',
        'participant_type',
        'participant_id',
        'role',
        'visibility',
        'last_seen_at',
        'last_typing_at',
        'is_muted',
        'metadata',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'last_typing_at' => 'datetime',
        'is_muted' => 'boolean',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(TicketMessage::class, 'last_message_id');
    }

    public function participantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_id')->where('participant_type', 'user');
    }

    public function participantContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'participant_id')->where('participant_type', 'contact');
    }

    public function applySnapshot(array $snapshot): void
    {
        $allowed = Arr::only($snapshot, [
            'role',
            'visibility',
            'last_seen_at',
            'last_typing_at',
            'is_muted',
            'metadata',
        ]);

        $this->fill($allowed);
    }
}
