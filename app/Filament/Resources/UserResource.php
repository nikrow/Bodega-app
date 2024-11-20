<?php

namespace App\Filament\Resources;

use App\Enums\RoleType;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Field;
use App\Models\Role;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $tenantOwnershipRelationshipName = 'fields';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Anexos';
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
                    ->rules('required', 'max:255'),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->required()
                    ->unique(user::class, 'email', ignoreRecord: true)
                    ->placeholder('Email del usuario')
                    ->rules('required', 'email', 'max:255'),
                Forms\Components\TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->hiddenOn('edit')
                    ->placeholder('Contraseña del usuario')
                    ->rules('required', 'min:8'),
                forms\Components\Select::make('fields')
                    ->label('Campos')
                    ->searchable()
                    ->multiple()
                    ->relationship('fields', 'name')
                    ->preload(),
                Forms\Components\Select::make('role')
                    ->label('Rol')
                    ->preload()
                    ->options([
                        RoleType::ADMIN->value => 'admin',
                        RoleType::AGRONOMO->value => 'agronomo',
                        RoleType::USUARIO->value => 'usuario',
                        RoleType::BODEGUERO->value => 'bodeguero',
                        RoleType::ASISTENTE->value => 'asistente',
                        RoleType::ESTANQUERO->value => 'estanquero',
                    ])
                    ->searchable()
                    ->required()

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->label('Nombre'),
                Tables\Columns\TextColumn::make('email')
                    ->sortable()
                    ->label('Email'),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->date('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->date('d/m/Y H:i')
                    ->label('Modificado el'),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->label('Rol')
                    ->formatStateUsing(function ($state) {
                        return match($state){
                            RoleType::ADMIN->value => 'admin',
                            RoleType::AGRONOMO->value => 'agronomo',
                            RoleType::USUARIO->value => 'usuario',
                            RoleType::BODEGUERO->value => 'bodeguero',
                            RoleType::ASISTENTE->value => 'asistente',
                            RoleType::ESTANQUERO->value => 'estanquero',
                            default => 'Ninguno',
                        };

                    })
                    ->colors([
                        RoleType::ADMIN->value => 'danger',
                        RoleType::AGRONOMO->value => 'warning',
                        RoleType::USUARIO->value => 'warning',
                        RoleType::BODEGUERO->value => 'warning',
                        RoleType::ASISTENTE->value => 'warning',
                        RoleType::ESTANQUERO->value => 'warning',
                        ])
                    ->sortable(),
            ])
            ->filters([

            ])
            ->actions([
                ActivityLogTimelineTableAction::make('Actividades'),
                Tables\Actions\EditAction::make(),
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
