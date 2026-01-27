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

    protected static ?string $navigationGroup = 'ECサイト';

    protected static ?string $navigationLabel = '注文';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('注文情報')
                    ->description('基本的な注文情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('order_number')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('注文番号'),
                                Forms\Components\Select::make('order_status_id')
                                    ->label('ステータス')
                                    ->options(
                                        \App\Models\OrderStatus::pluck('name', 'id')->toArray()
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('ステータスを選択'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('顧客メールアドレス'),
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('顧客名'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('phone')
                                    ->maxLength(20)
                                    ->placeholder('顧客電話番号'),
                                Forms\Components\TextInput::make('user_id')
                                    ->label('ユーザーID')
                                    ->numeric()
                                    ->placeholder('ゲスト注文の場合は空のままにしてください'),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('住所情報')
                    ->description('配送先住所情報')
                    ->schema([
                        Forms\Components\TextInput::make('address_line1')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('番地住所'),
                        Forms\Components\TextInput::make('address_line2')
                            ->maxLength(255)
                            ->placeholder('アパートメント、スイート等（任意）'),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('city')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('市区町村'),
                                Forms\Components\TextInput::make('state')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('都道府県'),
                                Forms\Components\TextInput::make('zip')
                                    ->required()
                                    ->maxLength(20)
                                    ->placeholder('郵便番号'),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('価格情報')
                    ->description('注文価格の詳細')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('subtotal_yen')
                                    ->label('小計（¥）')
                                    ->numeric()
                                    ->required()
                                    ->prefix('¥')
                                    ->placeholder('0'),
                                Forms\Components\TextInput::make('tax_yen')
                                    ->label('税金（¥）')
                                    ->numeric()
                                    ->required()
                                    ->prefix('¥')
                                    ->placeholder('0'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('shipping_yen')
                                    ->label('配送料（¥）')
                                    ->numeric()
                                    ->required()
                                    ->prefix('¥')
                                    ->placeholder('0'),
                                Forms\Components\TextInput::make('discount_yen')
                                    ->label('割引（¥）')
                                    ->numeric()
                                    ->required()
                                    ->prefix('¥')
                                    ->placeholder('0'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('total_yen')
                                    ->label('合計（¥）')
                                    ->numeric()
                                    ->required()
                                    ->prefix('¥')
                                    ->placeholder('0'),
                                Forms\Components\Select::make('payment_mode')
                                    ->options([
                                        'stripe' => 'ストライプ',
                                        'cash_on_delivery' => '代金引換',
                                        'bank_transfer' => '銀行振込',
                                        'other' => 'その他',
                                    ])
                                    ->required()
                                    ->placeholder('支払い方法を選択'),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('タイムスタンプ')
                    ->description('注文ライフサイクルのタイムスタンプ')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('ordered_at')
                                    ->label('注文日時'),
                                Forms\Components\DateTimePicker::make('shipped_at')
                                    ->label('発送日時'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('delivered_at')
                                    ->label('配達日時'),
                                Forms\Components\DateTimePicker::make('canceled_at')
                                    ->label('キャンセル日時'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('details_completed_at')
                                    ->label('詳細完了日時'),
                                Forms\Components\DateTimePicker::make('payment_started_at')
                                    ->label('支払い開始日時'),
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
                    ->label('注文番号')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('顧客')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_yen')
                    ->label('合計')
                    ->formatStateUsing(function ($state) {
                        return '¥' . number_format($state);
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('orderStatus.name')
                    ->label('ステータス')
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
                        return $record->orderStatus?->name ?? '不明';
                    }),
                Tables\Columns\TextColumn::make('ordered_at')
                    ->label('日付')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('商品数')
                    ->counts('items')
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivered_at')
                    ->label('配達日')
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
                    ->placeholder('すべてのステータス'),
                Tables\Filters\Filter::make('ordered_at')
                    ->form([
                        Forms\Components\DatePicker::make('ordered_from')
                            ->label('注文日範囲（開始）'),
                        Forms\Components\DatePicker::make('ordered_until')
                            ->label('注文日範囲（終了）'),
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
                            ->label('最小合計')
                            ->numeric()
                            ->placeholder('¥0'),
                        Forms\Components\TextInput::make('max_total')
                            ->label('最大合計')
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
                    ->label('発送済みにする')
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
                    ->label('配達済みにする')
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
                    ->label('注文をキャンセル')
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
                    ->label('注文を返金')
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