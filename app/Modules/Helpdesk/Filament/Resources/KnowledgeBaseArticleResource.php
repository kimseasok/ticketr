<?php

namespace App\Modules\Helpdesk\Filament\Resources;

use App\Modules\Helpdesk\Filament\Resources\KnowledgeBaseArticleResource\Pages;
use App\Modules\Helpdesk\Models\KnowledgeBaseArticle;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class KnowledgeBaseArticleResource extends Resource
{
    protected static ?string $model = KnowledgeBaseArticle::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Helpdesk';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('tenant_id')
                ->relationship('tenant', 'name')
                ->required(),
            Forms\Components\Select::make('brand_id')
                ->relationship('brand', 'name')
                ->searchable(),
            Forms\Components\Select::make('category_id')
                ->relationship('category', 'name')
                ->searchable(),
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('slug')
                ->required()
                ->maxLength(255),
            Forms\Components\RichEditor::make('content')->columnSpanFull(),
            Forms\Components\Select::make('status')
                ->options([
                    KnowledgeBaseArticle::STATUS_DRAFT => 'Draft',
                    KnowledgeBaseArticle::STATUS_PUBLISHED => 'Published',
                    KnowledgeBaseArticle::STATUS_ARCHIVED => 'Archived',
                ])->default(KnowledgeBaseArticle::STATUS_DRAFT)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('category.name')->label('Category'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('brand.name')->label('Brand'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    KnowledgeBaseArticle::STATUS_DRAFT => 'Draft',
                    KnowledgeBaseArticle::STATUS_PUBLISHED => 'Published',
                    KnowledgeBaseArticle::STATUS_ARCHIVED => 'Archived',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKnowledgeBaseArticles::route('/'),
            'create' => Pages\CreateKnowledgeBaseArticle::route('/create'),
            'view' => Pages\ViewKnowledgeBaseArticle::route('/{record}'),
            'edit' => Pages\EditKnowledgeBaseArticle::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();
        if (! $user) {
            return $query;
        }

        return $query
            ->when($user->tenant_id, fn (Builder $builder) => $builder->where('tenant_id', $user->tenant_id))
            ->when($user->brand_id, fn (Builder $builder) => $builder->where('brand_id', $user->brand_id));
    }
}
