<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Enums\RoleType;
use Filament\Forms\Form;
use App\Models\Warehouse;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $tenantOwnershipRelationshipName = 'fields';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Admin';

    protected static bool $isScopedToTenant = false;

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $slug = 'usuarios';
    
    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->unique(user::class, 'name', ignoreRecord: true)
                    ->placeholder('Nombre del usuario')
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->required()
                    ->unique(user::class, 'email', ignoreRecord: true)
                    ->placeholder('Email del usuario')
                    ->email(),
                Forms\Components\TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->placeholder('Contraseña del usuario')
                    ->required(fn($record) => $record === null)
                    ->visible(fn($record) => $record === null),
                forms\Components\Select::make('fields')
                    ->label('Campos')
                    ->searchable()
                    ->multiple()
                    ->required()
                    ->relationship('fields', 'name')
                    ->preload(),
                Forms\Components\Select::make('role')
                    ->label('Rol')
                    ->preload()
                    ->options(RoleType::class)
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('warehouses')
                    ->label('Bodegas asignadas')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->relationship('warehouses', 'name')
                    ->options(function (callable $get) {
                        // Obtenemos el array de field_ids seleccionados
                        $fieldIds = $get('fields') ?? [];


                        if (empty($fieldIds)) {
                            return [];
                        }

                        // Obtenemos las bodegas que pertenecen a los campos seleccionados
                        $warehouses = Warehouse::whereIn('field_id', $fieldIds)
                            ->where('is_special', false)
                            ->with('field')
                            ->get();

                        // Agrupamos las bodegas por el nombre del campo
                        $grouped = $warehouses->groupBy(fn($warehouse) => $warehouse->field->name);

                        // Convertimos la colección en el array esperado para las opciones agrupadas
                        $options = $grouped->mapWithKeys(function ($group, $fieldName) {
                            return [$fieldName => $group->pluck('name', 'id')->toArray()];
                        })->toArray();

                        return $options;
                    })
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->label('Nombre'),
                Tables\Columns\TextColumn::make('email')
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-o-clipboard-document-list')
                    ->searchable()
                    ->label('Email'),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->date('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\SelectColumn::make('role')
                    ->label('Rol')
                    ->options(RoleType::class)
                    ->sortable(),
                Tables\Columns\TextColumn::make('fields.name')
                    ->label('Campos')
                    ->badge()
                    ->separator(',')
                    ->sortable()
                    ->colors([
                        1 => 'success',
                        2 => 'danger',
                    ])
                    ->searchable(),
                Tables\Columns\TextColumn::make('warehouses.name')
                    ->label('Bodegas asignadas')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limitList(10)
                    ->expandableLimitedList()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('updated_at')

                    ->sortable()
                    ->date('d/m/Y H:i')
                    ->label('Modificado el'),
            ])
            ->filters([

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('change_password')
                ->label('Contraseña')
                ->icon('heroicon-o-key')
                ->color('secondary')
                ->form([
                    Forms\Components\TextInput::make('new_password')
                        ->label('Nueva Contraseña')
                        ->password()
                        ->required()
                        ->minLength(8),
                    Forms\Components\TextInput::make('new_password_confirmation')
                        ->label('Confirmar Nueva Contraseña')
                        ->password()
                        ->required()
                        ->same('new_password'),
                ])
                ->action(function (User $record, array $data) {
                    $record->password = Hash::make($data['new_password']);
                    $record->save();
                    Notification::make()
                        ->success()
                        ->title('Contraseña cambiada')
                        ->body('La contraseña para ' . $record->name . ' ha sido actualizada.')
                        ->send();
                }),
                Action::make('toggle_active')
                ->label(fn ($record) => $record->is_active ? 'Desactivar' : 'Activar')
                ->icon(fn ($record) => $record->is_active ? 'heroicon-o-user-minus' : 'heroicon-o-user-plus')
                ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                ->action(function (User $record) {
                    $record->is_active = !$record->is_active;
                    $record->save();
                    Notification::make()
                        ->success()
                        ->title($record->is_active ? 'Usuario activado' : 'Usuario desactivado')
                        ->body('El estado del usuario ' . $record->name . ' ha sido actualizado.')
                        ->send();
                }),
            ])
            ->bulkActions([

            ]);
    }

    public static function getRelations(): array
    {
        return [
            AuditsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
