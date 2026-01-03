<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderStatusHistoryResource\Pages;
use App\Filament\Resources\OrderStatusHistoryResource\RelationManagers;
use App\Models\OrderStatusHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderStatusHistoryResource extends Resource
{
    protected static ?string $model = OrderStatusHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Orders';

    protected static ?int $navigationSort = 17;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Status History Information')
                    ->description('Information about the order status change')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('order_id')
                                    ->relationship('order', 'order_number')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('Order'),
                                Forms\Components\Select::make('from_status_id')
                                    ->relationship('fromStatus', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('From Status'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('to_status_id')
                                    ->relationship('toStatus', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('To Status'),
                                Forms\Components\Select::make('changed_by')
                                    ->relationship('changedByUser', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->label('Changed By (User ID)')
                                    ->helperText('User who changed the status'),
                            ]),
                        Forms\Components\DateTimePicker::make('changed_at')
                            ->required()
                            ->label('Changed At'),
                        Forms\Components\Textarea::make('note')
                            ->rows(3)
                            ->maxLength(65535)
                            ->label('Note')
                            ->helperText('Optional note about the status change'),
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
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fromStatus.name')
                    ->label('From Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'gray',
                        'Processing' => 'info',
                        'Shipped' => 'warning',
                        'Delivered' => 'success',
                        'Cancelled' => 'danger',
                        'Refunded' => 'secondary',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('toStatus.name')
                    ->label('To Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'gray',
                        'Processing' => 'info',
                        'Shipped' => 'warning',
                        'Delivered' => 'success',
                        'Cancelled' => 'danger',
                        'Refunded' => 'secondary',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('changedByUser.name')
                    ->label('Changed By')
                    ->searchable()
                    ->sortable()
                    ->placeholder('System'),
                Tables\Columns\TextColumn::make('changed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('order_id')
                    ->relationship('order', 'order_number')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Orders'),
                Tables\Filters\SelectFilter::make('from_status_id')
                    ->relationship('fromStatus', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All From Statuses'),
                Tables\Filters\SelectFilter::make('to_status_id')
                    ->relationship('toStatus', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All To Statuses'),
                Tables\Filters\Filter::make('changed_at')
                    ->form([
                        Forms\Components\DatePicker::make('changed_from')
                            ->label('Changed From'),
                        Forms\Components\DatePicker::make('changed_until')
                            ->label('Changed Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['changed_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('changed_at', '>=', $date)
                            )
                            ->when(
                                $data['changed_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('changed_at', '<=', $date)
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
            ->defaultSort('changed_at', 'desc');
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
            'index' => Pages\ListOrderStatusHistories::route('/'),
            'create' => Pages\CreateOrderStatusHistory::route('/create'),
            'view' => Pages\ViewOrderStatusHistory::route('/{record}'),
            'edit' => Pages\EditOrderStatusHistory::route('/{record}/edit'),
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