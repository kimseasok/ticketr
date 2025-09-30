<?php

namespace App\Modules\Helpdesk\Filament\Resources\TicketResource\Pages;

use App\Modules\Helpdesk\Filament\Resources\TicketResource;
use Filament\Resources\Pages\EditRecord;

/**
 * @property \App\Modules\Helpdesk\Models\Ticket $record
 */
class EditTicket extends EditRecord
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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing(['categories', 'tags']);

        $data['category_ids'] = $this->record->categories->pluck('id')->all();
        $data['tag_ids'] = $this->record->tags->pluck('id')->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->categoryIds = $data['category_ids'] ?? [];
        $this->tagIds = $data['tag_ids'] ?? [];

        unset($data['category_ids'], $data['tag_ids']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->syncCategories($this->categoryIds, auth()->id());
        $this->record->syncTags($this->tagIds, auth()->id());
    }
}
