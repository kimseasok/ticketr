<?php

namespace App\Modules\Helpdesk\Filament\Resources;

use App\Modules\Helpdesk\Filament\Resources\TicketMessageResource\Pages;
use App\Modules\Helpdesk\Models\TicketMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TicketMessageResource extends Resource
{
    protected static ?string $model = TicketMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

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
            Forms\Components\Select::make('ticket_id')
                ->relationship('ticket', 'reference')
                ->required(),
            Forms\Components\Select::make('visibility')
                ->options([
                    'public' => 'Public',
                    'internal' => 'Internal',
                ])
                ->required(),
            Forms\Components\Select::make('channel')
                ->options([
                    'email' => 'Email',
                    'web' => 'Web',
                    'chat' => 'Chat',
                    'phone' => 'Phone',
                ])
                ->required(),
            Forms\Components\Textarea::make('body')
                ->rows(6)
                ->required(),
            Forms\Components\KeyValue::make('metadata')
                ->label('Metadata')
                ->keyLabel('Key')
                ->valueLabel('Value')
                ->columnSpanFull(),
            Forms\Components\DateTimePicker::make('posted_at')
                ->seconds(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket.reference')
                    ->label('Ticket')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('visibility')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('channel')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('attachments_count')
                    ->label('Attachments')
                    ->sortable(),
                Tables\Columns\TextColumn::make('posted_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('visibility')
                    ->options([
                        'public' => 'Public',
                        'internal' => 'Internal',
                    ]),
                Tables\Filters\SelectFilter::make('channel')
                    ->options([
                        'email' => 'Email',
                        'web' => 'Web',
                        'chat' => 'Chat',
                        'phone' => 'Phone',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('posted_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTicketMessages::route('/'),
            'create' => Pages\CreateTicketMessage::route('/create'),
            'view' => Pages\ViewTicketMessage::route('/{record}'),
            'edit' => Pages\EditTicketMessage::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('ticket');
        $user = auth()->user();

        if (! $user) {
            return $query;
        }

        return $query
            ->when($user->tenant_id, fn (Builder $builder) => $builder->where('tenant_id', $user->tenant_id))
            ->when($user->brand_id, fn (Builder $builder) => $builder->where('brand_id', $user->brand_id))
            ->visibleTo($user);
    }
}
