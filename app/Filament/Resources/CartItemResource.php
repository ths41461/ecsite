<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CartItemResource\Pages;
use App\Filament\Resources\CartItemResource\RelationManagers;
use App\Models\CartItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CartItemResource extends Resource
{
    protected static ?string $model = CartItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'ECサイト';

    protected static ?string $navigationLabel = 'カートアイテム';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('カートアイテム情報')
                    ->description('カート内のアイテムに関する情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('cart_id')
                                    ->relationship('cart', 'id')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('カート'),
                                Forms\Components\Select::make('product_variant_id')
                                    ->relationship('variant', 'sku')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('商品バリエーション'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('quantity')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->label('数量'),
                                Forms\Components\TextInput::make('unit_price_yen')
                                    ->required()
                                    ->numeric()
                                    ->prefix('¥')
                                    ->label('単価（¥）'),
                            ]),
                        Forms\Components\TextInput::make('line_total_yen')
                            ->required()
                            ->numeric()
                            ->prefix('¥')
                            ->label('小計（¥）')
                            ->helperText('数量 × 単価で計算されます'),
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
                Tables\Columns\TextColumn::make('cart.id')
                    ->label('カートID')
                    ->searchable()
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
                Tables\Filters\SelectFilter::make('cart_id')
                    ->relationship('cart', 'id')
                    ->searchable()
                    ->preload()
                    ->placeholder('すべてのカート'),
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
            ->defaultSort('updated_at', 'desc');
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
            'index' => Pages\ListCartItems::route('/'),
            'create' => Pages\CreateCartItem::route('/create'),
            'view' => Pages\ViewCartItem::route('/{record}'),
            'edit' => Pages\EditCartItem::route('/{record}/edit'),
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