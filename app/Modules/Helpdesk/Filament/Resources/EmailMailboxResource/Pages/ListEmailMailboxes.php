<?php

namespace App\Modules\Helpdesk\Filament\Resources\EmailMailboxResource\Pages;

use App\Modules\Helpdesk\Filament\Resources\EmailMailboxResource;
use Filament\Resources\Pages\ListRecords;

class ListEmailMailboxes extends ListRecords
{
    protected static string $resource = EmailMailboxResource::class;
}
