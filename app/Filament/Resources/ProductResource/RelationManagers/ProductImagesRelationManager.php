<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\ProductImage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ProductImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('path')
                    ->label('Image')
                    ->disk('public')
                    ->directory('product-images')
                    ->visibility('public')
                    ->image()
                    ->imageEditor()
                    ->required(),

                Forms\Components\TextInput::make('alt')
                    ->label('Alt Text')
                    ->maxLength(255),

                Forms\Components\Toggle::make('is_hero')
                    ->label('Hero Image')
                    ->helperText('Only one image can be the hero image'),

                Forms\Components\TextInput::make('rank')
                    ->label('Rank')
                    ->numeric()
                    ->helperText('Lower numbers appear first'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('path')
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->disk('public')
                    ->height(60),

                Tables\Columns\TextColumn::make('alt')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\IconColumn::make('is_hero')
                    ->boolean()
                    ->label('Hero'),

                Tables\Columns\TextColumn::make('rank')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_hero'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        // Ensure only one hero image exists
                        if ($data['is_hero'] ?? false) {
                            // Unset any existing hero images for this product
                            $this->getOwnerRecord()->images()->update(['is_hero' => false]);
                        }

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        // Ensure only one hero image exists
                        if ($data['is_hero'] ?? false) {
                            // Unset any existing hero images for this product (except current record)
                            $currentRecord = $this->getMountedTableActionRecord();
                            $this->getOwnerRecord()->images()
                                ->where('id', '!=', $currentRecord->id)
                                ->update(['is_hero' => false]);
                        }

                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}