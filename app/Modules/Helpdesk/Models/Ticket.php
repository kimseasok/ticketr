<?php

namespace App\Modules\Helpdesk\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use App\Modules\Helpdesk\Events\TicketStatusChanged;
use App\Modules\Helpdesk\Models\Attachment;
use App\Modules\Helpdesk\Models\TicketCategory;
use App\Modules\Helpdesk\Models\TicketMessage;
use App\Modules\Helpdesk\Models\TicketPriority;
use App\Modules\Helpdesk\Models\TicketStatus;
use App\Modules\Helpdesk\Models\TicketTag;
use App\Modules\Helpdesk\Models\TicketWorkflowTransition;
use App\Modules\Helpdesk\Services\TicketSlaEvaluator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class Ticket extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use SoftDeletes;

    public const STATUS_OPEN = 'open';
    public const STATUS_PENDING = 'pending';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'tenant_id',
        'brand_id',
        'contact_id',
        'company_id',
        'created_by',
        'assigned_to',
        'subject',
        'description',
        'status',
        'priority',
        'channel',
        'reference',
        'metadata',
        'status_changed_at',
        'first_response_due_at',
        'resolution_due_at',
        'first_responded_at',
        'resolved_at',
        'closed_at',
        'archived_at',
        'last_customer_reply_at',
        'last_agent_reply_at',
        'last_activity_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'status_changed_at' => 'datetime',
        'first_response_due_at' => 'datetime',
        'resolution_due_at' => 'datetime',
        'first_responded_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'archived_at' => 'datetime',
        'last_customer_reply_at' => 'datetime',
        'last_agent_reply_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $ticket): void {
            $ticket->status_changed_at ??= now();
            $ticket->last_activity_at ??= now();
        });

        static::saving(function (self $ticket): void {
            $ticket->applySlaMetadata();
        });

        static::updating(function (self $ticket): void {
            if ($ticket->isDirty('status')) {
                self::ensureStatusTransitionIsAllowed($ticket);

                $previousStatus = $ticket->getOriginal('status');
                $ticket->status_changed_at = now();
                if ($ticket->status === self::STATUS_ARCHIVED && $ticket->archived_at === null) {
                    $ticket->archived_at = now();
                }
                event(new TicketStatusChanged($ticket, $previousStatus, $ticket->status));
            }

            if ($ticket->isDirty(['description', 'status', 'priority', 'assigned_to'])) {
                $ticket->last_activity_at = now();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function statusDefinition(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'status', 'slug')
            ->where('ticket_statuses.tenant_id', $this->tenant_id);
    }

    public function priorityDefinition(): BelongsTo
    {
        return $this->belongsTo(TicketPriority::class, 'priority', 'slug')
            ->where('ticket_priorities.tenant_id', $this->tenant_id);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(TicketCategory::class, 'ticket_category_ticket')
            ->withPivot(['assigned_at', 'assigned_by', 'tenant_id'])
            ->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(TicketTag::class, 'ticket_tag_ticket')
            ->withPivot(['assigned_at', 'assigned_by', 'tenant_id'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('posted_at');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function watcherParticipants(): HasMany
    {
        return $this->hasMany(TicketParticipant::class)
            ->where('role', 'watcher')
            ->where('participant_type', 'user');
    }

    public function scopeForBrand(Builder $builder, int $brandId): Builder
    {
        return $builder->where($builder->qualifyColumn('brand_id'), $brandId);
    }

    public function auditPayload(): array
    {
        $payload = Arr::only($this->getAttributes(), [
            'id',
            'tenant_id',
            'brand_id',
            'contact_id',
            'company_id',
            'assigned_to',
            'status',
            'priority',
            'channel',
            'status_changed_at',
            'first_response_due_at',
            'resolution_due_at',
            'first_responded_at',
            'resolved_at',
            'closed_at',
            'archived_at',
            'last_activity_at',
        ]);

        $payload['metadata'] = $this->safeMetadata();

        return $payload;
    }

    /**
     * @param  array<int,int>  $ids
     */
    public function syncCategories(array $ids, ?int $userId = null): void
    {
        $validIds = TicketCategory::withoutGlobalScope('tenant')
            ->where('tenant_id', $this->tenant_id)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();

        $this->categories()->sync($this->preparePivotData($validIds, $userId));
    }

    /**
     * @param  array<int,int>  $ids
     */
    public function syncTags(array $ids, ?int $userId = null): void
    {
        $validIds = TicketTag::withoutGlobalScope('tenant')
            ->where('tenant_id', $this->tenant_id)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();

        $this->tags()->sync($this->preparePivotData($validIds, $userId));
    }

    /**
     * @param  array<int,int>  $watcherIds
     */
    public function syncWatchers(array $watcherIds, int $actorId): void
    {
        $watcherIds = Collection::make($watcherIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        DB::transaction(function () use ($watcherIds, $actorId): void {
            $existing = TicketParticipant::query()
                ->where('tenant_id', $this->tenant_id)
                ->where('ticket_id', $this->id)
                ->where('participant_type', 'user')
                ->where('role', 'watcher')
                ->pluck('participant_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values();

            $validWatchers = User::query()
                ->where('tenant_id', $this->tenant_id)
                ->when($this->brand_id !== null, function ($query): void {
                    $query->where(function ($query): void {
                        $query->whereNull('brand_id')
                            ->orWhere('brand_id', $this->brand_id);
                    });
                })
                ->whereIn('id', $watcherIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values();

            $toDelete = $existing->diff($validWatchers);
            if ($toDelete->isNotEmpty()) {
                TicketParticipant::query()
                    ->where('tenant_id', $this->tenant_id)
                    ->where('ticket_id', $this->id)
                    ->where('participant_type', 'user')
                    ->where('role', 'watcher')
                    ->whereIn('participant_id', $toDelete)
                    ->delete();
            }

            foreach ($validWatchers as $watcherId) {
                TicketParticipant::query()->updateOrCreate([
                    'tenant_id' => $this->tenant_id,
                    'ticket_id' => $this->id,
                    'participant_type' => 'user',
                    'participant_id' => $watcherId,
                ], [
                    'brand_id' => $this->brand_id,
                    'role' => 'watcher',
                    'visibility' => 'internal',
                ]);
            }

            $this->recordWatcherAudit($existing->all(), $validWatchers->all(), $actorId);
        });
    }

    private function recordWatcherAudit(array $previous, array $current, int $actorId): void
    {
        if ($previous === $current) {
            return;
        }

        AuditLog::create([
            'tenant_id' => $this->tenant_id,
            'brand_id' => $this->brand_id,
            'user_id' => $actorId,
            'action' => 'ticket.watchers.synced',
            'auditable_type' => self::class,
            'auditable_id' => $this->id,
            'old_values' => ['watchers' => $previous ?: []],
            'new_values' => ['watchers' => $current ?: []],
            'metadata' => [
                'reference' => $this->reference,
            ],
        ]);

        Log::channel('stack')->info('ticket.watchers_synced', [
            'ticket_id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'brand_id' => $this->brand_id,
            'actor_id' => $actorId,
            'watchers' => $current,
        ]);
    }

    public function safeMetadata(): array
    {
        $metadata = $this->metadata ?? [];

        return Arr::only($metadata, ['sla']);
    }

    /**
     * @param  array<int,int>  $ids
     * @return array<int,array<string,mixed>>
     */
    private function preparePivotData(array $ids, ?int $userId): array
    {
        return collect($ids)
            ->mapWithKeys(function (int $id) use ($userId) {
                return [
                    $id => [
                        'tenant_id' => $this->tenant_id,
                        'assigned_by' => $userId,
                        'assigned_at' => now(),
                    ],
                ];
            })
            ->all();
    }

    private static function ensureStatusTransitionIsAllowed(self $ticket): void
    {
        $fromStatus = $ticket->getOriginal('status');
        $toStatus = $ticket->status;

        if ($fromStatus === $toStatus) {
            return;
        }

        $tenantId = $ticket->tenant_id;

        $from = TicketStatus::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('slug', $fromStatus)
            ->first();

        $to = TicketStatus::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('slug', $toStatus)
            ->first();

        if (! $from || ! $to) {
            throw ValidationException::withMessages([
                'status' => __('The requested status is not available for this tenant.'),
            ]);
        }

        $allowed = TicketWorkflowTransition::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('from_status_id', $from->id)
            ->where('to_status_id', $to->id)
            ->exists();

        if (! $allowed) {
            throw ValidationException::withMessages([
                'status' => __('Transition from :from to :to is not allowed.', [
                    'from' => $fromStatus,
                    'to' => $toStatus,
                ]),
            ]);
        }
    }

    private function applySlaMetadata(): void
    {
        /** @var TicketSlaEvaluator $evaluator */
        $evaluator = App::make(TicketSlaEvaluator::class);

        $metadata = $this->metadata ?? [];
        $metadata['sla'] = $evaluator->evaluate($this);

        $this->metadata = $metadata;
    }
}
