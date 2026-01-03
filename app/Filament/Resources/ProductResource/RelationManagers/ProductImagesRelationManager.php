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
                Forms\Components\Section::make('Image Information')
                    ->description('Information about the product image')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\FileUpload::make('path')
                                    ->image()
                                    ->directory('products/images')
                                    ->visibility('public')
                                    ->imageEditor()
                                    ->required()
                                    ->label('Image File'),
                                Forms\Components\TextInput::make('alt')
                                    ->maxLength(255)
                                    ->label('Alt Text')
                                    ->placeholder('Alternative text for accessibility'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('sort')
                                    ->numeric()
                                    ->minValue(0)
                                    ->label('Sort Order')
                                    ->placeholder('Position in image sequence'),
                                Forms\Components\TextInput::make('rank')
                                    ->numeric()
                                    ->minValue(0)
                                    ->label('Rank')
                                    ->placeholder('Rank for ordering'),
                            ]),
                        Forms\Components\Toggle::make('is_hero')
                            ->label('Hero Image')
                            ->inline(false)
                            ->helperText('Only one image per product can be the hero image'),
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
                    ->label('Image')
                    ->circular()
                    ->size(60),
                Tables\Columns\TextColumn::make('alt')
                    ->label('Alt Text')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\IconColumn::make('is_hero')
                    ->label('Hero')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('sort')
                    ->label('Sort')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rank')
                    ->label('Rank')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_hero')
                    ->label('Hero Image'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
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