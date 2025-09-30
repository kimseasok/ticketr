<?php

namespace App\Modules\Helpdesk\Filament\Resources\TicketResource\Pages;

use App\Modules\Helpdesk\Filament\Resources\TicketResource;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;
}
