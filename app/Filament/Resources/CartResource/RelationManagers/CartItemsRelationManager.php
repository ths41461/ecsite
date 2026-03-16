<?php

namespace App\Filament\Resources\CartResource\RelationManagers;

use App\Models\CartItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CartItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('カートアイテム情報')
                    ->description('カート内のアイテムに関する情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('product_variant_id')
                                    ->relationship('variant', 'sku')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('商品バリエーション')
                                    ->helperText('このカートアイテムの商品バリエーションを選択してください'),
                                Forms\Components\TextInput::make('quantity')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->label('数量'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('unit_price_yen')
                                    ->required()
                                    ->numeric()
                                    ->prefix('¥')
                                    ->label('単価（¥）')
                                    ->helperText('1つあたりの価格（円）'),
                                Forms\Components\TextInput::make('line_total_yen')
                                    ->required()
                                    ->numeric()
                                    ->prefix('¥')
                                    ->label('小計（¥）')
                                    ->helperText('数量 × 単価で計算されます'),
                            ]),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('variant.sku')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('variant.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('variant.product.name')
                    ->label('商品')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('数量')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_price_yen')
                    ->label('単価')
                    ->formatStateUsing(fn ($state) => '¥' . number_format($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('line_total_yen')
                    ->label('小計')
                    ->formatStateUsing(fn ($state) => '¥' . number_format($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product_variant_id')
                    ->relationship('variant', 'sku')
                    ->searchable()
                    ->preload()
                    ->placeholder('すべてのバリエーション'),
                Tables\Filters\Filter::make('quantity')
                    ->form([
                        Forms\Components\TextInput::make('min_quantity')
                            ->label('最小数量')
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\TextInput::make('max_quantity')
                            ->label('最大数量')
                            ->numeric()
                            ->minValue(1),
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
                Tables\Filters\Filter::make('unit_price_yen')
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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AssociateAction::make()
                    ->multiple()
                    ->preloadRecordSelect(),
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