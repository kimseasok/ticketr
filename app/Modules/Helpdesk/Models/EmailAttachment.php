<?php

namespace App\Modules\Helpdesk\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EmailAttachment extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'brand_id',
        'mailbox_id',
        'message_type',
        'message_id',
        'attachment_id',
        'filename',
        'mime_type',
        'size',
        'content_id',
        'disposition',
        'checksum',
        'metadata',
    ];

    protected $casts = [
        'size' => 'integer',
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

    public function mailbox(): BelongsTo
    {
        return $this->belongsTo(EmailMailbox::class, 'mailbox_id');
    }

    public function message(): MorphTo
    {
        return $this->morphTo();
    }

    public function storedAttachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'attachment_id');
    }
}
