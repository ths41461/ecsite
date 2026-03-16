<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Filament\Resources\BrandResource\RelationManagers;
use App\Models\Brand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'ECサイト';

    protected static ?string $navigationLabel = 'ブランド';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('ブランド情報')
                    ->description('基本的なブランド情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('ブランド名を入力してください')
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
                                Forms\Components\FileUpload::make('logo')
                                    ->image()
                                    ->directory('brands')
                                    ->visibility('public')
                                    ->imageEditor()
                                    ->maxSize(2048)
                                    ->placeholder('ブランドロゴをアップロード'),
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->rows(4)
                            ->maxLength(65535)
                            ->placeholder('詳細なブランド説明を入力してください')
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
                    ->defaultImageUrl(asset('images/brand-placeholder.png'))
                    ->size(50),
                Tables\Columns\TextColumn::make('name')
                    ->label('ブランド')
                    ->searchable()
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\BrandProductsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('products');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'view' => Pages\ViewBrand::route('/{record}'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'ブランド';
    }

    public static function getPluralModelLabel(): string
    {
        return 'ブランド';
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