<?php

namespace App\Modules\Helpdesk\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use App\Modules\Helpdesk\Models\Contact;
use App\Modules\Helpdesk\Models\EmailInboundMessage;
use App\Modules\Helpdesk\Models\EmailOutboundMessage;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class TicketMessage extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'brand_id',
        'ticket_id',
        'author_type',
        'author_id',
        'visibility',
        'channel',
        'external_id',
        'dedupe_hash',
        'attachments_count',
        'body',
        'metadata',
        'posted_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'posted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $message): void {
            $message->posted_at ??= now();
            $message->dedupe_hash ??= self::generateHash(
                $message->external_id,
                (string) $message->author_id,
                (string) Str::of($message->body)->limit(140)
            );
        });
    }

    public function scopeForTicket(Builder $builder, int $ticketId): Builder
    {
        return $builder->where($builder->qualifyColumn('ticket_id'), $ticketId);
    }

    public function scopeVisibleTo(Builder $builder, Authenticatable $user): Builder
    {
        if (method_exists($user, 'hasRole') && ($user->hasRole('Admin') || $user->hasRole('Agent'))) {
            return $builder;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('Viewer')) {
            return $builder->where($builder->qualifyColumn('visibility'), 'public');
        }

        return $builder->whereRaw('0 = 1');
    }

    public function scopePublicOnly(Builder $builder): Builder
    {
        return $builder->where($builder->qualifyColumn('visibility'), 'public');
    }

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

    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id')
            ->where($this->qualifyColumn('author_type'), 'user');
    }

    public function authorContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'author_id')
            ->where($this->qualifyColumn('author_type'), 'contact');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function inboundEmail(): HasOne
    {
        return $this->hasOne(EmailInboundMessage::class);
    }

    public function outboundEmail(): HasOne
    {
        return $this->hasOne(EmailOutboundMessage::class);
    }

    public static function generateHash(?string $externalId, string $authorId, string $bodyPreview): string
    {
        return hash('sha256', implode('|', [$externalId, $authorId, $bodyPreview]));
    }

    public function auditPayload(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'brand_id' => $this->brand_id,
            'ticket_id' => $this->ticket_id,
            'visibility' => $this->visibility,
            'channel' => $this->channel,
            'attachments_count' => $this->attachments_count,
            'posted_at' => $this->posted_at?->toIso8601String(),
            'metadata' => Arr::only($this->metadata ?? [], ['source', 'adapter']),
        ];
    }
}
