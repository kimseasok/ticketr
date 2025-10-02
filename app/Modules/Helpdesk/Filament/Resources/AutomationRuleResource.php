<?php

namespace App\Modules\Helpdesk\Filament\Resources;

use App\Models\User;
use App\Modules\Helpdesk\Filament\Resources\AutomationRuleResource\Pages;
use App\Modules\Helpdesk\Models\AutomationRule;
use App\Modules\Helpdesk\Models\SlaPolicy;
use App\Modules\Helpdesk\Models\TicketTag;
use App\Support\Tenancy\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AutomationRuleResource extends Resource
{
    protected static ?string $model = AutomationRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';

    protected static ?string $navigationGroup = 'Automation';

    protected static ?string $navigationLabel = 'Rules';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Rule Details')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(150),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(150)
                        ->unique(ignoreRecord: true),
                    Forms\Components\Select::make('event')
                        ->required()
                        ->options([
                            'ticket.created' => 'Ticket Created',
                            'ticket.updated' => 'Ticket Updated',
                            'sla.breached' => 'SLA Breached',
                        ]),
                    Forms\Components\Select::make('match_type')
                        ->required()
                        ->options([
                            'all' => 'All conditions',
                            'any' => 'Any condition',
                        ])->default('all'),
                    Forms\Components\Select::make('brand_id')
                        ->relationship('brand', 'name')
                        ->searchable()
                        ->preload()
                        ->label('Brand Scope'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                    Forms\Components\TextInput::make('run_order')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->label('Execution Order'),
                ]),
            Forms\Components\Section::make('Conditions')
                ->schema([
                    Forms\Components\Repeater::make('conditions')
                        ->schema([
                            Forms\Components\Select::make('field')
                                ->required()
                                ->options([
                                    'priority' => 'Priority',
                                    'status' => 'Status',
                                    'channel' => 'Channel',
                                    'brand_id' => 'Brand',
                                    'sla_policy_id' => 'SLA Policy',
                                ]),
                            Forms\Components\Select::make('operator')
                                ->required()
                                ->options([
                                    'equals' => 'Equals',
                                    'not_equals' => 'Not Equals',
                                    'in' => 'In List',
                                    'not_in' => 'Not In List',
                                    'contains' => 'Contains',
                                ])->default('equals'),
                            Forms\Components\TextInput::make('value')
                                ->required()
                                ->label('Value')
                                ->maxLength(255),
                        ])
                        ->columnSpanFull()
                        ->addActionLabel('Add Condition'),
                ]),
            Forms\Components\Section::make('Actions')
                ->schema([
                    Forms\Components\Repeater::make('actions')
                        ->schema([
                            Forms\Components\Select::make('type')
                                ->required()
                                ->options([
                                    'set_priority' => 'Set Priority',
                                    'set_status' => 'Set Status',
                                    'assign_agent' => 'Assign Agent',
                                    'apply_sla' => 'Apply SLA Policy',
                                    'add_tags' => 'Add Tags',
                                ])->reactive(),
                            Forms\Components\TextInput::make('value')
                                ->label('Value')
                                ->visible(fn (callable $get) => in_array($get('type'), ['set_priority', 'set_status'], true)),
                            Forms\Components\Select::make('user_id')
                                ->label('Agent')
                                ->options(function () {
                                    $tenantId = app(TenantContext::class)->getTenantId();

                                    return User::query()
                                        ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->visible(fn (callable $get) => $get('type') === 'assign_agent'),
                            Forms\Components\Select::make('sla_policy_id')
                                ->label('SLA Policy')
                                ->options(function () {
                                    $tenantId = app(TenantContext::class)->getTenantId();

                                    return SlaPolicy::query()
                                        ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->visible(fn (callable $get) => $get('type') === 'apply_sla'),
                            Forms\Components\Select::make('tag_ids')
                                ->multiple()
                                ->label('Tags')
                                ->options(function () {
                                    $tenantId = app(TenantContext::class)->getTenantId();

                                    return TicketTag::query()
                                        ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                })
                                ->visible(fn (callable $get) => $get('type') === 'add_tags'),
                        ])
                        ->columnSpanFull()
                        ->addActionLabel('Add Action')
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('event')->badge(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('run_order')->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event')->options([
                    'ticket.created' => 'Ticket Created',
                    'ticket.updated' => 'Ticket Updated',
                    'sla.breached' => 'SLA Breached',
                ]),
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
            'index' => Pages\ListAutomationRules::route('/'),
            'create' => Pages\CreateAutomationRule::route('/create'),
            'edit' => Pages\EditAutomationRule::route('/{record}/edit'),
        ];
    }
}
