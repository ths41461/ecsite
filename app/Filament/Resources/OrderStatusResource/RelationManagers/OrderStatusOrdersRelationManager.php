<?php

namespace App\Filament\Resources\OrderStatusResource\RelationManagers;

use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderStatusOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('注文情報')
                    ->description('基本的な注文情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('order_number')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->label('注文番号'),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'ordered' => '注文済み',
                                        'processing' => '処理中',
                                        'shipped' => '発送済み',
                                        'delivered' => '配達済み',
                                        'canceled' => 'キャンセル',
                                        'refunded' => '返金済み',
                                    ])
                                    ->required()
                                    ->label('ステータス'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('total_yen')
                                    ->required()
                                    ->numeric()
                                    ->prefix('¥')
                                    ->label('合計（¥）'),
                                Forms\Components\TextInput::make('subtotal_yen')
                                    ->required()
                                    ->numeric()
                                    ->prefix('¥')
                                    ->label('小計（¥）'),
                            ]),
                        Forms\Components\Textarea::make('cancel_reason')
                            ->rows(2)
                            ->maxLength(65535)
                            ->label('キャンセル理由')
                            ->helperText('キャンセルの理由（該当する場合）'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_number')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_number')
                    ->label('注文番号')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('顧客')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_yen')
                    ->label('合計')
                    ->formatStateUsing(fn ($state) => '¥' . number_format($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ordered' => 'gray',
                        'processing' => 'info',
                        'shipped' => 'warning',
                        'delivered' => 'success',
                        'canceled' => 'danger',
                        'refunded' => 'secondary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'ordered' => '注文済み',
                        'processing' => '処理中',
                        'shipped' => '発送済み',
                        'delivered' => '配達済み',
                        'canceled' => 'キャンセル',
                        'refunded' => '返金済み',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('ordered_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'ordered' => '注文済み',
                        'processing' => '処理中',
                        'shipped' => '発送済み',
                        'delivered' => '配達済み',
                        'canceled' => 'キャンセル',
                        'refunded' => '返金済み',
                    ])
                    ->placeholder('すべてのステータス'),
                Tables\Filters\Filter::make('ordered_at')
                    ->form([
                        Forms\Components\DatePicker::make('ordered_from')
                            ->label('注文日範囲（開始）'),
                        Forms\Components\DatePicker::make('ordered_until')
                            ->label('注文日範囲（終了）'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['ordered_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('ordered_at', '>=', $date)
                            )
                            ->when(
                                $data['ordered_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('ordered_at', '<=', $date)
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('作成'),
                Tables\Actions\AssociateAction::make()
                    ->label('関連付け'),
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
                    Tables\Actions\DetachBulkAction::make()
                        ->label('解除'),
                ]),
            ]);
    }
}