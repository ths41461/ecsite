<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\OrderStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'E-commerce';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Information')
                    ->description('Basic order information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('order_number')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Order number'),
                                Forms\Components\Select::make('order_status_id')
                                    ->label('Status')
                                    ->options(
                                        \App\Models\OrderStatus::pluck('name', 'id')->toArray()
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Select status'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Customer email'),
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Customer name'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('phone')
                                    ->maxLength(20)
                                    ->placeholder('Customer phone'),
                                Forms\Components\TextInput::make('user_id')
                                    ->label('User ID')
                                    ->numeric()
                                    ->placeholder('Leave empty if guest order'),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Address Information')
                    ->description('Shipping address information')
                    ->schema([
                        Forms\Components\TextInput::make('address_line1')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Street address'),
                        Forms\Components\TextInput::make('address_line2')
                            ->maxLength(255)
                            ->placeholder('Apartment, suite, etc. (optional)'),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('city')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('City'),
                                Forms\Components\TextInput::make('state')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('State/Province'),
                                Forms\Components\TextInput::make('zip')
                                    ->required()
                                    ->maxLength(20)
                                    ->placeholder('ZIP/Postal code'),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pricing Information')
                    ->description('Order pricing details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('subtotal_yen')
                                    ->label('Subtotal (¥)')
                                    ->numeric()
                                    ->required()
                                    ->prefix('¥')
                                    ->placeholder('0'),
                                Forms\Components\TextInput::make('tax_yen')
                                    ->label('Tax (¥)')
                                    ->numeric()
                                    ->required()
                                    ->prefix('¥')
                                    ->placeholder('0'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('shipping_yen')
                                    ->label('Shipping (¥)')
                                    ->numeric()
                                    ->required()
                                    ->prefix('¥')
                                    ->placeholder('0'),
                                Forms\Components\TextInput::make('discount_yen')
                                    ->label('Discount (¥)')
                                    ->numeric()
                                    ->required()
                                    ->prefix('¥')
                                    ->placeholder('0'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('total_yen')
                                    ->label('Total (¥)')
                                    ->numeric()
                                    ->required()
                                    ->prefix('¥')
                                    ->placeholder('0'),
                                Forms\Components\Select::make('payment_mode')
                                    ->options([
                                        'stripe' => 'Stripe',
                                        'cash_on_delivery' => 'Cash on Delivery',
                                        'bank_transfer' => 'Bank Transfer',
                                        'other' => 'Other',
                                    ])
                                    ->required()
                                    ->placeholder('Select payment mode'),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Timestamps')
                    ->description('Order lifecycle timestamps')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('ordered_at')
                                    ->label('Ordered At'),
                                Forms\Components\DateTimePicker::make('shipped_at')
                                    ->label('Shipped At'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('delivered_at')
                                    ->label('Delivered At'),
                                Forms\Components\DateTimePicker::make('canceled_at')
                                    ->label('Canceled At'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('details_completed_at')
                                    ->label('Details Completed At'),
                                Forms\Components\DateTimePicker::make('payment_started_at')
                                    ->label('Payment Started At'),
                            ]),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_yen')
                    ->label('Total')
                    ->formatStateUsing(function ($state) {
                        return '¥' . number_format($state);
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('orderStatus.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Paid' => 'success',
                        'Fulfilled' => 'info',
                        'Cancelled' => 'danger',
                        'Refunded' => 'gray',
                        default => 'secondary',
                    })
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        return $record->orderStatus?->name ?? 'Unknown';
                    }),
                Tables\Columns\TextColumn::make('ordered_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivered_at')
                    ->label('Delivered On')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('order_status_id')
                    ->options(
                        \App\Models\OrderStatus::pluck('name', 'id')->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->placeholder('All Statuses'),
                Tables\Filters\Filter::make('ordered_at')
                    ->form([
                        Forms\Components\DatePicker::make('ordered_from')
                            ->label('Ordered From'),
                        Forms\Components\DatePicker::make('ordered_until')
                            ->label('Ordered Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['ordered_from'],
                                fn (Builder $query, $value): Builder => $query->whereDate('ordered_at', '>=', $value)
                            )
                            ->when(
                                $data['ordered_until'],
                                fn (Builder $query, $value): Builder => $query->whereDate('ordered_at', '<=', $value)
                            );
                    }),
                Tables\Filters\Filter::make('total_yen')
                    ->form([
                        Forms\Components\TextInput::make('min_total')
                            ->label('Min Total')
                            ->numeric()
                            ->placeholder('¥0'),
                        Forms\Components\TextInput::make('max_total')
                            ->label('Max Total')
                            ->numeric()
                            ->placeholder('¥999999'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_total'],
                                fn (Builder $query, $value): Builder => $query->where('total_yen', '>=', $value)
                            )
                            ->when(
                                $data['max_total'],
                                fn (Builder $query, $value): Builder => $query->where('total_yen', '<=', $value)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil'),
                Action::make('mark_shipped')
                    ->label('Mark Shipped')
                    ->icon('heroicon-o-truck')
                    ->requiresConfirmation()
                    ->color('info')
                    ->action(function (Order $record) {
                        $shippedId = (int) DB::table('order_statuses')->where('code', 'fulfilled')->value('id');
                        $record->transitionTo($shippedId);
                        $record->update(['shipped_at' => now()]);
                    })
                    ->visible(fn (Order $record) => $record->orderStatus?->code === 'paid'),
                Action::make('mark_delivered')
                    ->label('Mark Delivered')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->color('success')
                    ->action(function (Order $record) {
                        $deliveredId = (int) DB::table('order_statuses')->where('code', 'fulfilled')->value('id');
                        $record->transitionTo($deliveredId);
                        $record->update(['delivered_at' => now()]);
                    })
                    ->visible(fn (Order $record) => $record->orderStatus?->code === 'fulfilled'),
                Action::make('cancel')
                    ->label('Cancel Order')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->action(function (Order $record) {
                        $canceledId = (int) DB::table('order_statuses')->where('code', 'cancelled')->value('id');
                        $record->transitionTo($canceledId);
                        $record->update(['canceled_at' => now()]);
                    })
                    ->visible(fn (Order $record) => !in_array($record->orderStatus?->code, ['cancelled', 'refunded'])),
                Action::make('refund')
                    ->label('Refund Order')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->color('warning')
                    ->action(function (Order $record) {
                        $refundedId = (int) DB::table('order_statuses')->where('code', 'refunded')->value('id');
                        $record->transitionTo($refundedId);
                    })
                    ->visible(fn (Order $record) => $record->orderStatus?->code !== 'refunded'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('ordered_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OrderItemsRelationManager::class,
            RelationManagers\OrderPaymentsRelationManager::class,
            RelationManagers\OrderShipmentsRelationManager::class,
            RelationManagers\OrderStatusHistoryRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('items');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
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