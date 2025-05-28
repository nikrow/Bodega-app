<?php

namespace App\Filament\Resources\IrrigationResource\RelationManagers;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Enums\RoleType;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use App\Models\FertilizerMapping;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class FertilizationRelationManager extends RelationManager
{
    protected static string $relationship = 'fertilization';
    protected static ?string $navigationLabel = 'Fertilizaciones';
    protected static ?string $modelLabel = 'Fertilización';
    protected static ?string $label = 'Fertilización';
    protected static ?string $pluralLabel = 'Fertilizaciones';
    protected static ?string $title = 'Fertilizaciones';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                Forms\Components\Hidden::make('surface')
                    ->label('Superficie')
                    ->default(function () {
                        $irrigation = $this->getOwnerRecord();
                        return $irrigation->parcel->surface ?? 0;
                    }),
                Forms\Components\TextInput::make('quantity_solution')
                    ->label('Cantidad Solución')
                    ->required()
                    ->numeric()
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
                    ->readOnly()
                    ->minValue(0),
                Forms\Components\TextInput::make('quantity_product')
                    ->label('Cantidad Producto')
                    ->numeric()
                    ->minValue(0)
                    ->readOnly()
                    ->default(0),
                Forms\Components\Hidden::make('product_price')
                    ->label('Precio del Producto'),
                Forms\Components\Hidden::make('total_cost')
                    ->label('Costo Total')
                    ->default(0),
                Forms\Components\Hidden::make('application_method')
                    ->default('manual'),
                Forms\Components\Hidden::make('date')
                    ->default(function () {
                        $irrigation = $this->getOwnerRecord();
                        return $irrigation->date->format('Y-m-d');
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->sortable()
                    ->date('d/m/Y'),
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
                    ->label('Factor de Dilución')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_product')
                    ->label('Cantidad Producto')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_price')
                    ->label('Precio Producto')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->money('usd'),
                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Costo Total')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->money('usd'),
                Tables\Columns\TextColumn::make('application_method')
                    ->label('Método Aplicación')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Fertilización'),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
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
                //
            ]);
    }
}