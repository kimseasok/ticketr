<?php

namespace App\Modules\Helpdesk\Filament\Resources\EmailMailboxResource\Pages;

use App\Modules\Helpdesk\Filament\Resources\EmailMailboxResource;
use Filament\Resources\Pages\EditRecord;

class EditEmailMailbox extends EditRecord
{
    protected static string $resource = EmailMailboxResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return EmailMailboxResource::prepareCredentials($data, $this->record);
    }
}
