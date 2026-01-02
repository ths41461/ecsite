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
                Forms\Components\Section::make('Tracking Information')
                    ->description('Details about the shipment tracking event')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('carrier')
                                    ->required()
                                    ->maxLength(100)
                                    ->label('Carrier'),
                                Forms\Components\TextInput::make('track_no')
                                    ->label('Tracking Number')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'packed' => 'Packed',
                                        'in_transit' => 'In Transit',
                                        'delivered' => 'Delivered',
                                        'returned' => 'Returned',
                                    ])
                                    ->required()
                                    ->label('Status'),
                                Forms\Components\DateTimePicker::make('event_time')
                                    ->required()
                                    ->label('Event Time'),
                            ]),
                        Forms\Components\Textarea::make('raw_event_json')
                            ->label('Raw Event Data')
                            ->rows(5)
                            ->columnSpanFull()
                            ->helperText('JSON data of the tracking event'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('carrier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('track_no')
                    ->label('Tracking Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'packed' => 'info',
                        'in_transit' => 'warning',
                        'delivered' => 'success',
                        'returned' => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('event_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'packed' => 'Packed',
                        'in_transit' => 'In Transit',
                        'delivered' => 'Delivered',
                        'returned' => 'Returned',
                    ])
                    ->placeholder('All Statuses'),
                Tables\Filters\Filter::make('event_time')
                    ->form([
                        Forms\Components\DatePicker::make('event_time_from')
                            ->label('Event Time From'),
                        Forms\Components\DatePicker::make('event_time_until')
                            ->label('Event Time Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['event_time_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('event_time', '>=', $date)
                            )
                            ->when(
                                $data['event_time_until'],
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