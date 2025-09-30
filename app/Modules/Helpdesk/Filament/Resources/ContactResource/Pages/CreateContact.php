<?php

namespace App\Modules\Helpdesk\Filament\Resources\ContactResource\Pages;

use App\Modules\Helpdesk\Filament\Resources\ContactResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContact extends CreateRecord
{
    protected static string $resource = ContactResource::class;
}
