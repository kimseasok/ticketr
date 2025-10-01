<?php

namespace App\Modules\Helpdesk\Filament\Resources;

use App\Modules\Helpdesk\Filament\Resources\TicketMacroResource\Pages;
use App\Modules\Helpdesk\Models\TicketMacro;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TicketMacroResource extends Resource
{
    protected static ?string $model = TicketMacro::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Ticketing';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(150),
            Forms\Components\TextInput::make('slug')
                ->required()
                ->maxLength(150)
                ->unique(ignoreRecord: true),
            Forms\Components\Textarea::make('description')
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\Textarea::make('body')
                ->required()
                ->columnSpanFull(),
            Forms\Components\Select::make('visibility')
                ->required()
                ->options([
                    'tenant' => 'Tenant',
                    'brand' => 'Brand',
                    'private' => 'Private',
                ]),
            Forms\Components\Select::make('brand_id')
                ->relationship('brand', 'name')
                ->searchable()
                ->preload()
                ->label('Brand')
                ->visible(fn (callable $get) => $get('visibility') !== 'private'),
            Forms\Components\Toggle::make('is_shared')
                ->label('Shared with team')
                ->default(true),
            Forms\Components\KeyValue::make('metadata')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('visibility')->badge(),
                Tables\Columns\IconColumn::make('is_shared')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('visibility')
                    ->options([
                        'tenant' => 'Tenant',
                        'brand' => 'Brand',
                        'private' => 'Private',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTicketMacros::route('/'),
            'create' => Pages\CreateTicketMacro::route('/create'),
            'edit' => Pages\EditTicketMacro::route('/{record}/edit'),
        ];
    }
}
