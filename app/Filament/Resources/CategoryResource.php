<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'ECサイト';

    protected static ?string $navigationLabel = 'カテゴリ';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('カテゴリ情報')
                    ->description('基本的なカテゴリ情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('カテゴリ名を入力してください')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                        if ($operation !== 'create') {
                                            return;
                                        }
                                        $set('slug', Str::slug($state));
                                    })
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('名前から自動生成'),
                                Forms\Components\Select::make('parent_id')
                                    ->label('親カテゴリ')
                                    ->relationship('parent', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('親カテゴリを選択（任意）'),
                                Forms\Components\FileUpload::make('logo')
                                    ->image()
                                    ->directory('categories')
                                    ->visibility('public')
                                    ->imageEditor()
                                    ->maxSize(2048)
                                    ->placeholder('カテゴリロゴをアップロード'),
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->rows(4)
                            ->maxLength(65535)
                            ->placeholder('詳細なカテゴリ説明を入力してください')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('ロゴ')
                    ->circular()
                    ->defaultImageUrl(asset('images/category-placeholder.png'))
                    ->size(50),
                Tables\Columns\TextColumn::make('name')
                    ->label('カテゴリ')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('親カテゴリ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('スラッグ')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('products_count')
                    ->label('商品数')
                    ->counts('products')
                    ->sortable(),
                Tables\Columns\IconColumn::make('deleted_at')
                    ->label('削除済み')
                    ->boolean()
                    ->trueIcon('heroicon-o-trash')
                    ->trueColor('danger')
                    ->falseIcon('heroicon-o-x-circle')
                    ->falseColor('success')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash'),
                Tables\Actions\RestoreAction::make()
                    ->icon('heroicon-o-arrow-path'),
                Tables\Actions\ForceDeleteAction::make()
                    ->icon('heroicon-o-exclamation-triangle'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('products');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CategoryProductsRelationManager::class,
            RelationManagers\CategoryCouponsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'view' => Pages\ViewCategory::route('/{record}'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'カテゴリ';
    }

    public static function getPluralModelLabel(): string
    {
        return 'カテゴリ';
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