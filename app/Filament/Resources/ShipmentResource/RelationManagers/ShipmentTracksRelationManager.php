<?php

namespace App\Filament\Resources\ShipmentResource\RelationManagers;

use App\Models\ShipmentTrack;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShipmentTracksRelationManager extends RelationManager
{
    protected static string $relationship = 'shipmentTracks';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tracking Event Information')
                    ->description('Information about the shipment tracking event')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('carrier')
                                    ->required()
                                    ->maxLength(100)
                                    ->label('Carrier'),
                                Forms\Components\TextInput::make('track_no')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Tracking Number'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'packed' => 'Packed',
                                        'label_created' => 'Label Created',
                                        'in_transit' => 'In Transit',
                                        'out_for_delivery' => 'Out for Delivery',
                                        'delivered' => 'Delivered',
                                        'exception' => 'Exception',
                                        'returned' => 'Returned',
                                    ])
                                    ->required()
                                    ->label('Status'),
                                Forms\Components\DateTimePicker::make('event_time')
                                    ->required()
                                    ->label('Event Time'),
                            ]),
                        Forms\Components\Textarea::make('raw_event_json')
                            ->rows(4)
                            ->columnSpanFull()
                            ->label('Raw Event Data')
                            ->helperText('JSON data from carrier webhook'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('track_no')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('carrier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('track_no')
                    ->label('Tracking #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'packed' => 'info',
                        'label_created' => 'info',
                        'in_transit' => 'warning',
                        'out_for_delivery' => 'primary',
                        'delivered' => 'success',
                        'exception' => 'danger',
                        'returned' => 'secondary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state)))
                    ->sortable(),
                Tables\Columns\TextColumn::make('event_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'packed' => 'Packed',
                        'label_created' => 'Label Created',
                        'in_transit' => 'In Transit',
                        'out_for_delivery' => 'Out for Delivery',
                        'delivered' => 'Delivered',
                        'exception' => 'Exception',
                        'returned' => 'Returned',
                    ])
                    ->placeholder('All Statuses'),
                Tables\Filters\Filter::make('event_time')
                    ->form([
                        Forms\Components\DatePicker::make('event_from')
                            ->label('Event From'),
                        Forms\Components\DatePicker::make('event_until')
                            ->label('Event Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['event_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('event_time', '>=', $date)
                            )
                            ->when(
                                $data['event_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('event_time', '<=', $date)
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}