<?php

namespace App\Modules\Helpdesk\Filament\Resources\EmailOutboundMessageResource\Pages;

use App\Modules\Helpdesk\Filament\Resources\EmailOutboundMessageResource;
use Filament\Resources\Pages\ListRecords;

class ListEmailOutboundMessages extends ListRecords
{
    protected static string $resource = EmailOutboundMessageResource::class;
}
