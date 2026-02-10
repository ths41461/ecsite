<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderStatusHistoryResource\Pages;
use App\Filament\Resources\OrderStatusHistoryResource\RelationManagers;
use App\Models\OrderStatusHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderStatusHistoryResource extends Resource
{
    protected static ?string $model = OrderStatusHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = '注文';

    protected static ?string $navigationLabel = '注文ステータス履歴';

    protected static ?int $navigationSort = 17;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('ステータス履歴情報')
                    ->description('注文ステータス変更に関する情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('order_id')
                                    ->relationship('order', 'order_number')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('注文'),
                                Forms\Components\Select::make('from_status_id')
                                    ->relationship('fromStatus', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('変更前ステータス'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('to_status_id')
                                    ->relationship('toStatus', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('変更後ステータス'),
                                Forms\Components\Select::make('changed_by')
                                    ->relationship('changedByUser', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->label('変更者（ユーザーID）')
                                    ->helperText('ステータスを変更したユーザー'),
                            ]),
                        Forms\Components\DateTimePicker::make('changed_at')
                            ->required()
                            ->label('変更日時'),
                        Forms\Components\Textarea::make('note')
                            ->rows(3)
                            ->maxLength(65535)
                            ->label('備考')
                            ->helperText('ステータス変更に関するオプションの備考'),
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
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('注文番号')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fromStatus.name')
                    ->label('変更前ステータス')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '保留中' => 'gray',
                        '処理中' => 'info',
                        '発送済み' => 'warning',
                        '配達済み' => 'success',
                        'キャンセル' => 'danger',
                        '返金済み' => 'secondary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'Pending' => '保留中',
                        'Processing' => '処理中',
                        'Shipped' => '発送済み',
                        'Delivered' => '配達済み',
                        'Cancelled' => 'キャンセル',
                        'Refunded' => '返金済み',
                        default => $state,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('toStatus.name')
                    ->label('変更後ステータス')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '保留中' => 'gray',
                        '処理中' => 'info',
                        '発送済み' => 'warning',
                        '配達済み' => 'success',
                        'キャンセル' => 'danger',
                        '返金済み' => 'secondary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'Pending' => '保留中',
                        'Processing' => '処理中',
                        'Shipped' => '発送済み',
                        'Delivered' => '配達済み',
                        'Cancelled' => 'キャンセル',
                        'Refunded' => '返金済み',
                        default => $state,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('changedByUser.name')
                    ->label('変更者')
                    ->searchable()
                    ->sortable()
                    ->placeholder('システム'),
                Tables\Columns\TextColumn::make('changed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('order_id')
                    ->relationship('order', 'order_number')
                    ->searchable()
                    ->preload()
                    ->placeholder('すべての注文'),
                Tables\Filters\SelectFilter::make('from_status_id')
                    ->relationship('fromStatus', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('すべての変更前ステータス'),
                Tables\Filters\SelectFilter::make('to_status_id')
                    ->relationship('toStatus', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('すべての変更後ステータス'),
                Tables\Filters\Filter::make('changed_at')
                    ->form([
                        Forms\Components\DatePicker::make('changed_from')
                            ->label('変更日範囲（開始）'),
                        Forms\Components\DatePicker::make('changed_until')
                            ->label('変更日範囲（終了）'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['changed_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('changed_at', '>=', $date)
                            )
                            ->when(
                                $data['changed_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('changed_at', '<=', $date)
                            );
                    }),
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
            ->defaultSort('changed_at', 'desc');
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
            'index' => Pages\ListOrderStatusHistories::route('/'),
            'create' => Pages\CreateOrderStatusHistory::route('/create'),
            'view' => Pages\ViewOrderStatusHistory::route('/{record}'),
            'edit' => Pages\EditOrderStatusHistory::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return '注文ステータス履歴';
    }

    public static function getPluralModelLabel(): string
    {
        return '注文ステータス履歴';
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