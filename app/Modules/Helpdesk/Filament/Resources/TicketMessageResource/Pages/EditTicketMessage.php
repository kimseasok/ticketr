<?php

namespace App\Modules\Helpdesk\Filament\Resources\TicketMessageResource\Pages;

use App\Modules\Helpdesk\Filament\Resources\TicketMessageResource;
use Filament\Resources\Pages\EditRecord;

class EditTicketMessage extends EditRecord
{
    protected static string $resource = TicketMessageResource::class;
}
