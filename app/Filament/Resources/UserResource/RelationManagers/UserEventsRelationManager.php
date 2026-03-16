<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('商品を選択'),
                Forms\Components\TextInput::make('user_hash')
                    ->maxLength(255)
                    ->placeholder('ユーザーのハッシュを入力（匿名ユーザー用）'),
                Forms\Components\Select::make('event_type')
                    ->options([
                        'view' => '表示',
                        'add_to_cart' => 'カート追加',
                        'remove_from_cart' => 'カート削除',
                        'checkout_start' => '購入手続き開始',
                        'checkout_complete' => '購入完了',
                        'search' => '検索',
                        'filter' => 'フィルター',
                        'sort' => 'ソート',
                        'wishlist_add' => 'ウィッシュリスト追加',
                        'wishlist_remove' => 'ウィッシュリスト削除',
                        'review' => 'レビュー',
                        'share' => '共有',
                    ])
                    ->required()
                    ->searchable()
                    ->placeholder('イベントタイプを選択'),
                Forms\Components\TextInput::make('value')
                    ->numeric()
                    ->step('0.01')
                    ->placeholder('値を入力（任意）'),
                Forms\Components\DateTimePicker::make('occurred_at')
                    ->required()
                    ->placeholder('発生日時を選択'),
                Forms\Components\Textarea::make('meta_json')
                    ->columnSpanFull()
                    ->rows(4)
                    ->placeholder('メタデータをJSON形式で入力（任意）'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event_type')
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('商品')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_hash')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('event_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'view' => 'info',
                        'add_to_cart' => 'warning',
                        'remove_from_cart' => 'gray',
                        'checkout_start' => 'primary',
                        'checkout_complete' => 'success',
                        'search' => 'secondary',
                        'filter' => 'purple',
                        'sort' => 'indigo',
                        'wishlist_add' => 'pink',
                        'wishlist_remove' => 'red',
                        'review' => 'green',
                        'share' => 'blue',
                        default => 'secondary',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('value')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('occurred_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event_type')
                    ->options([
                        'view' => '表示',
                        'add_to_cart' => 'カート追加',
                        'remove_from_cart' => 'カート削除',
                        'checkout_start' => '購入手続き開始',
                        'checkout_complete' => '購入完了',
                        'search' => '検索',
                        'filter' => 'フィルター',
                        'sort' => 'ソート',
                        'wishlist_add' => 'ウィッシュリスト追加',
                        'wishlist_remove' => 'ウィッシュリスト削除',
                        'review' => 'レビュー',
                        'share' => '共有',
                    ])
                    ->placeholder('すべてのイベントタイプ'),
                Tables\Filters\SelectFilter::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('すべての商品'),
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
                                fn (Builder $query, $date): Builder => $query->whereDate('occurred_at', '>=', $date),
                            )
                            ->when(
                                $data['occurred_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('occurred_at', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('作成'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('表示'),
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
            ->defaultSort('occurred_at', 'desc');
    }
}