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

    protected static ?string $navigationGroup = 'ECサイト';

    protected static ?string $navigationLabel = '商品バリエーション';

    protected static ?int $navigationSort = 14;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本情報')
                    ->description('基本的な商品バリエーション情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('商品'),
                                Forms\Components\TextInput::make('sku')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->label('SKU')
                                    ->placeholder('このバリエーションのユニークSKUを入力してください'),
                            ]),
                        Forms\Components\KeyValue::make('option_json')
                            ->label('オプション')
                            ->keyLabel('オプションタイプ')
                            ->valueLabel('オプション値')
                            ->addable()
                            ->deletable()
                            ->helperText('商品バリエーションオプション（例：サイズ、色、香り）'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('価格情報')
                    ->description('このバリエーションの価格情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('price_yen')
                                    ->required()
                                    ->numeric()
                                    ->prefix('¥')
                                    ->label('価格（¥）')
                                    ->placeholder('0.00')
                                    ->helperText('円単位の価格'),
                                Forms\Components\TextInput::make('sale_price_yen')
                                    ->numeric()
                                    ->prefix('¥')
                                    ->label('セール価格（¥）')
                                    ->placeholder('0.00')
                                    ->helperText('セール価格（円単位）（任意）'),
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

                Forms\Components\Section::make('ステータス情報')
                    ->description('ステータスと可用性設定')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('有効')
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
                    ->label('商品')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('option_json')
                    ->label('オプション')
                    ->formatStateUsing(function ($state) {
                        if (!is_array($state)) {
                            return '該当なし';
                        }

                        $options = [];
                        foreach ($state as $key => $value) {
                            $options[] = "{$key}: {$value}";
                        }

                        return implode(', ', $options);
                    })
                    ->limit(50),
                Tables\Columns\TextColumn::make('price_yen')
                    ->label('価格')
                    ->formatStateUsing(fn ($state) => '¥' . number_format($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_price_yen')
                    ->label('セール価格')
                    ->formatStateUsing(fn ($state) => $state ? '¥' . number_format($state) : '-')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('有効')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('inventory.qty')
                    ->label('在庫')
                    ->getStateUsing(function (ProductVariant $record) {
                        return $record->inventory ? $record->inventory->qty : '在庫なし';
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
                    ->placeholder('すべての商品'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('有効'),
                Tables\Filters\TernaryFilter::make('on_sale')
                    ->label('セール中')
                    ->query(function (Builder $query) {
                        return $query->whereNotNull('sale_price_yen')->where('sale_price_yen', '>', 0);
                    }),
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

    public static function getModelLabel(): string
    {
        return '商品バリエーション';
    }

    public static function getPluralModelLabel(): string
    {
        return '商品バリエーション';
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