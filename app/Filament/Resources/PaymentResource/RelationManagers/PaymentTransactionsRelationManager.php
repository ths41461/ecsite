<?php

namespace App\Filament\Resources\PaymentResource\RelationManagers;

use App\Models\PaymentTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('取引情報')
                    ->description('支払い取引に関する情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('ext_id')
                                    ->maxLength(255)
                                    ->label('外部ID')
                                    ->placeholder('支払いプロバイダーからのID'),
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
                                Forms\Components\TextInput::make('amount_yen')
                                    ->required()
                                    ->numeric()
                                    ->prefix('¥')
                                    ->label('金額（¥）'),
                                Forms\Components\Select::make('currency')
                                    ->options([
                                        'JPY' => '日本円（¥）',
                                        'USD' => '米ドル（$）',
                                        'EUR' => 'ユーロ（€）',
                                        'GBP' => '英ポンド（£）',
                                    ])
                                    ->default('JPY')
                                    ->label('通貨'),
                            ]),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => '保留中',
                                'authorized' => '承認済み',
                                'captured' => 'キャプチャ済み',
                                'failed' => '失敗',
                                'refunded' => '返金済み',
                                'partial_refund' => '一部返金',
                                'voided' => '無効',
                                'expired' => '期限切れ',
                            ])
                            ->required()
                            ->label('ステータス'),
                        Forms\Components\DateTimePicker::make('occurred_at')
                            ->required()
                            ->label('発生日時'),
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
            ->recordTitleAttribute('ext_id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ext_id')
                    ->label('外部ID')
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
                Tables\Columns\TextColumn::make('amount_yen')
                    ->label('金額')
                    ->formatStateUsing(fn ($state) => '¥' . number_format($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'authorized' => 'info',
                        'captured' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'secondary',
                        'partial_refund' => 'warning',
                        'voided' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending' => '保留中',
                        'authorized' => '承認済み',
                        'captured' => 'キャプチャ済み',
                        'failed' => '失敗',
                        'refunded' => '返金済み',
                        'partial_refund' => '一部返金',
                        'voided' => '無効',
                        'expired' => '期限切れ',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('occurred_at')
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
                        'authorized' => '承認済み',
                        'captured' => 'キャプチャ済み',
                        'failed' => '失敗',
                        'refunded' => '返金済み',
                        'partial_refund' => '一部返金',
                        'voided' => '無効',
                        'expired' => '期限切れ',
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
                Tables\Filters\Filter::make('amount_yen')
                    ->form([
                        Forms\Components\TextInput::make('min_amount')
                            ->label('最小金額'),
                        Forms\Components\TextInput::make('max_amount')
                            ->label('最大金額'),
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