<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\ProductImage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('画像情報')
                    ->description('商品画像に関する情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\FileUpload::make('path')
                                    ->image()
                                    ->directory('products/images')
                                    ->visibility('public')
                                    ->imageEditor()
                                    ->required()
                                    ->label('画像ファイル'),
                                Forms\Components\TextInput::make('alt')
                                    ->maxLength(255)
                                    ->label('代替テキスト')
                                    ->placeholder('アクセシビリティのための代替テキスト'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('sort')
                                    ->numeric()
                                    ->minValue(0)
                                    ->label('並び順')
                                    ->placeholder('画像シーケンスでの位置'),
                                Forms\Components\TextInput::make('rank')
                                    ->numeric()
                                    ->minValue(0)
                                    ->label('ランク')
                                    ->placeholder('並び替え用のランク'),
                            ]),
                        Forms\Components\Toggle::make('is_hero')
                            ->label('ヒーロー画像')
                            ->inline(false)
                            ->helperText('商品ごとに1つの画像のみがヒーロー画像になれます'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('path')
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->label('画像')
                    ->circular()
                    ->size(60),
                Tables\Columns\TextColumn::make('alt')
                    ->label('代替テキスト')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\IconColumn::make('is_hero')
                    ->label('ヒーロー')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('sort')
                    ->label('並び順')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rank')
                    ->label('ランク')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_hero')
                    ->label('ヒーロー画像'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('作成日範囲（開始）'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('作成日範囲（終了）'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date)
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        // If this image is marked as hero, ensure no other image for this product is hero
                        if ($data['is_hero'] ?? false) {
                            \App\Models\ProductImage::where('product_id', $this->getOwnerRecord()->id)
                                ->where('is_hero', true)
                                ->update(['is_hero' => false]);
                        }

                        return $data;
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
            ]);
    }
}