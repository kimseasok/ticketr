<?php

namespace App\Modules\Helpdesk\Filament\Resources\EmailInboundMessageResource\Pages;

use App\Modules\Helpdesk\Filament\Resources\EmailInboundMessageResource;
use Filament\Resources\Pages\ListRecords;

class ListEmailInboundMessages extends ListRecords
{
    protected static string $resource = EmailInboundMessageResource::class;
}
