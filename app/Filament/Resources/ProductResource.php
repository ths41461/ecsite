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

    protected static ?string $navigationGroup = 'ECサイト';

    protected static ?string $navigationLabel = '商品';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本情報')
                    ->description('基本的な商品情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('商品名を入力してください')
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
                                    ->placeholder('名前から自動生成'),
                                Forms\Components\Select::make('brand_id')
                                    ->relationship('brand', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('ブランドを選択'),
                                Forms\Components\Select::make('category_id')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('カテゴリを選択'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('有効')
                                    ->default(true)
                                    ->inline(false),
                                Forms\Components\Toggle::make('is_featured')
                                    ->label('特集')
                                    ->default(false)
                                    ->inline(false),
                            ]),
                        Forms\Components\Textarea::make('short_description')
                            ->rows(2)
                            ->maxLength(255)
                            ->placeholder('商品の簡単な説明'),
                        Forms\Components\Textarea::make('description')
                            ->rows(4)
                            ->maxLength(65535)
                            ->placeholder('商品の詳細な説明')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('価格設定')
                    ->description('商品の価格と販売情報を設定')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->numeric()
                                    ->prefix('¥')
                                    ->required()
                                    ->placeholder('0.00')
                                    ->helperText('基本価格（円）'),
                                Forms\Components\TextInput::make('sale_price')
                                    ->numeric()
                                    ->prefix('¥')
                                    ->placeholder('0.00')
                                    ->helperText('セール価格（円）（オプション）'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('sale_start_date')
                                    ->label('セール開始日'),
                                Forms\Components\DatePicker::make('sale_end_date')
                                    ->label('セール終了日'),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('在庫とSKU')
                    ->description('商品の在庫とSKU情報')
                    ->schema([
                        Forms\Components\TextInput::make('sku')
                            ->maxLength(255)
                            ->placeholder('この商品の基本SKUを入力')
                            ->helperText('この商品の基本SKU'),
                    ]),

                Forms\Components\Section::make('バリエーション（容量ベース）')
                    ->description('異なるサイズやオプションを持つ商品バリエーションを管理')
                    ->schema([
                        Forms\Components\Repeater::make('variants')
                            ->relationship('variants')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('sku')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('バリエーションSKUを入力'),
                                        Forms\Components\KeyValue::make('option_json')
                                            ->label('オプション')
                                            ->keyLabel('オプションタイプ')
                                            ->valueLabel('オプション値')
                                            ->addable()
                                            ->deletable()
                                            ->helperText('容量ベースのバリエーションには、キーとして「size_ml」、値としてml単位の容量を使用してください'),
                                    ]),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('price_yen')
                                            ->label('価格（¥）')
                                            ->numeric()
                                            ->required()
                                            ->minValue(0)
                                            ->placeholder('0.00'),
                                        Forms\Components\TextInput::make('sale_price_yen')
                                            ->label('セール価格（¥）')
                                            ->numeric()
                                            ->minValue(0)
                                            ->placeholder('0.00')
                                            ->helperText('セールでない場合は空のままにしてください'),
                                    ]),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('有効')
                                    ->default(true)
                                    ->inline(false),
                            ])
                            ->createItemButtonLabel('バリエーション追加')
                            ->collapsible()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('香りプロファイル')
                    ->description('ノート、濃度などの香り固有の属性')
                    ->schema([
                        Forms\Components\KeyValue::make('attributes_json')
                            ->label('属性')
                            ->keyLabel('属性')
                            ->valueLabel('値')
                            ->addable()
                            ->deletable()
                            ->helperText('ノート、濃度などの香り固有の属性')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('画像')
                    ->description('商品画像をアップロード')
                    ->schema([
                        Forms\Components\FileUpload::make('images')
                            ->multiple()
                            ->directory('products')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->maxFiles(10)
                            ->placeholder('商品画像をアップロード')
                            ->helperText('最大10枚の商品画像をアップロード'),
                    ]),

                Forms\Components\Section::make('SEO情報')
                    ->description('検索エンジン最適化情報')
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')
                            ->maxLength(255)
                            ->placeholder('この商品のSEOタイトル'),
                        Forms\Components\Textarea::make('meta_description')
                            ->rows(3)
                            ->maxLength(255)
                            ->placeholder('この商品のSEO説明'),
                        Forms\Components\TextInput::make('meta_keywords')
                            ->maxLength(255)
                            ->placeholder('カンマ区切りのキーワード')
                            ->helperText('カンマ区切りのキーワード'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('images')
                    ->label('画像')
                    ->circular()
                    ->defaultImageUrl(asset('images/product-placeholder.png'))
                    ->size(50),
                Tables\Columns\TextColumn::make('name')
                    ->label('商品')
                    ->description(fn (Product $record): ?string => $record->slug)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('ブランド')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('カテゴリ')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('価格')
                    ->formatStateUsing(function (Product $record) {
                        if ($record->sale_price) {
                            return '¥' . number_format($record->price) . ' → ¥' . number_format($record->sale_price);
                        }
                        return '¥' . number_format($record->price);
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('variants.sku')
                    ->label('バリエーション')
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
                    ->label('有効')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('特集')
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
                    ->placeholder('すべてのカテゴリ'),
                Tables\Filters\SelectFilter::make('brand_id')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('すべてのブランド'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('特集'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('有効'),
                Tables\Filters\SelectFilter::make('stock_status')
                    ->options([
                        'in_stock' => '在庫あり',
                        'low_stock' => '在庫わずか',
                        'out_of_stock' => '在庫なし',
                    ])
                    ->placeholder('すべての在庫状況'),
                Tables\Filters\TernaryFilter::make('has_variants')
                    ->label('バリエーションあり'),
                Tables\Filters\Filter::make('price')
                    ->form([
                        Forms\Components\TextInput::make('min_price')
                            ->label('最小価格')
                            ->numeric()
                            ->placeholder('¥0'),
                        Forms\Components\TextInput::make('max_price')
                            ->label('最大価格')
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
                    ->label('プレビュー')
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
            RelationManagers\ProductEventsRelationManager::class,
            RelationManagers\ProductWishlistRelationManager::class,
            \App\Filament\Resources\OrderItemResource\RelationManagers\OrderItemsRelationManager::class,
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

    public static function getModelLabel(): string
    {
        return '商品';
    }

    public static function getPluralModelLabel(): string
    {
        return '商品';
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