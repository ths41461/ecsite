<?php

namespace App\Filament\Resources\ShipmentStatusResource\RelationManagers;

use App\Models\Shipment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShipmentStatusShipmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'shipments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Shipment Information')
                    ->description('Basic shipment information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('order_id')
                                    ->relationship('order', 'order_number')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('Order'),
                                Forms\Components\TextInput::make('carrier')
                                    ->required()
                                    ->maxLength(100)
                                    ->label('Carrier'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('tracking_number')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->label('Tracking Number'),
                                Forms\Components\DateTimePicker::make('shipped_at')
                                    ->label('Shipped At'),
                            ]),
                        Forms\Components\DateTimePicker::make('delivered_at')
                            ->label('Delivered At'),
                        Forms\Components\Textarea::make('timeline_json')
                            ->rows(4)
                            ->columnSpanFull()
                            ->label('Timeline')
                            ->helperText('JSON timeline of shipment events'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tracking_number')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('carrier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tracking_number')
                    ->label('Tracking #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('shipped_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivered_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('shipped_at')
                    ->form([
                        Forms\Components\DatePicker::make('shipped_from')
                            ->label('Shipped From'),
                        Forms\Components\DatePicker::make('shipped_until')
                            ->label('Shipped Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['shipped_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('shipped_at', '>=', $date)
                            )
                            ->when(
                                $data['shipped_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('shipped_at', '<=', $date)
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AssociateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}