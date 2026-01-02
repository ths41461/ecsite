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

    protected static ?string $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Metrics Information')
                    ->description('Daily metrics for the product')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('Product'),
                                Forms\Components\DatePicker::make('date')
                                    ->required()
                                    ->label('Date'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('views')
                                    ->numeric()
                                    ->minValue(0)
                                    ->label('Views'),
                                Forms\Components\TextInput::make('atc_count')
                                    ->label('Add-to-Cart Count')
                                    ->numeric()
                                    ->minValue(0),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('orders_count')
                                    ->label('Orders Count')
                                    ->numeric()
                                    ->minValue(0),
                                Forms\Components\TextInput::make('revenue_yen')
                                    ->label('Revenue (¥)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('¥')
                                    ->helperText('Revenue in yen'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('search_impressions')
                                    ->label('Search Impressions')
                                    ->numeric()
                                    ->minValue(0),
                                Forms\Components\TextInput::make('search_clicks')
                                    ->label('Search Clicks')
                                    ->numeric()
                                    ->minValue(0),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('wishlist_adds')
                                    ->label('Wishlist Adds')
                                    ->numeric()
                                    ->minValue(0),
                                Forms\Components\TextInput::make('rating_avg')
                                    ->label('Average Rating')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->step(0.1)
                                    ->helperText('Average rating on a 5-point scale'),
                            ]),
                        Forms\Components\TextInput::make('rating_count')
                            ->label('Rating Count')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Number of ratings received'),
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
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('views')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('atc_count')
                    ->label('ATC Count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('revenue_yen')
                    ->label('Revenue (¥)')
                    ->formatStateUsing(fn ($state) => '¥' . number_format($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('search_impressions')
                    ->label('Search Imp.')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('search_clicks')
                    ->label('Search Clicks')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('wishlist_adds')
                    ->label('Wishlist Adds')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating_avg')
                    ->label('Avg Rating')
                    ->formatStateUsing(fn ($state) => number_format($state, 2))
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating_count')
                    ->label('Rating Count')
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
                            ->label('Date From'),
                        Forms\Components\DatePicker::make('date_until')
                            ->label('Date Until'),
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
                    ->label('Category'),
                Tables\Filters\SelectFilter::make('product.brand_id')
                    ->relationship('product.brand', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Brand'),
                Tables\Filters\Filter::make('revenue_yen')
                    ->form([
                        Forms\Components\TextInput::make('min_revenue')
                            ->label('Min Revenue')
                            ->numeric()
                            ->prefix('¥'),
                        Forms\Components\TextInput::make('max_revenue')
                            ->label('Max Revenue')
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

    public static function can(string $action, $record = null): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->isAdmin();
    }
}