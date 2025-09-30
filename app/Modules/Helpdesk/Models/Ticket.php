<?php

namespace App\Modules\Helpdesk\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use App\Modules\Helpdesk\Events\TicketStatusChanged;
use App\Modules\Helpdesk\Models\TicketCategory;
use App\Modules\Helpdesk\Models\TicketTag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

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

        static::updating(function (self $ticket): void {
            if ($ticket->isDirty('status')) {
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
        return $this->hasMany(Message::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function scopeForBrand(Builder $builder, int $brandId): Builder
    {
        return $builder->where($builder->qualifyColumn('brand_id'), $brandId);
    }

    public function auditPayload(): array
    {
        return Arr::only($this->getAttributes(), [
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
}
