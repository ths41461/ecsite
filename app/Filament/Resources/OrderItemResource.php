<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderItemResource\Pages;
use App\Filament\Resources\OrderItemResource\RelationManagers;
use App\Models\OrderItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderItemResource extends Resource
{
    protected static ?string $model = OrderItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'ECサイト';

    protected static ?string $navigationLabel = '注文アイテム';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('注文アイテム情報')
                    ->description('注文内のアイテムに関する情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('order_id')
                                    ->relationship('order', 'order_number')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('注文'),
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('商品')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $product = \App\Models\Product::find($state);
                                            if ($product) {
                                                $set('product_name_snapshot', $product->name);
                                            }
                                        }
                                    }),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('product_variant_id')
                                    ->relationship('variant', 'sku')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('商品バリエーション')
                                    ->helperText('商品の特定のバリエーションを選択してください'),
                                Forms\Components\TextInput::make('product_name_snapshot')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('商品名（スナップショット）')
                                    ->helperText('注文時の名称'),
                            ]),
                        Forms\Components\TextInput::make('sku_snapshot')
                            ->required()
                            ->maxLength(255)
                            ->label('SKU（スナップショット）')
                            ->helperText('注文時のSKU'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('価格情報')
                    ->description('この注文アイテムの価格情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('unit_price_yen')
                                    ->required()
                                    ->numeric()
                                    ->prefix('¥')
                                    ->label('単価（¥）')
                                    ->helperText('注文時の単価')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        $quantity = $get('quantity');
                                        if ($quantity && $state) {
                                            $set('line_total_yen', $quantity * $state);
                                        }
                                    }),
                                Forms\Components\TextInput::make('quantity')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->label('数量')
                                    ->helperText('注文されたアイテム数')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        $unitPrice = $get('unit_price_yen');
                                        if ($unitPrice && $state) {
                                            $set('line_total_yen', $unitPrice * $state);
                                        }
                                    }),
                            ]),
                        Forms\Components\TextInput::make('line_total_yen')
                            ->required()
                            ->numeric()
                            ->prefix('¥')
                            ->label('小計（¥）')
                            ->helperText('この行アイテムの合計価格（自動計算）')
                            ->readOnly(),
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
                    ->label('注文番号')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('商品')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sku_snapshot')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_price_yen')
                    ->label('単価')
                    ->formatStateUsing(fn ($state) => '¥' . number_format($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('数量')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('line_total_yen')
                    ->label('小計')
                    ->formatStateUsing(fn ($state) => '¥' . number_format($state))
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
                Tables\Filters\SelectFilter::make('order_id')
                    ->relationship('order', 'order_number')
                    ->searchable()
                    ->preload()
                    ->placeholder('すべての注文'),
                Tables\Filters\SelectFilter::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('すべての商品'),
                Tables\Filters\Filter::make('price_range')
                    ->form([
                        Forms\Components\TextInput::make('min_price')
                            ->label('最小価格')
                            ->numeric()
                            ->prefix('¥'),
                        Forms\Components\TextInput::make('max_price')
                            ->label('最大価格')
                            ->numeric()
                            ->prefix('¥'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_price'],
                                fn (Builder $query, $value): Builder => $query->where('unit_price_yen', '>=', $value)
                            )
                            ->when(
                                $data['max_price'],
                                fn (Builder $query, $value): Builder => $query->where('unit_price_yen', '<=', $value)
                            );
                    }),
                Tables\Filters\Filter::make('quantity_range')
                    ->form([
                        Forms\Components\TextInput::make('min_quantity')
                            ->label('最小数量')
                            ->numeric(),
                        Forms\Components\TextInput::make('max_quantity')
                            ->label('最大数量')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_quantity'],
                                fn (Builder $query, $value): Builder => $query->where('quantity', '>=', $value)
                            )
                            ->when(
                                $data['max_quantity'],
                                fn (Builder $query, $value): Builder => $query->where('quantity', '<=', $value)
                            );
                    }),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('作成日範囲（開始）'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('作成日範囲（終了）'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date)
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
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListOrderItems::route('/'),
            'create' => Pages\CreateOrderItem::route('/create'),
            'view' => Pages\ViewOrderItem::route('/{record}'),
            'edit' => Pages\EditOrderItem::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return '注文アイテム';
    }

    public static function getPluralModelLabel(): string
    {
        return '注文アイテム';
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