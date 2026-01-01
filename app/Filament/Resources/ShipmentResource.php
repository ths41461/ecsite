<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShipmentResource\Pages;
use App\Filament\Resources\ShipmentResource\RelationManagers;
use App\Models\Shipment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Orders';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('order_id')
                    ->relationship('order', 'id')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('carrier')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('tracking_number')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('shipment_status_id')
                    ->relationship('shipmentStatus', 'name')
                    ->label('Status')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'label' => 'Label Created',
                        'shipped' => 'Shipped',
                        'in_transit' => 'In Transit',
                        'delivered' => 'Delivered',
                        'exception' => 'Exception',
                    ])
                    ->default('label')
                    ->required(),
                Forms\Components\DateTimePicker::make('shipped_at'),
                Forms\Components\DateTimePicker::make('delivered_at'),
                Forms\Components\Textarea::make('timeline_json')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.id')
                    ->label('Order ID')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('carrier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tracking_number')
                    ->label('Tracking #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('shipmentStatus.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Packed' => 'info',
                        'In Transit' => 'primary',
                        'Delivered' => 'success',
                        'Returned' => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'label' => 'info',
                        'shipped' => 'primary',
                        'in_transit' => 'warning',
                        'delivered' => 'success',
                        'exception' => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('shipped_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivered_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('shipment_status_id')
                    ->relationship('shipmentStatus', 'name')
                    ->label('Status'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'label' => 'Label Created',
                        'shipped' => 'Shipped',
                        'in_transit' => 'In Transit',
                        'delivered' => 'Delivered',
                        'exception' => 'Exception',
                    ])
                    ->placeholder('All Statuses'),
                Tables\Filters\Filter::make('shipped_at')
                    ->dateTime()
                    ->form([
                        Forms\Components\DatePicker::make('shipped_from'),
                        Forms\Components\DatePicker::make('shipped_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['shipped_from'], fn ($query, $date) => $query->whereDate('shipped_at', '>=', $date))
                            ->when($data['shipped_until'], fn ($query, $date) => $query->whereDate('shipped_at', '<=', $date));
                    }),
                Tables\Filters\Filter::make('delivered_at')
                    ->dateTime()
                    ->form([
                        Forms\Components\DatePicker::make('delivered_from'),
                        Forms\Components\DatePicker::make('delivered_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['delivered_from'], fn ($query, $date) => $query->whereDate('delivered_at', '>=', $date))
                            ->when($data['delivered_until'], fn ($query, $date) => $query->whereDate('delivered_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ShipmentTracksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShipments::route('/'),
            'create' => Pages\CreateShipment::route('/create'),
            'view' => Pages\ViewShipment::route('/{record}'),
            'edit' => Pages\EditShipment::route('/{record}/edit'),
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