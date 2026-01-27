<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentTransactionResource\Pages;
use App\Filament\Resources\PaymentTransactionResource\RelationManagers;
use App\Models\PaymentTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentTransactionResource extends Resource
{
    protected static ?string $model = PaymentTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = '注文';

    protected static ?string $navigationLabel = '支払い取引';

    protected static ?int $navigationSort = 18;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('取引情報')
                    ->description('支払い取引に関する情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('payment_id')
                                    ->relationship('payment', 'id')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('支払い'),
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
                                Forms\Components\TextInput::make('external_id')
                                    ->maxLength(255)
                                    ->label('外部ID')
                                    ->placeholder('支払いプロバイダーからのID'),
                                Forms\Components\TextInput::make('currency')
                                    ->maxLength(3)
                                    ->default('JPY')
                                    ->label('通貨'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount_yen')
                                    ->required()
                                    ->numeric()
                                    ->prefix('¥')
                                    ->label('金額（¥）'),
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
                            ]),
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment.order.order_number')
                    ->label('注文番号')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => route('filament.admin.resources.payments.edit', ['record' => $record->payment_id]))
                    ->openUrlInNewTab(false),
                Tables\Columns\TextColumn::make('provider')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('external_id')
                    ->label('外部ID')
                    ->searchable()
                    ->sortable(),
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
                    ->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state)))
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
            'index' => Pages\ListPaymentTransactions::route('/'),
            'create' => Pages\CreatePaymentTransaction::route('/create'),
            'view' => Pages\ViewPaymentTransaction::route('/{record}'),
            'edit' => Pages\EditPaymentTransaction::route('/{record}/edit'),
        ];
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