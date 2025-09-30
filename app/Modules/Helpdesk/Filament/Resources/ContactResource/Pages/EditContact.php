<?php

namespace App\Modules\Helpdesk\Filament\Resources\ContactResource\Pages;

use App\Modules\Helpdesk\Filament\Resources\ContactResource;
use Filament\Resources\Pages\EditRecord;

class EditContact extends EditRecord
{
    protected static string $resource = ContactResource::class;
}
