<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductMetricsDailyResource\Pages;
use App\Filament\Resources\ProductMetricsDailyResource\RelationManagers;
use App\Models\ProductMetricsDaily;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductMetricsDailyResource extends Resource
{
    protected static ?string $model = ProductMetricsDaily::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = '分析';

    protected static ?string $navigationLabel = '商品メトリクス（日次）';

    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('商品メトリクス情報')
                    ->description('商品の日次メトリクス')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('商品'),
                                Forms\Components\DatePicker::make('date')
                                    ->required()
                                    ->label('日付'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('views')
                                    ->numeric()
                                    ->minValue(0)
                                    ->label('表示回数'),
                                Forms\Components\TextInput::make('atc_count')
                                    ->label('カート追加数')
                                    ->numeric()
                                    ->minValue(0),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('orders_count')
                                    ->label('注文件数')
                                    ->numeric()
                                    ->minValue(0),
                                Forms\Components\TextInput::make('revenue_yen')
                                    ->label('収益（¥）')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('¥')
                                    ->helperText('円単位の収益'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('search_impressions')
                                    ->label('検索インプレッション')
                                    ->numeric()
                                    ->minValue(0),
                                Forms\Components\TextInput::make('search_clicks')
                                    ->label('検索クリック数')
                                    ->numeric()
                                    ->minValue(0),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('wishlist_adds')
                                    ->label('ウィッシュリスト追加数')
                                    ->numeric()
                                    ->minValue(0),
                                Forms\Components\TextInput::make('rating_avg')
                                    ->label('平均評価')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->step(0.1)
                                    ->helperText('5点満点での平均評価'),
                            ]),
                        Forms\Components\TextInput::make('rating_count')
                            ->label('評価件数')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('受信した評価の数'),
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
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('views')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('atc_count')
                    ->label('カート追加数')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('注文数')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('revenue_yen')
                    ->label('収益（¥）')
                    ->formatStateUsing(fn ($state) => '¥' . number_format($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('search_impressions')
                    ->label('検索インプレッション')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('search_clicks')
                    ->label('検索クリック数')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('wishlist_adds')
                    ->label('ウィッシュリスト追加数')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating_avg')
                    ->label('平均評価')
                    ->formatStateUsing(fn ($state) => number_format($state, 2))
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating_count')
                    ->label('評価件数')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('日付範囲（開始）'),
                        Forms\Components\DatePicker::make('date_until')
                            ->label('日付範囲（終了）'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date)
                            )
                            ->when(
                                $data['date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date)
                            );
                    }),
                Tables\Filters\SelectFilter::make('product.category_id')
                    ->relationship('product.category', 'name')
                    ->searchable()
                    ->preload()
                    ->label('カテゴリ'),
                Tables\Filters\SelectFilter::make('product.brand_id')
                    ->relationship('product.brand', 'name')
                    ->searchable()
                    ->preload()
                    ->label('ブランド'),
                Tables\Filters\Filter::make('revenue_yen')
                    ->form([
                        Forms\Components\TextInput::make('min_revenue')
                            ->label('最小収益')
                            ->numeric()
                            ->prefix('¥'),
                        Forms\Components\TextInput::make('max_revenue')
                            ->label('最大収益')
                            ->numeric()
                            ->prefix('¥'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_revenue'],
                                fn (Builder $query, $value): Builder => $query->where('revenue_yen', '>=', $value)
                            )
                            ->when(
                                $data['max_revenue'],
                                fn (Builder $query, $value): Builder => $query->where('revenue_yen', '<=', $value)
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
            ->defaultSort('date', 'desc');
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
            'index' => Pages\ListProductMetricsDailies::route('/'),
            'create' => Pages\CreateProductMetricsDaily::route('/create'),
            'view' => Pages\ViewProductMetricsDaily::route('/{record}'),
            'edit' => Pages\EditProductMetricsDaily::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return '商品メトリクス（日次）';
    }

    public static function getPluralModelLabel(): string
    {
        return '商品メトリクス（日次）';
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