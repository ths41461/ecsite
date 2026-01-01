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

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'E-commerce';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->description('Basic product information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter product name')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                        if ($operation !== 'create') {
                                            return;
                                        }
                                        $set('slug', Str::slug($state));
                                    })
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Auto-generated from name'),
                                Forms\Components\Select::make('brand_id')
                                    ->relationship('brand', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('Select a brand'),
                                Forms\Components\Select::make('category_id')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('Select a category'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->inline(false),
                                Forms\Components\Toggle::make('is_featured')
                                    ->label('Featured')
                                    ->default(false)
                                    ->inline(false),
                            ]),
                        Forms\Components\Textarea::make('short_description')
                            ->rows(2)
                            ->maxLength(255)
                            ->placeholder('Brief description of the product'),
                        Forms\Components\Textarea::make('description')
                            ->rows(4)
                            ->maxLength(65535)
                            ->placeholder('Detailed description of the product')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pricing')
                    ->description('Set product pricing and sale information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->numeric()
                                    ->prefix('¥')
                                    ->required()
                                    ->placeholder('0.00')
                                    ->helperText('Base price in yen'),
                                Forms\Components\TextInput::make('sale_price')
                                    ->numeric()
                                    ->prefix('¥')
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

                Forms\Components\Section::make('Inventory & SKU')
                    ->description('Product inventory and SKU information')
                    ->schema([
                        Forms\Components\TextInput::make('sku')
                            ->maxLength(255)
                            ->placeholder('Enter base SKU for this product')
                            ->helperText('Base SKU for this product'),
                    ]),

                Forms\Components\Section::make('Variants (volume-based)')
                    ->description('Manage product variants with different sizes or options')
                    ->schema([
                        Forms\Components\Repeater::make('variants')
                            ->relationship('variants')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('sku')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Enter variant SKU'),
                                        Forms\Components\KeyValue::make('option_json')
                                            ->label('Options')
                                            ->keyLabel('Option Type')
                                            ->valueLabel('Option Value')
                                            ->addable()
                                            ->deletable()
                                            ->helperText('For volume-based variants, use "size_ml" as the key and the volume in ml as the value'),
                                    ]),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('price_yen')
                                            ->label('Price (¥)')
                                            ->numeric()
                                            ->required()
                                            ->minValue(0)
                                            ->placeholder('0.00'),
                                        Forms\Components\TextInput::make('sale_price_yen')
                                            ->label('Sale Price (¥)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->placeholder('0.00')
                                            ->helperText('Leave empty if not on sale'),
                                    ]),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->inline(false),
                            ])
                            ->createItemButtonLabel('Add Variant')
                            ->collapsible()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Fragrance Profile')
                    ->description('Fragrance-specific attributes like notes, concentration, etc.')
                    ->schema([
                        Forms\Components\KeyValue::make('attributes_json')
                            ->label('Attributes')
                            ->keyLabel('Attribute')
                            ->valueLabel('Value')
                            ->addable()
                            ->deletable()
                            ->helperText('Fragrance-specific attributes like notes, concentration, etc.')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Images')
                    ->description('Upload product images')
                    ->schema([
                        Forms\Components\FileUpload::make('images')
                            ->multiple()
                            ->directory('products')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->maxFiles(10)
                            ->placeholder('Upload product images')
                            ->helperText('Upload up to 10 product images'),
                    ]),

                Forms\Components\Section::make('SEO Information')
                    ->description('Search engine optimization information')
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')
                            ->maxLength(255)
                            ->placeholder('SEO title for this product'),
                        Forms\Components\Textarea::make('meta_description')
                            ->rows(3)
                            ->maxLength(255)
                            ->placeholder('SEO description for this product'),
                        Forms\Components\TextInput::make('meta_keywords')
                            ->maxLength(255)
                            ->placeholder('Comma separated keywords')
                            ->helperText('Comma separated keywords'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('images')
                    ->label('Image')
                    ->circular()
                    ->defaultImageUrl(asset('images/product-placeholder.png'))
                    ->size(50),
                Tables\Columns\TextColumn::make('name')
                    ->label('Product')
                    ->description(fn (Product $record): ?string => $record->slug)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Brand')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->formatStateUsing(function (Product $record) {
                        if ($record->sale_price) {
                            return '¥' . number_format($record->price) . ' → ¥' . number_format($record->sale_price);
                        }
                        return '¥' . number_format($record->price);
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('variants.sku')
                    ->label('Variants')
                    ->badge()
                    ->separator(',')
                    ->limit(3)
                    ->tooltip(function ($state): ?string {
                        if (is_array($state) && count($state) > 3) {
                            return implode(', ', array_slice($state, 3));
                        }
                        return null;
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueColor('warning')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('stock_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in_stock' => 'success',
                        'low_stock' => 'warning',
                        'out_of_stock' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Categories'),
                Tables\Filters\SelectFilter::make('brand_id')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Brands'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\SelectFilter::make('stock_status')
                    ->options([
                        'in_stock' => 'In Stock',
                        'low_stock' => 'Low Stock',
                        'out_of_stock' => 'Out of Stock',
                    ])
                    ->placeholder('All Stock Statuses'),
                Tables\Filters\TernaryFilter::make('has_variants')
                    ->label('Has Variants'),
                Tables\Filters\Filter::make('price')
                    ->form([
                        Forms\Components\TextInput::make('min_price')
                            ->label('Min Price')
                            ->numeric()
                            ->placeholder('¥0'),
                        Forms\Components\TextInput::make('max_price')
                            ->label('Max Price')
                            ->numeric()
                            ->placeholder('¥999999'),
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
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Product $record): string => route('products.show', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash'),
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
            RelationManagers\ProductVariantsRelationManager::class,
            RelationManagers\ProductImagesRelationManager::class,
            RelationManagers\ProductReviewsRelationManager::class,
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