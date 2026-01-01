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

    protected static ?string $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Metrics')
                    ->description('Current metrics for the product')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Product'),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('units_7d')
                                    ->numeric()
                                    ->minValue(0)
                                    ->label('Units Sold (7 Days)'),
                                Forms\Components\TextInput::make('units_30d')
                                    ->numeric()
                                    ->minValue(0)
                                    ->label('Units Sold (30 Days)'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('conv_rate_pdp')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(1)
                                    ->step(0.01)
                                    ->label('Conversion Rate (PDP)'),
                                Forms\Components\TextInput::make('atc_rate')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(1)
                                    ->step(0.01)
                                    ->label('Add-to-Cart Rate'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('search_ctr')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(1)
                                    ->step(0.01)
                                    ->label('Search Click-Through Rate'),
                                Forms\Components\TextInput::make('revenue_30d')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('¥')
                                    ->label('Revenue (30 Days)'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('wishlist_14d')
                                    ->numeric()
                                    ->minValue(0)
                                    ->label('Wishlist Additions (14 Days)'),
                                Forms\Components\TextInput::make('rating_bayes')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->step(0.1)
                                    ->label('Bayesian Average Rating'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('freshness_bonus')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(1)
                                    ->step(0.01)
                                    ->label('Freshness Bonus'),
                                Forms\Components\TextInput::make('stock')
                                    ->numeric()
                                    ->minValue(0)
                                    ->label('Current Stock'),
                            ]),
                        Forms\Components\TextInput::make('safety_stock')
                            ->numeric()
                            ->minValue(0)
                            ->label('Safety Stock Level'),
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
                Tables\Columns\TextColumn::make('units_7d')
                    ->label('Units (7d)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('units_30d')
                    ->label('Units (30d)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('conv_rate_pdp')
                    ->label('Conv Rate')
                    ->formatStateUsing(fn ($state) => number_format($state * 100, 2) . '%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('atc_rate')
                    ->label('ATC Rate')
                    ->formatStateUsing(fn ($state) => number_format($state * 100, 2) . '%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating_bayes')
                    ->label('Rating')
                    ->formatStateUsing(fn ($state) => number_format($state, 2))
                    ->sortable(),
                Tables\Columns\TextColumn::make('revenue_30d')
                    ->label('Revenue (30d)')
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
                            ->label('Min Units (7d)')
                            ->numeric(),
                        Forms\Components\TextInput::make('max_units_7d')
                            ->label('Max Units (7d)')
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
                    ->label('Category')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('product.brand_id')
                    ->relationship('product.brand', 'name')
                    ->label('Brand')
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
}