<?php

namespace App\Modules\Helpdesk\Filament\Resources\TicketResource\Pages;

use App\Modules\Helpdesk\Filament\Resources\TicketResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

/**
 * @property \App\Modules\Helpdesk\Models\Ticket $record
 */
class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    /**
     * @var array<int,int>
     */
    protected array $categoryIds = [];

    /**
     * @var array<int,int>
     */
    protected array $tagIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->categoryIds = $data['category_ids'] ?? [];
        $this->tagIds = $data['tag_ids'] ?? [];

        unset($data['category_ids'], $data['tag_ids']);

        $data['reference'] = $data['reference'] ?? sprintf('T-%s', Str::upper(Str::random(8)));

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->syncCategories($this->categoryIds, auth()->id());
        $this->record->syncTags($this->tagIds, auth()->id());
    }
}
