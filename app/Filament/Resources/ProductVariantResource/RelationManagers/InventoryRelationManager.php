<?php

namespace App\Filament\Resources\ProductVariantResource\RelationManagers;

use App\Models\Inventory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryRelationManager extends RelationManager
{
    protected static string $relationship = 'inventory';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_variant_id')
            ->columns([
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
                    ->label('Stock Status'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}