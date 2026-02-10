<?php

namespace App\Filament\Resources\PaymentStatusResource\RelationManagers;

use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentStatusPaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('支払い情報')
                    ->description('基本的な支払い情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('order_id')
                                    ->relationship('order', 'order_number')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('注文'),
                                Forms\Components\Select::make('provider')
                                    ->options([
                                        'stripe' => 'ストライプ',
                                        'paypal' => 'ペイパル',
                                        'bank_transfer' => '銀行振込',
                                        'cash_on_delivery' => '代金引換',
                                        'other' => 'その他',
                                    ])
                                    ->required()
                                    ->label('プロバイダー'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'one_time' => 'ワンタイム',
                                        'subscription' => '定期購読',
                                        'installment' => '分割払い',
                                    ])
                                    ->required()
                                    ->label('タイプ'),
                                Forms\Components\TextInput::make('amount_yen')
                                    ->required()
                                    ->numeric()
                                    ->prefix('¥')
                                    ->label('金額（¥）'),
                            ]),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => '保留中',
                                'processing' => '処理中',
                                'succeeded' => '成功',
                                'failed' => '失敗',
                                'canceled' => 'キャンセル',
                                'refunded' => '返金済み',
                            ])
                            ->required()
                            ->label('ステータス'),
                        Forms\Components\DateTimePicker::make('processed_at')
                            ->label('処理日時'),
                        Forms\Components\Textarea::make('payload_json')
                            ->rows(4)
                            ->columnSpanFull()
                            ->label('ペイロードデータ')
                            ->helperText('生の支払いプロバイダーデータ'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('注文番号')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('provider')
                    ->label('プロバイダー')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'stripe' => 'ストライプ',
                        'paypal' => 'ペイパル',
                        'bank_transfer' => '銀行振込',
                        'cash_on_delivery' => '代金引換',
                        'other' => 'その他',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    }),
                Tables\Columns\TextColumn::make('type')
                    ->label('タイプ')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'one_time' => 'ワンタイム',
                        'subscription' => '定期購読',
                        'installment' => '分割払い',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    }),
                Tables\Columns\TextColumn::make('amount_yen')
                    ->label('金額')
                    ->formatStateUsing(fn ($state) => '¥' . number_format($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'succeeded' => 'success',
                        'failed' => 'danger',
                        'canceled' => 'danger',
                        'refunded' => 'secondary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending' => '保留中',
                        'processing' => '処理中',
                        'succeeded' => '成功',
                        'failed' => '失敗',
                        'canceled' => 'キャンセル',
                        'refunded' => '返金済み',
                        default => ucfirst($state),
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('processed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => '保留中',
                        'processing' => '処理中',
                        'succeeded' => '成功',
                        'failed' => '失敗',
                        'canceled' => 'キャンセル',
                        'refunded' => '返金済み',
                    ])
                    ->placeholder('すべてのステータス'),
                Tables\Filters\SelectFilter::make('provider')
                    ->options([
                        'stripe' => 'ストライプ',
                        'paypal' => 'ペイパル',
                        'bank_transfer' => '銀行振込',
                        'cash_on_delivery' => '代金引換',
                        'other' => 'その他',
                    ])
                    ->placeholder('すべてのプロバイダー'),
                Tables\Filters\Filter::make('processed_at')
                    ->form([
                        Forms\Components\DatePicker::make('processed_from')
                            ->label('処理日範囲（開始）'),
                        Forms\Components\DatePicker::make('processed_until')
                            ->label('処理日範囲（終了）'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['processed_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('processed_at', '>=', $date)
                            )
                            ->when(
                                $data['processed_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('processed_at', '<=', $date)
                            );
                    }),
                Tables\Filters\Filter::make('amount_yen')
                    ->form([
                        Forms\Components\TextInput::make('min_amount')
                            ->label('最小金額')
                            ->numeric()
                            ->prefix('¥'),
                        Forms\Components\TextInput::make('max_amount')
                            ->label('最大金額')
                            ->numeric()
                            ->prefix('¥'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_amount'],
                                fn (Builder $query, $value): Builder => $query->where('amount_yen', '>=', $value)
                            )
                            ->when(
                                $data['max_amount'],
                                fn (Builder $query, $value): Builder => $query->where('amount_yen', '<=', $value)
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
                ]),
            ]);
    }
}