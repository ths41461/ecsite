<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Filament\Resources\ReviewResource\RelationManagers;
use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'ECサイト';

    protected static ?string $navigationLabel = 'レビュー';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('レビュー情報')
                    ->description('レビューに関する基本情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('商品を選択してください'),
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('ユーザーを選択してください（任意）'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('rating')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(5)
                                    ->placeholder('評価（1-5）'),
                                Forms\Components\Toggle::make('approved')
                                    ->label('承認済み')
                                    ->required()
                                    ->inline(false)
                                    ->default(true),
                            ]),
                        Forms\Components\Textarea::make('body')
                            ->required()
                            ->maxLength(65535)
                            ->rows(4)
                            ->placeholder('レビュー内容'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('商品')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Review $record): string => $record->product->brand?->name ?? ''),
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
                    ->falseColor('danger')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('すべての商品'),
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
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListReviews::route('/'),
            'create' => Pages\CreateReview::route('/create'),
            'view' => Pages\ViewReview::route('/{record}'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'レビュー';
    }

    public static function getPluralModelLabel(): string
    {
        return 'レビュー';
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