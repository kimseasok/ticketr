<?php

namespace App\Modules\Helpdesk\Filament\Resources;

use App\Modules\Helpdesk\Filament\Resources\ChannelAdapterResource\Pages;
use App\Modules\Helpdesk\Models\ChannelAdapter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ChannelAdapterResource extends Resource
{
    protected static ?string $model = ChannelAdapter::class;

    protected static ?string $navigationIcon = 'heroicon-o-plug';

    protected static ?string $navigationGroup = 'Ticketing';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(120),
            Forms\Components\TextInput::make('slug')
                ->required()
                ->maxLength(120)
                ->unique(ignoreRecord: true),
            Forms\Components\Select::make('channel')
                ->required()
                ->options([
                    'email' => 'Email',
                    'web' => 'Web',
                    'chat' => 'Chat',
                    'phone' => 'Phone',
                ]),
            Forms\Components\TextInput::make('provider')
                ->required()
                ->maxLength(120),
            Forms\Components\Select::make('brand_id')
                ->relationship('brand', 'name')
                ->searchable()
                ->preload()
                ->label('Brand'),
            Forms\Components\KeyValue::make('configuration')
                ->label('Configuration')
                ->columnSpanFull(),
            Forms\Components\KeyValue::make('metadata')
                ->label('Metadata')
                ->columnSpanFull(),
            Forms\Components\Toggle::make('is_active')
                ->default(true)
                ->label('Active'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('channel')->badge(),
                Tables\Columns\TextColumn::make('provider'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
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
            'index' => Pages\ListChannelAdapters::route('/'),
            'create' => Pages\CreateChannelAdapter::route('/create'),
            'edit' => Pages\EditChannelAdapter::route('/{record}/edit'),
        ];
    }
}
