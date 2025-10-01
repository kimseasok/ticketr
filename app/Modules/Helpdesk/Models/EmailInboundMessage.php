<?php

namespace App\Modules\Helpdesk\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class EmailInboundMessage extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'brand_id',
        'mailbox_id',
        'ticket_id',
        'ticket_message_id',
        'message_id',
        'thread_id',
        'subject',
        'from_name',
        'from_email',
        'to_recipients',
        'cc_recipients',
        'bcc_recipients',
        'text_body',
        'html_body',
        'attachments_count',
        'status',
        'received_at',
        'processed_at',
        'error_info',
        'headers',
    ];

    protected $casts = [
        'to_recipients' => 'array',
        'cc_recipients' => 'array',
        'bcc_recipients' => 'array',
        'attachments_count' => 'integer',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'error_info' => 'array',
        'headers' => 'array',
    ];

    public function scopePending(Builder $builder): Builder
    {
        return $builder->where($builder->qualifyColumn('status'), 'pending');
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
