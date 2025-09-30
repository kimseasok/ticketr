<?php

namespace App\Modules\Helpdesk\Filament\Resources\TicketResource\Pages;

use App\Modules\Helpdesk\Filament\Resources\TicketResource;
use Filament\Resources\Pages\ListRecords;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;
}
