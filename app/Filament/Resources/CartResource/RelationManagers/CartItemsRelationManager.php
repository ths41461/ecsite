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
                Forms\Components\Section::make('Cart Item Information')
                    ->description('Information about the item in the cart')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('product_variant_id')
                                    ->relationship('variant', 'sku')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live() // Make it reactive
                                    ->label('Product Variant')
                                    ->helperText('Select the product variant for this cart item')
                                    ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                        if ($state) {
                                            // Load the unit price from the selected product variant
                                            $variant = \App\Models\ProductVariant::find($state);
                                            if ($variant) {
                                                $set('unit_price_yen', $variant->price_yen);
                                            }
                                        }
                                    }),
                                Forms\Components\TextInput::make('quantity')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->live() // Make it reactive
                                    ->label('Quantity')
                                    ->helperText('Number of items')
                                    ->afterStateUpdated(function (Forms\Set $set, ?int $state) {
                                        $unitPrice = $set('unit_price_yen');
                                        if ($state !== null && $unitPrice !== null) {
                                            $lineTotal = $state * $unitPrice;
                                            $set('line_total_yen', $lineTotal);
                                        }
                                    }),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('unit_price_yen')
                                    ->required()
                                    ->numeric()
                                    ->prefix('¥')
                                    ->live() // Make it reactive
                                    ->label('Unit Price (¥)')
                                    ->helperText('Price per unit in yen')
                                    ->afterStateUpdated(function (Forms\Set $set, ?int $state, ?array $old) {
                                        $quantity = $set('quantity');
                                        if ($state !== null && $quantity !== null) {
                                            $lineTotal = $state * $quantity;
                                            $set('line_total_yen', $lineTotal);
                                        }
                                    }),
                                Forms\Components\TextInput::make('line_total_yen')
                                    ->required()
                                    ->numeric()
                                    ->prefix('¥')
                                    ->label('Line Total (¥)')
                                    ->helperText('Total price for this line item (calculated)')
                                    ->readOnly()
                                    ->dehydrated(false), // Don't save this field as it's calculated
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
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AttachAction::make()
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