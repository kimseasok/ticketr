<?php

namespace App\Modules\Helpdesk\Filament\Resources\KnowledgeBaseArticleResource\Pages;

use App\Modules\Helpdesk\Filament\Resources\KnowledgeBaseArticleResource;
use Filament\Resources\Pages\ListRecords;

class ListKnowledgeBaseArticles extends ListRecords
{
    protected static string $resource = KnowledgeBaseArticleResource::class;
}
