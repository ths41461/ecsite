<?php

namespace App\Filament\Resources\OrderItemResource\RelationManagers;

use App\Models\OrderItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'orderItems';

    protected static ?string $title = '注文アイテム';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('order_id')
                    ->relationship('order', 'order_number')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->placeholder('注文を選択'),
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->placeholder('商品を選択')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        if ($state) {
                            $product = \App\Models\Product::find($state);
                            if ($product) {
                                $set('name_snapshot', $product->name);
                                $set('sku_snapshot', $product->sku ?? '');
                            }
                        }
                    }),
                Forms\Components\Select::make('product_variant_id')
                    ->relationship('variant', 'sku')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->placeholder('バリエーションを選択（任意）')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        if ($state) {
                            $variant = \App\Models\ProductVariant::find($state);
                            if ($variant) {
                                $set('name_snapshot', $variant->product->name);
                                $set('sku_snapshot', $variant->sku);
                                $set('unit_price_yen', $variant->price_yen);
                                $set('line_total_yen', $variant->price_yen * $get('qty'));
                            }
                        }
                    }),
                Forms\Components\TextInput::make('name_snapshot')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('商品名スナップショット'),
                Forms\Components\TextInput::make('sku_snapshot')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('SKUスナップショット'),
                Forms\Components\TextInput::make('unit_price_yen')
                    ->label('単価（¥）')
                    ->required()
                    ->numeric()
                    ->prefix('¥')
                    ->placeholder('0')
                    ->helperText('注文時の単価')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        $quantity = $get('qty');
                        if ($quantity && $state) {
                            $set('line_total_yen', $quantity * $state);
                        }
                    }),
                Forms\Components\TextInput::make('qty')
                    ->label('数量')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->placeholder('1')
                    ->helperText('注文されたアイテム数')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        $unitPrice = $get('unit_price_yen');
                        if ($unitPrice && $state) {
                            $set('line_total_yen', $unitPrice * $state);
                        }
                    }),
                Forms\Components\TextInput::make('line_total_yen')
                    ->label('小計（¥）')
                    ->required()
                    ->numeric()
                    ->prefix('¥')
                    ->placeholder('0')
                    ->helperText('この行アイテムの合計価格（単価 × 数量）')
                    ->readOnly(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name_snapshot')
            ->columns([
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('注文番号')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name_snapshot')
                    ->label('商品')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sku_snapshot')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_price_yen')
                    ->label('単価')
                    ->formatStateUsing(function ($state) {
                        return '¥' . number_format($state);
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('qty')
                    ->label('数量')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('line_total_yen')
                    ->label('小計')
                    ->formatStateUsing(function ($state) {
                        return '¥' . number_format($state);
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
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