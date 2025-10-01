<?php

namespace App\Modules\Helpdesk\Filament\Resources\TicketMessageResource\Pages;

use App\Modules\Helpdesk\Filament\Resources\TicketMessageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTicketMessage extends CreateRecord
{
    protected static string $resource = TicketMessageResource::class;
}
