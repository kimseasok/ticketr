<?php

namespace App\Modules\Helpdesk\Filament\Resources;

use App\Modules\Helpdesk\Filament\Resources\TicketResource\Pages;
use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Models\TicketCategory;
use App\Modules\Helpdesk\Models\TicketPriority;
use App\Modules\Helpdesk\Models\TicketStatus;
use App\Modules\Helpdesk\Models\TicketTag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

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
                ->label('Status')
                ->options(fn () => TicketStatus::query()->orderBy('sort_order')->pluck('name', 'slug')->all())
                ->default(fn () => TicketStatus::query()->where('is_default', true)->orderBy('sort_order')->value('slug') ?? Ticket::STATUS_OPEN)
                ->required(),
            Forms\Components\Select::make('priority')
                ->label('Priority')
                ->options(fn () => TicketPriority::query()->orderBy('sort_order')->pluck('name', 'slug')->all())
                ->default(fn () => TicketPriority::query()->where('is_default', true)->orderBy('sort_order')->value('slug') ?? 'normal')
                ->required(),
            Forms\Components\Select::make('channel')
                ->options([
                    'email' => 'Email',
                    'web' => 'Web',
                    'chat' => 'Chat',
                    'phone' => 'Phone',
                ])
                ->default('email')
                ->required(),
            Forms\Components\TextInput::make('reference')
                ->default(fn () => sprintf('T-%s', Str::upper(Str::random(8))))
                ->disabled()
                ->dehydrated(false)
                ->maxLength(50)
                ->helperText('Automatically generated on create.'),
            Forms\Components\DateTimePicker::make('first_response_due_at')
                ->seconds(false),
            Forms\Components\DateTimePicker::make('resolution_due_at')
                ->seconds(false),
            Forms\Components\DateTimePicker::make('resolved_at')
                ->seconds(false),
            Forms\Components\DateTimePicker::make('closed_at')
                ->seconds(false),
            Forms\Components\MultiSelect::make('category_ids')
                ->label('Categories')
                ->options(fn () => TicketCategory::query()->orderBy('name')->pluck('name', 'id')->all())
                ->default([])
                ->preload(),
            Forms\Components\MultiSelect::make('tag_ids')
                ->label('Tags')
                ->options(fn () => TicketTag::query()->orderBy('name')->pluck('name', 'id')->all())
                ->default([])
                ->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')->searchable(),
                Tables\Columns\TextColumn::make('subject')->searchable(),
                Tables\Columns\TextColumn::make('contact.name')->label('Contact')->sortable(),
                Tables\Columns\TextColumn::make('statusDefinition.name')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('priorityDefinition.name')
                    ->label('Priority')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('channel')->badge(),
                Tables\Columns\TextColumn::make('resolution_due_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('last_activity_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(fn () => TicketStatus::query()->orderBy('sort_order')->pluck('name', 'slug')->all()),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priority')
                    ->options(fn () => TicketPriority::query()->orderBy('sort_order')->pluck('name', 'slug')->all()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('last_activity_at', 'desc');
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
        $query = parent::getEloquentQuery()->with(['statusDefinition', 'priorityDefinition']);

        $user = auth()->user();
        if (! $user) {
            return $query;
        }

        return $query
            ->when($user->tenant_id, fn (Builder $builder) => $builder->where('tenant_id', $user->tenant_id))
            ->when($user->brand_id, fn (Builder $builder) => $builder->where('brand_id', $user->brand_id));
    }
}
