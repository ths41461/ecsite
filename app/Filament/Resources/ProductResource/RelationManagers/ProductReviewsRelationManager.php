<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviews';

    protected static ?string $recordTitleAttribute = 'body';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('ユーザーを選択（任意）'),
                Forms\Components\TextInput::make('rating')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->placeholder('評価（1-5）'),
                Forms\Components\Textarea::make('body')
                    ->required()
                    ->maxLength(65535)
                    ->rows(4)
                    ->placeholder('レビュー内容'),
                Forms\Components\Toggle::make('approved')
                    ->required()
                    ->inline(false)
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('ユーザー')
                    ->searchable()
                    ->sortable()
                    ->placeholder('ゲスト'),
                Tables\Columns\TextColumn::make('rating')
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        5 => 'success',
                        4 => 'info',
                        3 => 'warning',
                        2 => 'warning',
                        1 => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state) . str_repeat('☆', 5 - $state)),
                Tables\Columns\TextColumn::make('body')
                    ->label('レビュー')
                    ->limit(50),
                Tables\Columns\IconColumn::make('approved')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('すべてのユーザー'),
                Tables\Filters\TernaryFilter::make('approved')
                    ->label('承認済み'),
                Tables\Filters\SelectFilter::make('rating')
                    ->options([
                        '1' => '1つ星',
                        '2' => '2つ星',
                        '3' => '3つ星',
                        '4' => '4つ星',
                        '5' => '5つ星',
                    ])
                    ->placeholder('すべての評価'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('作成'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('編集'),
                Tables\Actions\DeleteAction::make()
                    ->label('削除'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('削除'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}