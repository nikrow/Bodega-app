<?php

namespace App\Filament\Resources\ImportBatchResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Jobs\ProcessExcel;
use Filament\Tables\Table;
use App\Models\ImportedEvent;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';
    protected static ?string $model = ImportedEvent::class;
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('tenant')
                    ->label('Campo')
                    ->disabled(),
                Forms\Components\TextInput::make('description')
                    ->label('Cuartel')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('date_time')
                    ->label('Fecha y Hora')
                    ->disabled(),
                Forms\Components\TextInput::make('duration')
                    ->label('Duración')
                    ->disabled(),
                Forms\Components\TextInput::make('quantity_m3')
                    ->label('Cantidad (m³)')
                    ->numeric()
                    ->disabled(),
                Forms\Components\KeyValue::make('fertilizers')
                    ->label('Fertilizantes')
                    ->disabled(),
                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'processed' => 'Procesado',
                        'failed' => 'Fallido',
                    ])
                    ->disabled(),
                Forms\Components\Textarea::make('error_message')
                    ->label('Mensaje de Error')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
            
                Tables\Columns\TextColumn::make('description')
                    ->label('Cuartel')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_time')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_m3')
                    ->label('Cantidad (m³)')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processed' => 'success',
                        'failed' => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('error_message')
                    ->label('Mensaje de Error')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pendiente',
                        'processed' => 'Procesado',
                        'failed' => 'Fallido',
                    ])
                    ->label('Estado'),
            ])
            ->actions([
                Action::make('reprocess')
                    ->label('Reprocesar')
                    ->icon('eva-refresh-outline')
                    ->color('primary')
                    ->action(function (ImportedEvent $record) {
                        $record->update(['status' => 'pending', 'error_message' => null]);
                        ProcessExcel::dispatch(null, $record->tenant);
                    })
                    ->visible(fn (ImportedEvent $record) => $record->status === 'failed'),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
