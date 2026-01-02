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

    protected static ?string $navigationGroup = 'E-commerce';

    protected static ?int $navigationSort = 19;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Cart Item Information')
                    ->description('Information about the item in the cart')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('cart_id')
                                    ->relationship('cart', 'id')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('Cart'),
                                Forms\Components\Select::make('product_variant_id')
                                    ->relationship('variant', 'sku')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('Product Variant')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $variant = \App\Models\ProductVariant::find($state);
                                            if ($variant) {
                                                $set('unit_price_yen', $variant->price_yen);
                                                $set('line_total_yen', $variant->price_yen); // Will be updated when quantity changes
                                            }
                                        }
                                    }),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('quantity')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->label('Quantity')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        $unitPrice = $get('unit_price_yen');
                                        if ($unitPrice && $state) {
                                            $set('line_total_yen', $unitPrice * $state);
                                        }
                                    }),
                                Forms\Components\TextInput::make('unit_price_yen')
                                    ->required()
                                    ->numeric()
                                    ->prefix('¥')
                                    ->label('Unit Price (¥)')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        $quantity = $get('quantity');
                                        if ($quantity && $state) {
                                            $set('line_total_yen', $quantity * $state);
                                        }
                                    }),
                            ]),
                        Forms\Components\TextInput::make('line_total_yen')
                            ->required()
                            ->numeric()
                            ->prefix('¥')
                            ->label('Line Total (¥)')
                            ->readOnly()
                            ->helperText('Automatically calculated (quantity × unit price)'),
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
                    ->label('Cart ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('variant.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('variant.product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_price_yen')
                    ->label('Unit Price')
                    ->formatStateUsing(fn ($state) => '¥' . number_format($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('line_total_yen')
                    ->label('Line Total')
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
                    ->placeholder('All Carts'),
                Tables\Filters\SelectFilter::make('product_variant_id')
                    ->relationship('variant', 'sku')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Variants'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
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