<?php

namespace App\Modules\Helpdesk\Filament\Resources;

use App\Modules\Helpdesk\Filament\Resources\TicketResource\Pages;
use App\Modules\Helpdesk\Models\Ticket;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-life-buoy';

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
            Forms\Components\Select::make('contact_id')
                ->relationship('contact', 'name')
                ->searchable(),
            Forms\Components\Select::make('company_id')
                ->relationship('company', 'name')
                ->searchable(),
            Forms\Components\Select::make('created_by')
                ->relationship('creator', 'name')
                ->searchable(),
            Forms\Components\Select::make('assigned_to')
                ->relationship('assignee', 'name')
                ->searchable(),
            Forms\Components\TextInput::make('subject')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('description'),
            Forms\Components\Select::make('status')
                ->options([
                    Ticket::STATUS_OPEN => 'Open',
                    Ticket::STATUS_PENDING => 'Pending',
                    Ticket::STATUS_RESOLVED => 'Resolved',
                    Ticket::STATUS_CLOSED => 'Closed',
                ])->default(Ticket::STATUS_OPEN)
                ->required(),
            Forms\Components\Select::make('priority')
                ->options([
                    'low' => 'Low',
                    'normal' => 'Normal',
                    'high' => 'High',
                    'urgent' => 'Urgent',
                ])->default('normal')
                ->required(),
            Forms\Components\TextInput::make('reference')
                ->required()
                ->maxLength(50),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')->searchable(),
                Tables\Columns\TextColumn::make('subject')->searchable(),
                Tables\Columns\TextColumn::make('contact.name')->label('Contact')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('priority')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Ticket::STATUS_OPEN => 'Open',
                        Ticket::STATUS_PENDING => 'Pending',
                        Ticket::STATUS_RESOLVED => 'Resolved',
                        Ticket::STATUS_CLOSED => 'Closed',
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
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'view' => Pages\ViewTicket::route('/{record}'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
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
