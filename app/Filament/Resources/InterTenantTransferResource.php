<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Services\StockService;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use App\Models\InterTenantTransfer;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\InterTenantTransferResource\Pages;

class InterTenantTransferResource extends Resource
{
    protected static ?string $model = InterTenantTransfer::class;

    protected static ?string $navigationGroup = 'Bodega';
    protected static bool $isScopedToTenant = false;

    protected static ?string $navigationLabel = 'Transferencias entre campos';

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Select::make('tenant_origen_id')
                    ->label('Tenant de Origen')
                    ->options(\App\Models\Field::all()->pluck('name', 'id'))
                    ->required()
                    ->disabled(),
                Select::make('tenant_destino_id')
                    ->label('Tenant de Destino')
                    ->options(\App\Models\Field::all()->pluck('name', 'id'))
                    ->required()
                    ->disabled(),
                Select::make('bodega_origen_id')
                    ->label('Bodega de Origen')
                    ->options(\App\Models\Warehouse::all()->pluck('name', 'id'))
                    ->required()
                    ->disabled(),
                Select::make('bodega_destino_id')
                    ->label('Bodega de Destino')
                    ->options(\App\Models\Warehouse::all()->pluck('name', 'id'))
                    ->required()
                    ->disabled(),
                Select::make('orden_compra_id')
                    ->label('Orden de Compra')
                    ->options(\App\Models\PurchaseOrder::all()->pluck('number', 'id'))
                    ->nullable(),
                Select::make('estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'aprobado' => 'Aprobado',
                        'rechazado' => 'Rechazado',
                    ])
                    ->default('pendiente')
                    ->required()
                    ->disabled(),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('tenantOrigen.name')->label('Tenant Origen'),
                TextColumn::make('tenantDestino.name')->label('Tenant Destino'),
                TextColumn::make('bodegaOrigen.name')->label('Bodega Origen'),
                TextColumn::make('bodegaDestino.name')->label('Bodega Destino'),
                TextColumn::make('estado')->label('Estado')->badge(),
                TextColumn::make('movimientoOrigen.id')->label('Movimiento Origen'),
                TextColumn::make('movimientoDestino.id')->label('Movimiento Destino'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'aprobado' => 'Aprobado',
                        'rechazado' => 'Rechazado',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Aprobar')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->action(function (InterTenantTransfer $record, StockService $stockService) {
                        $stockService->approveInterTenantTransfer($record, Auth::id());
                        Log::info("Transferencia ID: {$record->id} aprobada por usuario ID: " . Auth::id());
                    })
                    ->visible(fn(InterTenantTransfer $record) => $record->estado === 'pendiente' && Filament::getTenant()->id === $record->tenant_destino_id),
                Action::make('reject')
                    ->label('Rechazar')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->requiresConfirmation()
                    ->action(function (InterTenantTransfer $record, StockService $stockService) {
                        $stockService->rejectInterTenantTransfer($record, Auth::id());
                        Log::info("Transferencia ID: {$record->id} rechazada por usuario ID: " . Auth::id());
                    })
                    ->visible(fn(InterTenantTransfer $record) => $record->estado === 'pendiente' && Filament::getTenant()->id === $record->tenant_destino_id),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Agrega relaciones si es necesario
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInterTenantTransfers::route('/'),
            
        ];
    }
}