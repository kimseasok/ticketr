<?php

namespace App\Modules\Helpdesk\Filament\Resources;

use App\Modules\Helpdesk\Filament\Resources\SlaPolicyResource\Pages;
use App\Modules\Helpdesk\Models\SlaPolicy;
use App\Support\Tenancy\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SlaPolicyResource extends Resource
{
    protected static ?string $model = SlaPolicy::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Automation';

    protected static ?string $navigationLabel = 'SLA Policies';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(150),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(150),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\Select::make('brand_id')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Brand Scope'),
            ]),
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\Select::make('priority_scope')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ])
                    ->label('Priority Scope')
                    ->placeholder('All priorities'),
                Forms\Components\Select::make('channel_scope')
                    ->options([
                        'email' => 'Email',
                        'web' => 'Web',
                        'chat' => 'Chat',
                        'phone' => 'Phone',
                    ])
                    ->label('Channel Scope')
                    ->placeholder('All channels'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]),
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\TextInput::make('first_response_minutes')
                    ->numeric()
                    ->required()
                    ->minValue(5)
                    ->maxValue(10080),
                Forms\Components\TextInput::make('resolution_minutes')
                    ->numeric()
                    ->required()
                    ->minValue(5)
                    ->maxValue(10080),
                Forms\Components\TextInput::make('grace_minutes')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(1440),
            ]),
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('alert_after_minutes')
                    ->numeric()
                    ->default(30)
                    ->minValue(5)
                    ->maxValue(10080),
                Forms\Components\Select::make('escalation_user_id')
                    ->label('Escalation Owner')
                    ->options(function () {
                        $tenantId = app(TenantContext::class)->getTenantId();

                        return \App\Models\User::query()
                            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->searchable(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('priority_scope')->label('Priority')->badge(),
                Tables\Columns\TextColumn::make('channel_scope')->label('Channel')->badge(),
                Tables\Columns\TextColumn::make('first_response_minutes')->label('First Response (min)'),
                Tables\Columns\TextColumn::make('resolution_minutes')->label('Resolution (min)'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSlaPolicies::route('/'),
            'create' => Pages\CreateSlaPolicy::route('/create'),
            'edit' => Pages\EditSlaPolicy::route('/{record}/edit'),
        ];
    }
}
