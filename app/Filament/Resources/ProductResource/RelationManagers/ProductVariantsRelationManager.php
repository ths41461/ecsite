<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductVariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('sku')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Forms\Components\KeyValue::make('option_json')
                    ->label('オプション')
                    ->keyLabel('オプションタイプ')
                    ->valueLabel('オプション値')
                    ->addable()
                    ->deletable()
                    ->helperText('容量ベースのバリエーションには、「size_ml」をキーとして、ml単位の容量を値として使用してください'),

                Forms\Components\TextInput::make('price_yen')
                    ->label('価格（¥）')
                    ->numeric()
                    ->required()
                    ->minValue(0),

                Forms\Components\TextInput::make('sale_price_yen')
                    ->label('セール価格（¥）')
                    ->numeric()
                    ->minValue(0)
                    ->helperText('セールでない場合は空のままにしてください'),

                Forms\Components\Toggle::make('is_active')
                    ->label('有効')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([
                Tables\Columns\TextColumn::make('sku')
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
                    ->label('価格（¥）')
                    ->formatStateUsing(fn($state) => '¥' . number_format($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('sale_price_yen')
                    ->label('セール価格（¥）')
                    ->formatStateUsing(fn($state) => $state > 0 ? '¥' . number_format($state) : 'セール対象外')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('inventory.stock')
                    ->label('在庫')
                    ->getStateUsing(function (ProductVariant $record) {
                        return $record->inventory ? $record->inventory->stock : '在庫なし';
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->options([
                        true => '有効',
                        false => '無効',
                    ]),
                Tables\Filters\TernaryFilter::make('on_sale'),
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