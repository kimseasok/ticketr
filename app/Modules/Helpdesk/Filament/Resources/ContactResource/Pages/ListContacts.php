<?php

namespace App\Modules\Helpdesk\Filament\Resources\ContactResource\Pages;

use App\Modules\Helpdesk\Filament\Resources\ContactResource;
use Filament\Resources\Pages\ListRecords;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;
}
