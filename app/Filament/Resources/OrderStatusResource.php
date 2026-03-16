<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderStatusResource\Pages;
use App\Filament\Resources\OrderStatusResource\RelationManagers;
use App\Models\OrderStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderStatusResource extends Resource
{
    protected static ?string $model = OrderStatus::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = '注文';

    protected static ?string $navigationLabel = '注文ステータス';

    protected static ?int $navigationSort = 16;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('ステータス情報')
                    ->description('注文ステータスに関する情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->required()
                                    ->maxLength(50)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('ステータスコードを入力してください（例：pending, paid）')
                                    ->helperText('このステータスのユニークコード')
                                    ->formatStateUsing(fn (string $state) => match ($state) {
                                        'pending' => '保留中',
                                        'paid' => '支払済み',
                                        'fulfilled' => '履行済み',
                                        'cancelled' => 'キャンセル',
                                        'refunded' => '返金済み',
                                        default => $state,
                                    }),
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('ステータス名を入力してください（例：Pending, Paid）')
                                    ->helperText('このステータスの表示名')
                                    ->formatStateUsing(fn (string $state) => match ($state) {
                                        'Pending' => '保留中',
                                        'Paid' => '支払済み',
                                        'Fulfilled' => '履行済み',
                                        'Cancelled' => 'キャンセル',
                                        'Refunded' => '返金済み',
                                        default => $state,
                                    }),
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->maxLength(65535)
                            ->placeholder('このステータスの説明を入力してください')
                            ->helperText('このステータスを説明するオプションの説明'),
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
                Tables\Columns\TextColumn::make('code')
                    ->label('コード')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending' => '保留中',
                        'paid' => '支払済み',
                        'fulfilled' => '履行済み',
                        'cancelled' => 'キャンセル',
                        'refunded' => '返金済み',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('name')
                    ->label('名前')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'Pending' => '保留中',
                        'Paid' => '支払済み',
                        'Fulfilled' => '履行済み',
                        'Cancelled' => 'キャンセル',
                        'Refunded' => '返金済み',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->label('説明')
                    ->limit(50)
                    ->sortable(),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('注文数')
                    ->counts('orders')
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
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OrderStatusOrdersRelationManager::class,
            RelationManagers\OrderStatusFromHistoriesRelationManager::class,
            RelationManagers\OrderStatusToHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrderStatuses::route('/'),
            'create' => Pages\CreateOrderStatus::route('/create'),
            'view' => Pages\ViewOrderStatus::route('/{record}'),
            'edit' => Pages\EditOrderStatus::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return '注文ステータス';
    }

    public static function getPluralModelLabel(): string
    {
        return '注文ステータス';
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