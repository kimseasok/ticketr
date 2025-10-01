<?php

namespace App\Modules\Helpdesk\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class EmailOutboundMessage extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'brand_id',
        'mailbox_id',
        'ticket_id',
        'ticket_message_id',
        'subject',
        'to_recipients',
        'cc_recipients',
        'bcc_recipients',
        'text_body',
        'html_body',
        'status',
        'attempts',
        'provider_message_id',
        'scheduled_at',
        'last_attempted_at',
        'sent_at',
        'last_error',
        'metadata',
    ];

    protected $casts = [
        'to_recipients' => 'array',
        'cc_recipients' => 'array',
        'bcc_recipients' => 'array',
        'status' => 'string',
        'attempts' => 'integer',
        'scheduled_at' => 'datetime',
        'last_attempted_at' => 'datetime',
        'sent_at' => 'datetime',
        'last_error' => 'array',
        'metadata' => 'array',
    ];

    public function scopeQueued(Builder $builder): Builder
    {
        return $builder->where($builder->qualifyColumn('status'), 'queued');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function mailbox(): BelongsTo
    {
        return $this->belongsTo(EmailMailbox::class, 'mailbox_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function ticketMessage(): BelongsTo
    {
        return $this->belongsTo(TicketMessage::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(EmailAttachment::class, 'message');
    }
}
