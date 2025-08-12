<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Program;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Exports\ProgramExport;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProgramResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProgramResource\RelationManagers;
use Illuminate\Validation\Rule;

class ProgramResource extends Resource
{
    protected static ?string $model = Program::class;

    protected static ?string $navigationIcon = 'carbon-data-table';
    protected static ?string $navigationGroup = 'Aplicaciones';
    protected static ?string $navigationLabel = 'Programas de fertilización';
    protected static ?string $pluralModelLabel = 'Programas';
    protected static ?string $slug = 'programas';
    protected static ?string $modelLabel = 'programa';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->rules(function (Forms\Get $get) {
                        return [
                            Rule::unique('programs', 'name')
                                ->ignore($get('id'))
                                ->where(function (\Illuminate\Database\Query\Builder $query) {
                                    return $query->where('field_id', Filament::getTenant()->id);
                                }),
                        ];
                    })
                    ->label('Nombre del Programa')
                    ->maxLength(255),
                Forms\Components\Select::make('crop_id')
                    ->relationship('crop', 'especie')
                    ->label('Cultivo')
                    ->required(),
                Forms\Components\DatePicker::make('start_date')
                    ->label('Fecha de Inicio')
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->label('Fecha de Fin')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('Descripción')
                    ->nullable()
                    ->maxLength(65535),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre del Programa')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha de Inicio')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fecha de Fin')
                    ->date('d/m/Y')
                    ->sortable(),
                
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label('Eliminados'),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Exportar a Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->action(function () {
                        // 1. Obtenemos el objeto tenant completo
                        $tenant = Filament::getTenant();

                        // 2. Extraemos el ID y el nombre en variables separadas
                        $tenantId = $tenant->id;
                        $tenantName = $tenant->name;
                        
                        // 3. Usamos la variable correcta ($tenantName) para el nombre del archivo
                        $filename = Carbon::today()->format('Y-m-d') . " - {$tenantName} - Programas.xlsx";

                        // 4. Pasamos el ID del tenant ($tenantId) al constructor del exportador
                        return Excel::download(new ProgramExport($tenantId), $filename);
                    })
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->color('warning')
                        ->visible(fn (Program $record) => $record->is_active),
                    Action::make('export_record')
                        ->label('Exportar')
                        ->color('info')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function (Program $record) {
                            $programId = $record->id;
                            $tenantId = $record->field_id;
                            $filename = "programa-{$record->name}.xlsx";
                            return Excel::download(new ProgramExport($tenantId, $programId), $filename);
                        }),
                    Action::make('download_pdf')
                        ->label('Pdf Programa')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->url(fn (Program $record): string => route('program.downloadPdf', $record))
                        ->openUrlInNewTab(),
                    Action::make('deactivate')
                        ->label('Cerrar Programa')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (Program $record) {
                            $record->update(['is_active' => false]);
                            Filament::notify('success', 'Programa desactivado correctamente.');
                        })
                        ->requiresConfirmation(),
                ])
            ])
            ->bulkActions([
                
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    public static function getRelations(): array
    {
        return [
            RelationManagers\FertilizerRelationManager::class,
            RelationManagers\ParcelsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrograms::route('/'),
            'create' => Pages\CreateProgram::route('/create'),
            'view' => Pages\ViewProgram::route('/{record}'),
            'edit' => Pages\EditProgram::route('/{record}/edit'),
        ];
    }
}
