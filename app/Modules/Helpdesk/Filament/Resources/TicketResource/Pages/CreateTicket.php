<?php

namespace App\Modules\Helpdesk\Filament\Resources\TicketResource\Pages;

use App\Modules\Helpdesk\Filament\Resources\TicketResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;
}
