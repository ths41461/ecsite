<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductImageResource\Pages;
use App\Filament\Resources\ProductImageResource\RelationManagers;
use App\Models\ProductImage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductImageResource extends Resource
{
    protected static ?string $model = ProductImage::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'ECサイト';

    protected static ?string $navigationLabel = '商品画像';

    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('画像情報')
                    ->description('商品画像に関する情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('商品'),
                                Forms\Components\FileUpload::make('path')
                                    ->image()
                                    ->directory('products/images')
                                    ->visibility('public')
                                    ->imageEditor()
                                    ->required()
                                    ->label('画像ファイル'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('alt')
                                    ->maxLength(255)
                                    ->label('代替テキスト')
                                    ->placeholder('アクセシビリティのための代替テキスト'),
                                Forms\Components\TextInput::make('sort')
                                    ->numeric()
                                    ->minValue(0)
                                    ->label('並び順')
                                    ->placeholder('画像シーケンスでの位置'),
                            ]),
                        Forms\Components\Toggle::make('is_hero')
                            ->label('ヒーロー画像')
                            ->inline(false)
                            ->helperText('商品ごとに1つの画像のみがヒーロー画像になれます'),
                        Forms\Components\TextInput::make('rank')
                            ->numeric()
                            ->minValue(0)
                            ->label('ランク')
                            ->placeholder('並び替え用のランク'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->label('画像')
                    ->circular()
                    ->size(60),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('商品')
                    ->searchable()
                    ->sortable(),
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
            ->defaultSort('sort', 'asc');
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
            'index' => Pages\ListProductImages::route('/'),
            'create' => Pages\CreateProductImage::route('/create'),
            'view' => Pages\ViewProductImage::route('/{record}'),
            'edit' => Pages\EditProductImage::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return '商品画像';
    }

    public static function getPluralModelLabel(): string
    {
        return '商品画像';
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