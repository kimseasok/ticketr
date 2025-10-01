<?php

namespace App\Modules\Helpdesk\Filament\Resources\TicketMacroResource\Pages;

use App\Modules\Helpdesk\Filament\Resources\TicketMacroResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTicketMacro extends CreateRecord
{
    protected static string $resource = TicketMacroResource::class;
}
