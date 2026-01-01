<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryResource\Pages;
use App\Filament\Resources\InventoryResource\RelationManagers;
use App\Models\Inventory;
use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryResource extends Resource
{
    protected static ?string $model = Inventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'E-commerce';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Inventory';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Inventory Information')
                    ->description('Manage product variant inventory')
                    ->schema([
                        Forms\Components\Select::make('product_variant_id')
                            ->label('Product Variant')
                            ->relationship('variant', 'sku')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Select the product variant this inventory record is for'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('stock')
                                    ->label('Current Stock')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->helperText('Current available stock quantity'),

                                Forms\Components\TextInput::make('safety_stock')
                                    ->label('Safety Stock')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->helperText('Minimum stock level before reordering'),
                            ]),

                        Forms\Components\Toggle::make('managed')
                            ->label('Inventory Managed')
                            ->default(true)
                            ->helperText('Whether this inventory is actively managed (stock will be decremented on order)'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('variant.sku')
                    ->label('Variant SKU')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Inventory $record) => $record->variant?->product?->name),

                Tables\Columns\TextColumn::make('variant.product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock')
                    ->label('Current Stock')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 5 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('safety_stock')
                    ->label('Safety Stock')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in_stock' => 'success',
                        'low_stock' => 'warning',
                        'out_of_stock' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(function (Inventory $record) {
                        if ($record->stock <= 0) {
                            return 'out_of_stock';
                        } elseif ($record->stock <= $record->safety_stock) {
                            return 'low_stock';
                        }
                        return 'in_stock';
                    }),

                Tables\Columns\IconColumn::make('managed')
                    ->label('Managed')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('managed')
                    ->options([
                        true => 'Managed',
                        false => 'Not Managed',
                    ])
                    ->label('Inventory Management Status'),

                Tables\Filters\SelectFilter::make('stock_status')
                    ->options([
                        'in_stock' => 'In Stock',
                        'low_stock' => 'Low Stock', 
                        'out_of_stock' => 'Out of Stock',
                    ])
                    ->label('Stock Status')
                    ->query(function (Builder $query, array $data) {
                        return match ($data['value']) {
                            'in_stock' => $query->where('stock', '>', 0)->whereColumn('stock', '>', 'safety_stock'),
                            'low_stock' => $query->where('stock', '>', 0)->whereColumn('stock', '<=', 'safety_stock'),
                            'out_of_stock' => $query->where('stock', 0),
                            default => $query,
                        };
                    }),

                Tables\Filters\Filter::make('stock')
                    ->form([
                        Forms\Components\TextInput::make('min_stock')
                            ->label('Min Stock')
                            ->numeric()
                            ->placeholder('0'),
                        Forms\Components\TextInput::make('max_stock')
                            ->label('Max Stock')
                            ->numeric()
                            ->placeholder('9999'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_stock'],
                                fn (Builder $query, $value): Builder => $query->where('stock', '>=', $value)
                            )
                            ->when(
                                $data['max_stock'],
                                fn (Builder $query, $value): Builder => $query->where('stock', '<=', $value)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventories::route('/'),
            'create' => Pages\CreateInventory::route('/create'),
            'view' => Pages\ViewInventory::route('/{record}'),
            'edit' => Pages\EditInventory::route('/{record}/edit'),
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