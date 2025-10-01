<?php

namespace App\Modules\Helpdesk\Filament\Resources\EmailMailboxResource\Pages;

use App\Modules\Helpdesk\Filament\Resources\EmailMailboxResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmailMailbox extends CreateRecord
{
    protected static string $resource = EmailMailboxResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return EmailMailboxResource::prepareCredentials($data);
    }
}
