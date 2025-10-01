<?php

namespace App\Modules\Helpdesk\Filament\Resources;

use App\Modules\Helpdesk\Filament\Resources\EmailInboundMessageResource\Pages;
use App\Modules\Helpdesk\Models\EmailInboundMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmailInboundMessageResource extends Resource
{
    protected static ?string $model = EmailInboundMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox';

    protected static ?string $navigationGroup = 'Ticketing';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('subject')->disabled(),
            Forms\Components\TextInput::make('from_email')->disabled()->label('From'),
            Forms\Components\TextInput::make('status')->disabled(),
            Forms\Components\Textarea::make('text_body')->disabled()->rows(6),
            Forms\Components\KeyValue::make('headers')->disabled(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subject')->searchable(),
                Tables\Columns\TextColumn::make('from_email')->label('From'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('received_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processed' => 'Processed',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailInboundMessages::route('/'),
            'view' => Pages\ViewEmailInboundMessage::route('/{record}'),
        ];
    }
}
