<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Helpdesk\Models\Attachment */
class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'metadata' => $this->metadata,
        ];
    }
}
