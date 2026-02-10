<?php

namespace App\Filament\Resources\ProductVariantResource\RelationManagers;

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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('order_id')
                    ->relationship('order', 'order_number')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('注文'),
                Forms\Components\TextInput::make('product_name_snapshot')
                    ->required()
                    ->maxLength(255)
                    ->label('商品名（スナップショット）'),
                Forms\Components\TextInput::make('sku_snapshot')
                    ->required()
                    ->maxLength(255)
                    ->label('SKU（スナップショット）'),
                Forms\Components\TextInput::make('unit_price_yen')
                    ->required()
                    ->numeric()
                    ->prefix('¥')
                    ->label('単価（¥）'),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->label('数量'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('注文番号')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_name_snapshot')
                    ->label('商品名')
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
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('order_id')
                    ->relationship('order', 'order_number')
                    ->searchable()
                    ->preload()
                    ->placeholder('すべての注文'),
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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('作成'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('表示'),
                Tables\Actions\EditAction::make()
                    ->label('編集'),
                Tables\Actions\DeleteAction::make()
                    ->label('削除'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('削除'),
                ]),
            ]);
    }
}