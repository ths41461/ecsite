<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductMetricsCurrentResource\Pages;
use App\Filament\Resources\ProductMetricsCurrentResource\RelationManagers;
use App\Models\ProductMetricsCurrent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductMetricsCurrentResource extends Resource
{
    protected static ?string $model = ProductMetricsCurrent::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationGroup = '分析';

    protected static ?string $navigationLabel = '商品メトリクス（現在）';

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('商品メトリクス')
                    ->description('商品の現在のメトリクス')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('商品'),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('units_7d')
                                    ->numeric()
                                    ->minValue(0)
                                    ->label('販売数（7日間）'),
                                Forms\Components\TextInput::make('units_30d')
                                    ->numeric()
                                    ->minValue(0)
                                    ->label('販売数（30日間）'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('conv_rate_pdp')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(1)
                                    ->step(0.01)
                                    ->label('コンバージョン率（PDP）'),
                                Forms\Components\TextInput::make('atc_rate')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(1)
                                    ->step(0.01)
                                    ->label('カート追加率'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('search_ctr')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(1)
                                    ->step(0.01)
                                    ->label('検索クリック率'),
                                Forms\Components\TextInput::make('revenue_30d')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('¥')
                                    ->label('収益（30日間）'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('wishlist_14d')
                                    ->numeric()
                                    ->minValue(0)
                                    ->label('ウィッシュリスト追加数（14日間）'),
                                Forms\Components\TextInput::make('rating_bayes')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->step(0.1)
                                    ->label('ベイズ平均評価'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('freshness_bonus')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(1)
                                    ->step(0.01)
                                    ->label('新鮮度ボーナス'),
                                Forms\Components\TextInput::make('stock')
                                    ->numeric()
                                    ->minValue(0)
                                    ->label('現在の在庫'),
                            ]),
                        Forms\Components\TextInput::make('safety_stock')
                            ->numeric()
                            ->minValue(0)
                            ->label('安全在庫レベル'),
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
                Tables\Columns\TextColumn::make('units_7d')
                    ->label('販売数（7日間）')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('units_30d')
                    ->label('販売数（30日間）')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('conv_rate_pdp')
                    ->label('コンバージョン率')
                    ->formatStateUsing(fn ($state) => number_format($state * 100, 2) . '%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('atc_rate')
                    ->label('カート追加率')
                    ->formatStateUsing(fn ($state) => number_format($state * 100, 2) . '%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating_bayes')
                    ->label('評価')
                    ->formatStateUsing(fn ($state) => number_format($state, 2))
                    ->sortable(),
                Tables\Columns\TextColumn::make('revenue_30d')
                    ->label('収益（30日間）')
                    ->formatStateUsing(fn ($state) => '¥' . number_format($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('units_sold')
                    ->form([
                        Forms\Components\TextInput::make('min_units_7d')
                            ->label('最小販売数（7日間）')
                            ->numeric(),
                        Forms\Components\TextInput::make('max_units_7d')
                            ->label('最大販売数（7日間）')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_units_7d'],
                                fn (Builder $query, $value): Builder => $query->where('units_7d', '>=', $value)
                            )
                            ->when(
                                $data['max_units_7d'],
                                fn (Builder $query, $value): Builder => $query->where('units_7d', '<=', $value)
                            );
                    }),
                Tables\Filters\SelectFilter::make('product.category_id')
                    ->relationship('product.category', 'name')
                    ->label('カテゴリ')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('product.brand_id')
                    ->relationship('product.brand', 'name')
                    ->label('ブランド')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('units_30d', 'desc');
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
            'index' => Pages\ListProductMetricsCurrents::route('/'),
            'create' => Pages\CreateProductMetricsCurrent::route('/create'),
            'view' => Pages\ViewProductMetricsCurrent::route('/{record}'),
            'edit' => Pages\EditProductMetricsCurrent::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return '商品メトリクス（現在）';
    }

    public static function getPluralModelLabel(): string
    {
        return '商品メトリクス（現在）';
    }
}