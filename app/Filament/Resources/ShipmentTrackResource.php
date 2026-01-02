<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShipmentTrackResource\Pages;
use App\Filament\Resources\ShipmentTrackResource\RelationManagers;
use App\Models\ShipmentTrack;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShipmentTrackResource extends Resource
{
    protected static ?string $model = ShipmentTrack::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Orders';

    protected static ?int $navigationSort = 14;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tracking Information')
                    ->description('Information about the shipment tracking event')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('shipment_id')
                                    ->relationship('shipment', 'tracking_number')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('Shipment'),
                                Forms\Components\TextInput::make('carrier')
                                    ->required()
                                    ->maxLength(100)
                                    ->label('Carrier'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('track_no')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Tracking Number'),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'label_created' => 'Label Created',
                                        'picked_up' => 'Picked Up',
                                        'in_transit' => 'In Transit',
                                        'out_for_delivery' => 'Out for Delivery',
                                        'delivered' => 'Delivered',
                                        'exception' => 'Exception',
                                        'returned' => 'Returned',
                                    ])
                                    ->required()
                                    ->label('Status'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('event_time')
                                    ->required()
                                    ->label('Event Time'),
                                Forms\Components\TextInput::make('location')
                                    ->maxLength(255)
                                    ->label('Location'),
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->maxLength(65535)
                            ->label('Description'),
                        Forms\Components\Textarea::make('raw_event_json')
                            ->rows(5)
                            ->columnSpanFull()
                            ->label('Raw Event Data')
                            ->helperText('JSON data received from carrier webhook'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('shipment.tracking_number')
                    ->label('Shipment Tracking #')
                    ->searchable()
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
                        'label_created' => 'gray',
                        'picked_up' => 'info',
                        'in_transit' => 'warning',
                        'out_for_delivery' => 'primary',
                        'delivered' => 'success',
                        'exception' => 'danger',
                        'returned' => 'secondary',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('event_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('location')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'label_created' => 'Label Created',
                        'picked_up' => 'Picked Up',
                        'in_transit' => 'In Transit',
                        'out_for_delivery' => 'Out for Delivery',
                        'delivered' => 'Delivered',
                        'exception' => 'Exception',
                        'returned' => 'Returned',
                    ])
                    ->placeholder('All Statuses'),
                Tables\Filters\SelectFilter::make('shipment_id')
                    ->relationship('shipment', 'tracking_number')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Shipments'),
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
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('event_time', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShipmentTracks::route('/'),
            'create' => Pages\CreateShipmentTrack::route('/create'),
            'view' => Pages\ViewShipmentTrack::route('/{record}'),
            'edit' => Pages\EditShipmentTrack::route('/{record}/edit'),
        ];
    }

    public static function can(string $action, $record = null): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->isAdmin();
    }
}