<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CartResource\Pages;
use App\Filament\Resources\CartResource\RelationManagers;
use App\Models\Cart;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CartResource extends Resource
{
    protected static ?string $model = Cart::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'ECサイト';

    protected static ?string $navigationLabel = 'カート';

    protected static ?int $navigationSort = 19;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('カート情報')
                    ->description('基本的なカート情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('id')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->label('カートID')
                                    ->placeholder('カートの一意識別子'),
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('ユーザーを選択（ゲストカートの場合は空のままにしてください）')
                                    ->label('ユーザー（任意）'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('session_id')
                                    ->maxLength(255)
                                    ->label('セッションID')
                                    ->placeholder('ゲストカートのセッション識別子'),
                                Forms\Components\TextInput::make('currency')
                                    ->maxLength(3)
                                    ->default('JPY')
                                    ->label('通貨'),
                            ]),
                        Forms\Components\TextInput::make('ttl_minutes')
                            ->numeric()
                            ->minValue(1)
                            ->label('有効期限（分）')
                            ->placeholder('カートの有効期限（分）'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('有効')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('カートID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('ユーザー')
                    ->searchable()
                    ->sortable()
                    ->placeholder('ゲスト'),
                Tables\Columns\TextColumn::make('session_id')
                    ->label('セッションID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('商品数')
                    ->counts('items')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('合計金額')
                    ->formatStateUsing(fn ($record) => '¥' . number_format($record->getTotalAmountAttribute()))
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('有効')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
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
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('有効'),
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
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CartItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCarts::route('/'),
            'create' => Pages\CreateCart::route('/create'),
            'view' => Pages\ViewCart::route('/{record}'),
            'edit' => Pages\EditCart::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'カート';
    }

    public static function getPluralModelLabel(): string
    {
        return 'カート';
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