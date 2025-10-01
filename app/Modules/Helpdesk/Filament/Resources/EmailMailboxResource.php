<?php

namespace App\Modules\Helpdesk\Filament\Resources;

use App\Modules\Helpdesk\Filament\Resources\EmailMailboxResource\Pages;
use App\Modules\Helpdesk\Models\EmailMailbox;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EmailMailboxResource extends Resource
{
    protected static ?string $model = EmailMailbox::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope-open';

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
            Forms\Components\Select::make('direction')
                ->required()
                ->options([
                    'inbound' => 'Inbound',
                    'outbound' => 'Outbound',
                    'bidirectional' => 'Bidirectional',
                ]),
            Forms\Components\Select::make('protocol')
                ->required()
                ->options([
                    'imap' => 'IMAP',
                    'smtp' => 'SMTP',
                ]),
            Forms\Components\TextInput::make('host')
                ->required()
                ->maxLength(191),
            Forms\Components\TextInput::make('port')
                ->numeric()
                ->required(),
            Forms\Components\Select::make('encryption')
                ->options([
                    'ssl' => 'SSL',
                    'tls' => 'TLS',
                    'starttls' => 'STARTTLS',
                    'none' => 'None',
                ])->default('ssl'),
            Forms\Components\TextInput::make('username')
                ->required()
                ->maxLength(191),
            Forms\Components\Fieldset::make('Credentials')
                ->schema([
                    Forms\Components\TextInput::make('credentials.password')
                        ->password()
                        ->revealable()
                        ->maxLength(191)
                        ->dehydrateStateUsing(fn ($state) => $state === '' ? null : $state)
                        ->required(fn (Forms\Get $get, ?Model $record) => $record === null),
                    Forms\Components\TextInput::make('credentials.client_secret')
                        ->password()
                        ->revealable()
                        ->maxLength(191)
                        ->dehydrateStateUsing(fn ($state) => $state === '' ? null : $state),
                ])->columns(2),
            Forms\Components\TextInput::make('settings.folder')
                ->label('Folder')
                ->maxLength(120),
            Forms\Components\TextInput::make('settings.mailer')
                ->label('Mailer')
                ->maxLength(120),
            Forms\Components\Select::make('brand_id')
                ->relationship('brand', 'name')
                ->searchable()
                ->preload()
                ->label('Brand'),
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
                Tables\Columns\TextColumn::make('direction')->badge(),
                Tables\Columns\TextColumn::make('protocol')->badge(),
                Tables\Columns\TextColumn::make('host'),
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

    public static function prepareCredentials(array $data, ?EmailMailbox $record = null): array
    {
        $credentials = Arr::get($data, 'credentials', []);
        $credentials = array_filter($credentials, fn ($value) => $value !== null && $value !== '');

        if ($record) {
            $existing = $record->credentials ?? [];
            $credentials = array_merge($existing, $credentials);
        }

        if ($credentials !== []) {
            $data['credentials'] = $credentials;
        } elseif ($record) {
            $data['credentials'] = $record->credentials;
        }

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailMailboxes::route('/'),
            'create' => Pages\CreateEmailMailbox::route('/create'),
            'edit' => Pages\EditEmailMailbox::route('/{record}/edit'),
        ];
    }
}
