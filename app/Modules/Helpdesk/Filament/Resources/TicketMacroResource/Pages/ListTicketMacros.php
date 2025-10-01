<?php

namespace App\Modules\Helpdesk\Filament\Resources\TicketMacroResource\Pages;

use App\Modules\Helpdesk\Filament\Resources\TicketMacroResource;
use Filament\Resources\Pages\ListRecords;

class ListTicketMacros extends ListRecords
{
    protected static string $resource = TicketMacroResource::class;
}
