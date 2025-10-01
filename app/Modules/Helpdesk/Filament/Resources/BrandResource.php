<?php

namespace App\Modules\Helpdesk\Filament\Resources;

use App\Modules\Helpdesk\Filament\Resources\BrandResource\Pages;
use App\Modules\Helpdesk\Models\Brand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $navigationGroup = 'Helpdesk';

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Brand Details')
                ->schema([
                    Forms\Components\Select::make('tenant_id')
                        ->relationship('tenant', 'name')
                        ->required(),
                    Forms\Components\TextInput::make('name')->required()->maxLength(120),
                    Forms\Components\TextInput::make('slug')->required()->alphaDash()->maxLength(120),
                    Forms\Components\TextInput::make('domain')->url()->maxLength(255)->nullable(),
                    Forms\Components\TextInput::make('portal_domain')->url()->maxLength(255)->nullable(),
                ])->columns(2),
            Forms\Components\Section::make('Theme')
                ->schema([
                    Forms\Components\ColorPicker::make('primary_color')->label('Primary colour')->nullable(),
                    Forms\Components\ColorPicker::make('secondary_color')->label('Secondary colour')->nullable(),
                    Forms\Components\ColorPicker::make('accent_color')->label('Accent colour')->nullable(),
                    Forms\Components\TextInput::make('logo_url')->label('Logo URL')->url()->maxLength(255)->nullable(),
                    Forms\Components\KeyValue::make('metadata')
                        ->label('Metadata')
                        ->keyLabel('Key')
                        ->valueLabel('Value')
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->badge(),
                Tables\Columns\TextColumn::make('portal_domain')->label('Portal domain')->toggleable(),
                Tables\Columns\TextColumn::make('primary_color')->badge()->label('Primary'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'view' => Pages\ViewBrand::route('/{record}'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }
}
