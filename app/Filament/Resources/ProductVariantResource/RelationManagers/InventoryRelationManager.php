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
                            ->label('現在の在庫')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->helperText('現在利用可能な在庫数量'),

                        Forms\Components\TextInput::make('safety_stock')
                            ->label('安全在庫')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->helperText('再注文前の最低在庫レベル'),
                    ]),

                Forms\Components\Toggle::make('managed')
                    ->label('在庫管理')
                    ->default(true)
                    ->helperText('この在庫が積極的に管理されているかどうか（注文時に在庫が減少します）'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_variant_id')
            ->columns([
                Tables\Columns\TextColumn::make('stock')
                    ->label('現在の在庫')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 5 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('safety_stock')
                    ->label('安全在庫')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_status')
                    ->label('ステータス')
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
                    ->label('管理対象')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('managed')
                    ->options([
                        true => '管理対象',
                        false => '非管理対象',
                    ])
                    ->label('在庫管理ステータス'),

                Tables\Filters\SelectFilter::make('stock_status')
                    ->options([
                        'in_stock' => '在庫あり',
                        'low_stock' => '在庫わずか',
                        'out_of_stock' => '在庫なし',
                    ])
                    ->label('在庫ステータス'),
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