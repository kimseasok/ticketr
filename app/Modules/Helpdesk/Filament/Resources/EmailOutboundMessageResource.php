<?php

namespace App\Modules\Helpdesk\Filament\Resources;

use App\Modules\Helpdesk\Filament\Resources\EmailOutboundMessageResource\Pages;
use App\Modules\Helpdesk\Models\EmailOutboundMessage;
use App\Modules\Helpdesk\Services\Email\EmailDeliveryService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmailOutboundMessageResource extends Resource
{
    protected static ?string $model = EmailOutboundMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationGroup = 'Ticketing';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('subject')->disabled(),
            Forms\Components\TextInput::make('status')->disabled(),
            Forms\Components\TextInput::make('attempts')->disabled(),
            Forms\Components\Textarea::make('text_body')->disabled()->rows(6),
            Forms\Components\TextInput::make('provider_message_id')->disabled(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subject')->searchable(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('attempts'),
                Tables\Columns\TextColumn::make('scheduled_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('sent_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'queued' => 'Queued',
                    'sending' => 'Sending',
                    'sent' => 'Sent',
                    'failed' => 'Failed',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('deliver')
                    ->label('Deliver now')
                    ->requiresConfirmation()
                    ->hidden(fn (EmailOutboundMessage $record) => $record->status === 'sent')
                    ->action(function (EmailOutboundMessage $record): void {
                        $result = app(EmailDeliveryService::class)->deliver($record);

                        if ($result->success) {
                            Notification::make()
                                ->title('Email sent')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Delivery failed')
                                ->body($result->error['message'] ?? 'Unable to deliver email.')
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailOutboundMessages::route('/'),
            'view' => Pages\ViewEmailOutboundMessage::route('/{record}'),
        ];
    }
}
