<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Info')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                if ($operation !== 'create') {
                                    return;
                                }
                                $set('slug', Str::slug($state));
                            }),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('brand_id')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured')
                            ->default(false),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->maxLength(65535),
                        Forms\Components\Textarea::make('short_description')
                            ->rows(2)
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Price & Sale')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('¥')
                            ->required()
                            ->helperText('Price in yen'),
                        Forms\Components\TextInput::make('sale_price')
                            ->numeric()
                            ->prefix('¥')
                            ->helperText('Sale price in yen'),
                        Forms\Components\DatePicker::make('sale_start_date'),
                        Forms\Components\DatePicker::make('sale_end_date'),
                    ])->columns(2),

                Forms\Components\Section::make('Inventory & SKU')
                    ->schema([
                        Forms\Components\TextInput::make('sku')
                            ->maxLength(255)
                            ->helperText('Base SKU for this product'),
                    ]),

                Forms\Components\Section::make('Variants (volume-based)')
                    ->schema([
                        Forms\Components\Repeater::make('variants')
                            ->relationship('variants')
                            ->schema([
                                Forms\Components\TextInput::make('sku')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\KeyValue::make('option_json')
                                    ->label('Options')
                                    ->keyLabel('Option Type')
                                    ->valueLabel('Option Value')
                                    ->addable()
                                    ->deletable()
                                    ->helperText('For volume-based variants, use "size_ml" as the key and the volume in ml as the value'),
                                Forms\Components\TextInput::make('price_yen')
                                    ->label('Price (¥)')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0),
                                Forms\Components\TextInput::make('sale_price_yen')
                                    ->label('Sale Price (¥)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->helperText('Leave empty if not on sale'),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->createItemButtonLabel('Add Variant')
                            ->collapsible(),
                    ]),

                Forms\Components\Section::make('Fragrance Profile')
                    ->schema([
                        Forms\Components\KeyValue::make('attributes_json')
                            ->label('Attributes')
                            ->keyLabel('Attribute')
                            ->valueLabel('Value')
                            ->helperText('Fragrance-specific attributes like notes, concentration, etc.'),
                    ]),

                Forms\Components\Section::make('Images')
                    ->schema([
                        Forms\Components\FileUpload::make('images')
                            ->multiple()
                            ->directory('products')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->maxFiles(10)
                            ->helperText('Upload product images'),
                    ]),

                Forms\Components\Section::make('SEO')
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('meta_description')
                            ->rows(3)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('meta_keywords')
                            ->maxLength(255)
                            ->helperText('Comma separated keywords'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('images')
                    ->label('Thumb')
                    ->circular()
                    ->defaultImageUrl(asset('images/product-placeholder.png'))
                    ->size(40),
                Tables\Columns\TextColumn::make('name')
                    ->description(fn (Product $record): ?string => $record->slug)
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price/Sale')
                    ->formatStateUsing(function (Product $record) {
                        if ($record->sale_price) {
                            return '¥' . number_format($record->price) . ' → ¥' . number_format($record->sale_price);
                        }
                        return '¥' . number_format($record->price);
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('variants.sku')
                    ->label('SKU')
                    ->badge()
                    ->separator(',')
                    ->limit(3)
                    ->tooltip(function ($state): ?string {
                        if (is_array($state) && count($state) > 3) {
                            return implode(', ', array_slice($state, 3));
                        }
                        return null;
                    }),
                Tables\Columns\TextColumn::make('category.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured'),
                Tables\Columns\TextColumn::make('stock_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in_stock' => 'success',
                        'low_stock' => 'warning',
                        'out_of_stock' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty')
                    ->numeric(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('brand_id')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_featured'),
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\SelectFilter::make('stock_status')
                    ->options([
                        'in_stock' => 'In Stock',
                        'low_stock' => 'Low Stock',
                        'out_of_stock' => 'Out of Stock',
                    ]),
                Tables\Filters\TernaryFilter::make('has_variants'),
                Tables\Filters\Filter::make('price')
                    ->form([
                        Forms\Components\TextInput::make('min_price')
                            ->label('Min Price')
                            ->numeric(),
                        Forms\Components\TextInput::make('max_price')
                            ->label('Max Price')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_price'],
                                fn (Builder $query, $value): Builder => $query->where('price', '>=', $value)
                            )
                            ->when(
                                $data['max_price'],
                                fn (Builder $query, $value): Builder => $query->where('price', '<=', $value)
                            );
                    }),
            ])
            ->actions([
                Action::make('preview')
                    ->label('Preview PDP')
                    ->url(fn (Product $record): string => route('products.show', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProductVariantsRelationManager::class,
            RelationManagers\ProductImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}