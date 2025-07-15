<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Parcel;
use App\Enums\RoleType;
use App\Models\Product;
use Filament\Forms\Form;
use App\Models\Irrigation;
use Filament\Tables\Table;
use App\Models\Fertilization;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use App\Models\FertilizerMapping;
use Filament\Tables\Actions\Action;
use Filament\Tables\Grouping\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\FertilizationResource\Pages;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;
use App\Filament\Resources\FertilizationResource\RelationManagers;

class FertilizationResource extends Resource
{
    protected static ?string $model = Fertilization::class;

    protected static ?string $navigationIcon = 'bi-box2';
    protected static ?string $navigationGroup = 'Fertirriego';
    protected static ?string $navigationLabel = 'Fertilizaciones';
    protected static ?string $modelLabel = 'Fertilización';
    protected static ?string $label = 'Fertilización';
    protected static ?string $pluralLabel = 'Fertilizaciones';
    protected static ?string $slug = 'fertilizaciones';
    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('parcel_id')
                    ->label('Cuartel')
                    ->options(function () {
                        $tenantId = Filament::getTenant()->id;
                        return cache()->remember("parcels_for_tenant_{$tenantId}", 3600, function () use ($tenantId) {
                            return Parcel::whereHas('field', function ($query) use ($tenantId) {
                                $query->where('field_id', $tenantId);
                            })
                            ->pluck('name', 'id')
                            ->toArray();
                        });
                    })
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        $parcel = Parcel::find($state);
                        $set('surface', $parcel ? $parcel->surface : 0);
                        $set('field_id', $parcel ? $parcel->field_id : null);
                    }),
                Forms\Components\Select::make('irrigation_id')
                    ->label('Riego')
                    ->options(function (callable $get) {
                        $parcelId = $get('parcel_id');
                        $tenantId = Filament::getTenant()->id;
                        if ($parcelId) {
                            return Irrigation::where('parcel_id', $parcelId)
                                ->whereHas('field', function ($query) use ($tenantId) {
                                    $query->where('field_id', $tenantId);
                                })
                                ->pluck('date', 'id')
                                ->mapWithKeys(function ($date, $id) {
                                    return [$id => \Carbon\Carbon::parse($date)->format('d/m/Y')];
                                })
                                ->toArray();
                        }
                        return [];
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        $irrigation = Irrigation::find($state);
                        $set('date', $irrigation ? $irrigation->date->format('Y-m-d') : null);
                    }),
                Forms\Components\Hidden::make('date')
                    ->default(function (callable $get) {
                        $irrigation = Irrigation::find($get('irrigation_id'));
                        return $irrigation ? $irrigation->date->format('Y-m-d') : null;
                    }),
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
                            $set('product_id', $mapping->product_id);
                            $set('dilution_factor', $mapping->dilution_factor);
                            $set('product_price', $mapping->product->price ?? 0);
                            $quantity_solution = $get('quantity_solution');
                            if (is_numeric($quantity_solution) && is_numeric($mapping->dilution_factor)) {
                                $quantity_product = round($quantity_solution * $mapping->dilution_factor, 2);
                                $set('quantity_product', $quantity_product);
                                $set('total_cost', $quantity_product * ($mapping->product->price ?? 0));
                            }
                        }
                    }),
                Forms\Components\TextInput::make('surface')
                    ->label('Superficie')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->readOnly(),
                Forms\Components\TextInput::make('quantity_solution')
                    ->label('Cantidad Solución')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->reactive()
                    ->debounce(3000)
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
                    ->readOnly()
                    ->minValue(0),
                Forms\Components\TextInput::make('quantity_product')
                    ->label('Cantidad Producto')
                    ->numeric()
                    ->minValue(0)
                    ->readOnly()
                    ->default(0),
                Forms\Components\Hidden::make('product_price')
                    ->default(0),
                Forms\Components\Hidden::make('total_cost')
                    ->default(0),
                Forms\Components\Hidden::make('application_method')
                    ->default('manual'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('date', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['parcel', 'fertilizerMapping.product']);
            })
            ->groups([
                Group::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->collapsible()
                    ->titlePrefixedWithLabel(false),
                Group::make('parcel.name')
                    ->label('Cuartel')
                    ->collapsible()
                    ->titlePrefixedWithLabel(false),
            ])
            ->groupingSettingsInDropdownOnDesktop()
            ->groupRecordsTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Agrupar'),
            )
            ->defaultPaginationPageOption(50)
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->sortable()
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('parcel.name')
                    ->label('Cuartel')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\SelectColumn::make('fertilizer_mapping_id')
                    ->label('Fertilizante')
                    ->options(function () {
                        return FertilizerMapping::all()
                            ->mapWithKeys(function ($mapping) {
                                return [$mapping->id => $mapping->fertilizer_name . ' (' . $mapping->product->product_name . ')'];
                            })
                            ->toArray();
                    })
                    ->searchable()
                    ->disabled(fn ($record) => !Gate::allows('update', $record))
                    ->tooltip(function ($record) {
                        if (!Gate::allows('update', $record)) {
                            $user = Auth::user();
                            if ($record->field_id !== Filament::getTenant()->id) {
                                return 'No tienes permiso para modificar este registro.';
                            }
                            if (!in_array($user->role, [RoleType::ADMIN, RoleType::AGRONOMO, RoleType::ASISTENTE])) {
                                return 'No tienes el rol necesario para modificar fertilizaciones.';
                            }
                            if ($record->created_at->diffInDays(Carbon::now()) > 4 && $user->role !== RoleType::ADMIN) {
                                return 'No se puede modificar la fertilización después de 4 días de su creación.';
                            }
                        }
                        return null;
                    })
                    ->afterStateUpdated(function ($record, $state, $livewire) {
                        if (!Gate::allows('update', $record)) {
                            \Filament\Notifications\Notification::make()
                                ->title('No autorizado')
                                ->body('No tienes permiso para modificar este registro.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $mapping = FertilizerMapping::find($state);
                        if ($mapping) {
                            $product_id = $mapping->product_id;
                            $dilution_factor = $mapping->dilution_factor;
                            $quantity_solution = $record->quantity_solution;
                            $product_price = $mapping->product->price ?? 0;
                            $quantity_product = (is_numeric($quantity_solution) && is_numeric($dilution_factor))
                                ? round($quantity_solution * $dilution_factor, 2)
                                : 0;
                            $total_cost = $quantity_product * $product_price;

                            $record->update([
                                'fertilizer_mapping_id' => $state,
                                'product_id' => $product_id,
                                'dilution_factor' => $dilution_factor,
                                'quantity_product' => $quantity_product,
                                'product_price' => $product_price,
                                'total_cost' => $total_cost,
                            ]);

                            $livewire->dispatch('refreshTable');
                            \Filament\Notifications\Notification::make()
                                ->title('Fertilizante actualizado')
                                ->success()
                                ->send();
                        }
                    }),
                Tables\Columns\TextColumn::make('surface')
                    ->label('Superficie')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextInputColumn::make('quantity_solution')
                    ->label('Cantidad Solución')
                    ->rules(['numeric', 'min:0'])
                    ->disabled(fn ($record) => !Gate::allows('update', $record))
                    ->tooltip(function ($record) {
                        if (!Gate::allows('update', $record)) {
                            $user = Auth::user();
                            if ($record->field_id !== Filament::getTenant()->id) {
                                return 'No tienes permiso para modificar este registro.';
                            }
                            if (!in_array($user->role, [RoleType::ADMIN, RoleType::AGRONOMO, RoleType::ASISTENTE])) {
                                return 'No tienes el rol necesario para modificar fertilizaciones.';
                            }
                            if ($record->created_at->diffInDays(Carbon::now()) > 5 && $user->role !== RoleType::ADMIN) {
                                return 'No se puede modificar la fertilización después de 5 días de su creación.';
                            }
                        }
                        return null;
                    })
                    ->afterStateUpdated(function ($record, $state, $livewire) {
                        if (!Gate::allows('update', $record)) {
                            \Filament\Notifications\Notification::make()
                                ->title('No autorizado')
                                ->body('No tienes permiso para modificar este registro.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $dilution_factor = $record->dilution_factor;
                        $product_price = $record->product_price ?? 0;
                        $quantity_product = is_numeric($state) && is_numeric($dilution_factor)
                            ? round($state * $dilution_factor, 2)
                            : 0;
                        $total_cost = $quantity_product * $product_price;

                        $record->update([
                            'quantity_solution' => $state,
                            'quantity_product' => $quantity_product,
                            'total_cost' => $total_cost,
                        ]);

                        $livewire->dispatch('refreshTable');
                        \Filament\Notifications\Notification::make()
                            ->title('Cantidad actualizada')
                            ->success()
                            ->send();
                    }),
                Tables\Columns\TextColumn::make('dilution_factor')
                    ->label('Dilución')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Producto')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('product.SAP_code')
                    ->label('Codigo SAP')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity_product')
                    ->label('Cantidad Producto')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_price')
                    ->label('Precio Producto')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->money('usd'),
                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Costo Total')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->money('usd'),
                Tables\Columns\TextColumn::make('application_method')
                    ->label('Método Aplicación')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
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
                    Tables\Actions\EditAction::make()
                        ->label('Editar'),
                    Tables\Actions\DeleteAction::make()
                        ->label('Eliminar')
                        ->action(function ($record) {
                            $record->delete();
                            \Filament\Notifications\Notification::make()
                                ->title('Fertilización eliminada')
                                ->success()
                                ->send();
                        }),
                ])
                ->button()
                ->label('Acciones'),
            ])
            ->bulkActions([
                ExportBulkAction::make(),
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
            'index' => Pages\ListFertilizations::route('/'),
            'create' => Pages\CreateFertilization::route('/create'),
            'edit' => Pages\EditFertilization::route('/{record}/edit'),
        ];
    }
}