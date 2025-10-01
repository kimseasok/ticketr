<?php

namespace App\Modules\Helpdesk\Services;

use App\Modules\Helpdesk\Models\Attachment;
use Illuminate\Support\Facades\Log;

class AttachmentScanner
{
    public function scan(Attachment $attachment): void
    {
        Log::channel('stack')->info('attachment.scan.dispatched', [
            'attachment_id' => $attachment->id,
            'tenant_id' => $attachment->tenant_id,
            'mime_type' => $attachment->mime_type,
            'size' => $attachment->size,
        ]);
    }
}
