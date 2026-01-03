<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductVariantResource\Pages;
use App\Filament\Resources\ProductVariantResource\RelationManagers;
use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductVariantResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'E-commerce';

    protected static ?int $navigationSort = 14;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->description('Basic product variant information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('Product'),
                                Forms\Components\TextInput::make('sku')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->label('SKU')
                                    ->placeholder('Enter unique SKU for this variant'),
                            ]),
                        Forms\Components\KeyValue::make('option_json')
                            ->label('Options')
                            ->keyLabel('Option Type')
                            ->valueLabel('Option Value')
                            ->addable()
                            ->deletable()
                            ->helperText('Product variant options (e.g., size, color, scent)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pricing Information')
                    ->description('Pricing information for this variant')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('price_yen')
                                    ->required()
                                    ->numeric()
                                    ->prefix('¥')
                                    ->label('Price (¥)')
                                    ->placeholder('0.00')
                                    ->helperText('Price in yen'),
                                Forms\Components\TextInput::make('sale_price_yen')
                                    ->numeric()
                                    ->prefix('¥')
                                    ->label('Sale Price (¥)')
                                    ->placeholder('0.00')
                                    ->helperText('Sale price in yen (optional)'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('sale_start_date')
                                    ->label('Sale Start Date'),
                                Forms\Components\DatePicker::make('sale_end_date')
                                    ->label('Sale End Date'),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status Information')
                    ->description('Status and availability settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false),
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
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('option_json')
                    ->label('Options')
                    ->formatStateUsing(function ($state) {
                        if (!is_array($state)) {
                            return 'N/A';
                        }

                        $options = [];
                        foreach ($state as $key => $value) {
                            $options[] = "{$key}: {$value}";
                        }

                        return implode(', ', $options);
                    })
                    ->limit(50),
                Tables\Columns\TextColumn::make('price_yen')
                    ->label('Price')
                    ->formatStateUsing(fn ($state) => '¥' . number_format($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_price_yen')
                    ->label('Sale Price')
                    ->formatStateUsing(fn ($state) => $state ? '¥' . number_format($state) : '-')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('inventory.qty')
                    ->label('Stock')
                    ->getStateUsing(function (ProductVariant $record) {
                        return $record->inventory ? $record->inventory->qty : 'No inventory';
                    })
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
                Tables\Filters\SelectFilter::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Products'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\TernaryFilter::make('on_sale')
                    ->label('On Sale')
                    ->query(function (Builder $query) {
                        return $query->whereNotNull('sale_price_yen')->where('sale_price_yen', '>', 0);
                    }),
                Tables\Filters\Filter::make('price_range')
                    ->form([
                        Forms\Components\TextInput::make('min_price')
                            ->label('Min Price')
                            ->numeric()
                            ->prefix('¥'),
                        Forms\Components\TextInput::make('max_price')
                            ->label('Max Price')
                            ->numeric()
                            ->prefix('¥'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_price'],
                                fn (Builder $query, $value): Builder => $query->where('price_yen', '>=', $value)
                            )
                            ->when(
                                $data['max_price'],
                                fn (Builder $query, $value): Builder => $query->where('price_yen', '<=', $value)
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
            ->defaultSort('product.name', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InventoryRelationManager::class,
            RelationManagers\OrderItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductVariants::route('/'),
            'create' => Pages\CreateProductVariant::route('/create'),
            'view' => Pages\ViewProductVariant::route('/{record}'),
            'edit' => Pages\EditProductVariant::route('/{record}/edit'),
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