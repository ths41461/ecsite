<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Filament\Resources\EventResource\RelationManagers;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationGroup = '分析';

    protected static ?string $navigationLabel = 'イベント';

    protected static ?int $navigationSort = 16;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('イベント情報')
                    ->description('ユーザー行動イベントに関する情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->label('商品（任意）')
                                    ->placeholder('該当する場合は商品を選択してください'),
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->label('ユーザー（任意）')
                                    ->placeholder('ログインしている場合はユーザーを選択してください'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('user_hash')
                                    ->maxLength(255)
                                    ->label('ユーザー識別子')
                                    ->placeholder('匿名ユーザー識別子'),
                                Forms\Components\Select::make('event_type')
                                    ->options([
                                        'product_view' => '商品表示',
                                        'add_to_cart' => 'カート追加',
                                        'remove_from_cart' => 'カート削除',
                                        'checkout_start' => 'チェックアウト開始',
                                        'checkout_complete' => 'チェックアウト完了',
                                        'search' => '検索',
                                        'category_view' => 'カテゴリ表示',
                                        'brand_view' => 'ブランド表示',
                                        'wishlist_add' => 'ウィッシュリスト追加',
                                        'wishlist_remove' => 'ウィッシュリスト削除',
                                    ])
                                    ->required()
                                    ->label('イベントタイプ'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('value')
                                    ->numeric()
                                    ->minValue(0)
                                    ->label('値（任意）')
                                    ->placeholder('イベントに関連する数値'),
                                Forms\Components\DateTimePicker::make('occurred_at')
                                    ->required()
                                    ->label('発生日時'),
                            ]),
                        Forms\Components\Textarea::make('meta_json')
                            ->rows(4)
                            ->columnSpanFull()
                            ->label('メタデータ')
                            ->helperText('JSON形式の追加イベントメタデータ'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('商品')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('ユーザー')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_hash')
                    ->label('ユーザー識別子')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('event_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'product_view' => 'info',
                        'add_to_cart' => 'warning',
                        'remove_from_cart' => 'gray',
                        'checkout_start' => 'primary',
                        'checkout_complete' => 'success',
                        'search' => 'secondary',
                        'category_view' => 'purple',
                        'brand_view' => 'amber',
                        'wishlist_add' => 'pink',
                        'wishlist_remove' => 'red',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'product_view' => '商品表示',
                        'add_to_cart' => 'カート追加',
                        'remove_from_cart' => 'カート削除',
                        'checkout_start' => 'チェックアウト開始',
                        'checkout_complete' => 'チェックアウト完了',
                        'search' => '検索',
                        'category_view' => 'カテゴリ表示',
                        'brand_view' => 'ブランド表示',
                        'wishlist_add' => 'ウィッシュリスト追加',
                        'wishlist_remove' => 'ウィッシュリスト削除',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('value')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('occurred_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event_type')
                    ->options([
                        'product_view' => '商品表示',
                        'add_to_cart' => 'カート追加',
                        'remove_from_cart' => 'カート削除',
                        'checkout_start' => 'チェックアウト開始',
                        'checkout_complete' => 'チェックアウト完了',
                        'search' => '検索',
                        'category_view' => 'カテゴリ表示',
                        'brand_view' => 'ブランド表示',
                        'wishlist_add' => 'ウィッシュリスト追加',
                        'wishlist_remove' => 'ウィッシュリスト削除',
                    ])
                    ->placeholder('すべてのイベントタイプ'),
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
                Tables\Filters\Filter::make('occurred_at')
                    ->form([
                        Forms\Components\DatePicker::make('occurred_from')
                            ->label('発生日範囲（開始）'),
                        Forms\Components\DatePicker::make('occurred_until')
                            ->label('発生日範囲（終了）'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['occurred_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('occurred_at', '>=', $date)
                            )
                            ->when(
                                $data['occurred_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('occurred_at', '<=', $date)
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
            ->defaultSort('occurred_at', 'desc');
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
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'view' => Pages\ViewEvent::route('/{record}'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'イベント';
    }

    public static function getPluralModelLabel(): string
    {
        return 'イベント';
    }

    public static function can(string $action, $record = null): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->isAdmin() || $user->isStaff();
    }
}