<?php

namespace App\Modules\Helpdesk\Filament\Resources\TicketMessageResource\Pages;

use App\Modules\Helpdesk\Filament\Resources\TicketMessageResource;
use Filament\Resources\Pages\ListRecords;

class ListTicketMessages extends ListRecords
{
    protected static string $resource = TicketMessageResource::class;
}
