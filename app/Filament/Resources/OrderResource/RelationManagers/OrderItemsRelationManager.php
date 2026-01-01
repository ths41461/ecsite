<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\OrderItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('product_variant_id')
                    ->relationship('variant', 'sku')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Forms\Components\TextInput::make('name_snapshot')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('sku_snapshot')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('unit_price_yen')
                    ->required()
                    ->numeric()
                    ->prefix('¥'),
                Forms\Components\TextInput::make('qty')
                    ->required()
                    ->numeric()
                    ->minValue(1),
                Forms\Components\TextInput::make('line_total_yen')
                    ->required()
                    ->numeric()
                    ->prefix('¥'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name_snapshot')
            ->columns([
                Tables\Columns\TextColumn::make('name_snapshot')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sku_snapshot')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_price_yen')
                    ->label('Unit Price')
                    ->formatStateUsing(function ($state) {
                        return '¥' . number_format($state);
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('qty')
                    ->label('Quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('line_total_yen')
                    ->label('Line Total')
                    ->formatStateUsing(function ($state) {
                        return '¥' . number_format($state);
                    })
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AttachAction::make(),
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