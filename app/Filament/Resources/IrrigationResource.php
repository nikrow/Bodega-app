<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Irrigation;
use Carbon\CarbonInterval;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\FertilizerMapping;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\IrrigationResource\Pages;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\IrrigationResource\RelationManagers;

class IrrigationResource extends Resource
{
    protected static ?string $model = Irrigation::class;

    protected static ?string $navigationIcon = 'eos-water-drop-o';
    protected static ?string $navigationGroup = 'Fertirriego';
    protected static ?string $navigationLabel = 'Riegos';
    protected static ?string $modelLabel = 'Riego';
    protected static ?string $label = 'Riego';
    protected static ?string $pluralLabel = 'Riegos';
    protected static ?string $slug = 'riegos';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->label('Fecha')
                    ->required()
                    ->default(now()->toDateString())
                    ->format('Y-m-d')
                    ->displayFormat('d/m/Y'),
                Forms\Components\Select::make('parcel_id')
                    ->label('Cuartel')
                    ->relationship('parcel', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('duration')
                    ->label('Duración (HH:MM:SS)')
                    ->required()
                    ->formatStateUsing(function ($state) {
                        if (is_numeric($state)) {
                            $hours = floor($state / 3600);
                            $minutes = floor(($state % 3600) / 60);
                            $seconds = $state % 60;
                            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                        }
                        return '00:00:00';
                    })
                    ->dehydrateStateUsing(function ($state) {
                        if (preg_match('/^(\d{2}):(\d{2}):(\d{2})$/', $state, $matches)) {
                            $hours = (int) $matches[1];
                            $minutes = (int) $matches[2];
                            $seconds = (int) $matches[3];
                            return ($hours * 3600) + ($minutes * 60) + $seconds;
                        }
                        return 0;
                    })
                    ->reactive(),
                Forms\Components\TextInput::make('quantity_m3')
                    ->label('Cantidad (m³)')
                    ->numeric()
                    ->minValue(0),
                Forms\Components\TextInput::make('type')
                    ->label('Tipo')
                    ->readOnly()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date('d/m/Y')
                    ->label('Fecha')
                    ->sortable(),
                Tables\Columns\TextColumn::make('parcel.name')
                    ->sortable()
                    ->searchable()
                    ->label('Cuartel')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duración')
                    ->formatStateUsing(function ($state) {
                        $hours = floor($state / 3600);
                        $minutes = floor(($state % 3600) / 60);
                        $seconds = $state % 60;
                        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_m3')
                    ->label('Cantidad (m³)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fertilization_count')
                    ->label('Fertilizaciones')
                    ->getStateUsing(function ($record) {
                        return $record->fertilization()->count();
                    })
                    ->toggleable()
                    ->tooltip(function ($record) {
                        // Obtener las fertilizaciones asociadas al registro
                        $fertilizations = $record->fertilization;

                        // Si no hay fertilizaciones, mostrar un mensaje por defecto
                        if ($fertilizations->isEmpty()) {
                            return 'No hay fertilizaciones aplicadas.';
                        }

                        // Mapear las fertilizaciones para obtener los nombres de los fertilizantes y cantidades
                        $fertilizers = $fertilizations->map(function ($fertilization) {
                            // Obtener el nombre del fertilizante desde fertilizerMapping
                            $mapping = $fertilization->fertilizerMapping;
                            $fertilizerName = $mapping ? $mapping->fertilizer_name : 'Desconocido';
                            // Usar quantity_solution como la cantidad (puedes cambiar a quantity_product si prefieres)
                            $quantity = $fertilization->quantity_solution ?? 0;
                            return " - {$fertilizerName}: {$quantity}";
                        });

                        // Unir los fertilizantes en una cadena separada por comas
                        return implode(', ', $fertilizers->toArray());
                    }),
                Tables\Columns\TextColumn::make('created_by')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_by')
                    ->label('Actualizado por')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Creado el')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Actualizado el')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('fecha')
                    ->columns(2)
                    ->form([
                        DatePicker::make('start_date')->label('Fecha Inicio'),
                        DatePicker::make('end_date')
                            ->default(now())
                            ->label('Fecha Fin'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['start_date'], fn ($q) => $q->whereDate('date', '>=', $data['start_date']))
                            ->when($data['end_date'], fn ($q) => $q->whereDate('date', '<=', $data['end_date']));
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Action::make('add_fertilization')
                        ->label('Agregar Fertilización')
                        ->color('warning')
                        ->icon('heroicon-o-plus')
                        ->modalHeading(fn ($record) => 'Agregar Fertilizante al Riego #' . $record->parcel->name)
                        ->modalSubmitActionLabel('Guardar Fertilizante')
                        ->form(function ($record) {
                            return [
                                Forms\Components\Select::make('fertilizer_mapping_id')
                                    ->label('Fertilizante')
                                    ->options(function () {
                                        return FertilizerMapping::all()
                                            ->mapWithKeys(function ($mapping) {
                                                return [$mapping->id => $mapping->fertilizer_name . ' (' . $mapping->product->product_name . ')'];
                                            })
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, $state, $get) {
                                        $mapping = FertilizerMapping::find($state);
                                        if ($mapping) {
                                            $set('dilution_factor', $mapping->dilution_factor);
                                            $set('product_price', $mapping->product->price ?? 0);
                                            $quantity_solution = $get('quantity_solution');
                                            if (is_numeric($quantity_solution) && is_numeric($mapping->dilution_factor)) {
                                                $quantity_product = round($quantity_solution * $mapping->dilution_factor, 2);
                                                $set('quantity_product', $quantity_product);
                                                $set('total_cost', $quantity_product * ($mapping->product->price ?? 0));
                                            }
                                        } else {
                                            $set('dilution_factor', 0);
                                            $set('product_price', 0);
                                            $set('quantity_product', 0);
                                            $set('total_cost', 0);
                                        }
                                    }),
                                Forms\Components\TextInput::make('surface')
                                    ->label('Superficie')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default($record->parcel->surface ?? 0)
                                    ->readOnly(),
                                Forms\Components\TextInput::make('quantity_solution')
                                    ->label('Cantidad Solución')
                                    ->required()
                                    ->numeric()
                                    ->debounce(5000)
                                    ->minValue(0)
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, $state, $get) {
                                        $dilution_factor = $get('dilution_factor');
                                        $product_price = $get('product_price');
                                        if (is_numeric($state) && is_numeric($dilution_factor)) {
                                            $quantity_product = round($state * $dilution_factor, 2);
                                            $set('quantity_product', $quantity_product);
                                            $set('total_cost', $quantity_product * ($product_price ?? 0));
                                        }
                                    }),
                                Forms\Components\TextInput::make('dilution_factor')
                                    ->label('Factor de Dilución')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->readOnly(),
                                Forms\Components\TextInput::make('quantity_product')
                                    ->label('Cantidad Producto')
                                    ->numeric()
                                    ->minValue(0)
                                    ->readOnly()
                                    ->default(0),
                                Forms\Components\Hidden::make('product_price')
                                    ->label('Precio del Producto')
                                    ->default(0),
                                Forms\Components\Hidden::make('total_cost')
                                    ->label('Costo Total')
                                    ->default(0),
                                Forms\Components\Hidden::make('application_method')
                                    ->default('manual'),
                                Forms\Components\Hidden::make('date')
                                    ->default($record->date->format('Y-m-d')),
                            ];
                        })
                        ->action(function (array $data, $record, $livewire) {
                            $mapping = FertilizerMapping::find($data['fertilizer_mapping_id']);
                            $product_id = $mapping ? $mapping->product_id : null;

                            $record->fertilization()->create([
                                'fertilizer_mapping_id' => $data['fertilizer_mapping_id'],
                                'product_id' => $product_id,
                                'surface' => $data['surface'],
                                'quantity_solution' => $data['quantity_solution'],
                                'dilution_factor' => $data['dilution_factor'],
                                'quantity_product' => $data['quantity_product'],
                                'product_price' => $data['product_price'],
                                'total_cost' => $data['total_cost'],
                                'application_method' => $data['application_method'],
                                'date' => $data['date'],
                                'parcel_id' => $record->parcel_id,
                                'field_id' => $record->field_id,
                            ]);
                            $livewire->dispatch('refreshTable');
                            \Filament\Notifications\Notification::make()
                                ->title('Fertilización agregada')
                                ->success()
                                ->send();
                        }),
                ])
                ->button()
                ->label('Acciones'),
            ])
            ->bulkActions([
                ExportBulkAction::make('export'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\FertilizationRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIrrigations::route('/'),
            'create' => Pages\CreateIrrigation::route('/create'),
            'view' => Pages\ViewIrrigation::route('/{record}'),
            'edit' => Pages\EditIrrigation::route('/{record}/edit'),
        ];
    }
}