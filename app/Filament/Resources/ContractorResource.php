<?php

namespace App\Filament\Resources;

use App\Models\Contractor;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class ContractorResource extends Resource
{
    protected static ?string $model = Contractor::class;
    protected static bool $isScopedToTenant = false;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Anexos';
    protected static ?string $modelLabel = 'Contratista';
    protected static ?string $pluralModelLabel = 'Contratistas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),
            TextInput::make('rut')
                ->label('RUT')
                ->maxLength(50),
            TextInput::make('phone')
                ->label('Teléfono')
                ->maxLength(50),
            TextInput::make('email')
                ->email()
                ->label('Email')
                ->maxLength(255),
            Textarea::make('notes')
                ->label('Notas')
                ->columnSpanFull(),
            Toggle::make('is_active')
                ->label('Activo')
                ->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rut')
                    ->label('RUT')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Teléfono'),
                TextColumn::make('email')
                    ->label('Email'),
                TextColumn::make('is_active')
                    ->label('Activo')
                    ->badge()
                    ->formatStateUsing(fn ($s) => $s ? 'Sí' : 'No')
                    ->color(fn ($s) => $s ? 'success' : 'gray'),
                TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updatedBy.name')
                    ->label('Actualizado por')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->visible(),
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Activo')
                    ->default('true')
                    ->options([
                        true => 'Sí',
                        false => 'No',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make()
                    ->visible(fn ($record) => $record->trashed()),
            ])
            ->bulkActions([

            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ContractorResource\Pages\ListContractors::route('/'),
            'create' => ContractorResource\Pages\CreateContractor::route('/create'),
            'edit' => ContractorResource\Pages\EditContractor::route('/{record}/edit'),
        ];
    }
}
