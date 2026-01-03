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

    protected static ?string $title = 'Order Items';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('order_id')
                    ->relationship('order', 'order_number')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->placeholder('Select order'),
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->placeholder('Select product')
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
                    ->placeholder('Select variant (optional)')
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
                    ->placeholder('Product name snapshot'),
                Forms\Components\TextInput::make('sku_snapshot')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('SKU snapshot'),
                Forms\Components\TextInput::make('unit_price_yen')
                    ->label('Unit Price (¥)')
                    ->required()
                    ->numeric()
                    ->prefix('¥')
                    ->placeholder('0')
                    ->helperText('Price per unit at time of order')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        $quantity = $get('qty');
                        if ($quantity && $state) {
                            $set('line_total_yen', $quantity * $state);
                        }
                    }),
                Forms\Components\TextInput::make('qty')
                    ->label('Quantity')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->placeholder('1')
                    ->helperText('Number of items ordered')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        $unitPrice = $get('unit_price_yen');
                        if ($unitPrice && $state) {
                            $set('line_total_yen', $unitPrice * $state);
                        }
                    }),
                Forms\Components\TextInput::make('line_total_yen')
                    ->label('Line Total (¥)')
                    ->required()
                    ->numeric()
                    ->prefix('¥')
                    ->placeholder('0')
                    ->helperText('Total price for this line item (unit price × quantity)')
                    ->readOnly(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name_snapshot')
            ->columns([
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name_snapshot')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sku_snapshot')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_price_yen')
                    ->label('Unit Price')
                    ->formatStateUsing(function ($state) {
                        return '¥' . number_format($state);
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('qty')
                    ->label('Quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('line_total_yen')
                    ->label('Line Total')
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