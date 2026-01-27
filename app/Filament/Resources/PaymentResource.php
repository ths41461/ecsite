<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = '注文';

    protected static ?string $navigationLabel = '支払い';

    protected static ?int $navigationSort = 16;

    public static function form(Form $form): Form
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
                                        'auth' => 'オーソリゼーション',
                                        'capture' => 'キャプチャ',
                                        'refund' => '返金',
                                        'one_time' => 'ワンタイム',
                                        'subscription' => '定期購読',
                                        'installment' => '分割払い',
                                    ])
                                    ->required()
                                    ->default('auth')
                                    ->label('タイプ'),
                                Forms\Components\TextInput::make('amount_yen')
                                    ->required()
                                    ->numeric()
                                    ->prefix('¥')
                                    ->label('金額（¥）'),
                            ]),
                        Forms\Components\Select::make('status')
                            ->options([
                                'created' => '作成済み',
                                'pending' => '保留中',
                                'approved' => '承認済み',
                                'declined' => '拒否',
                                'refunded' => '返金済み',
                                'voided' => '無効',
                                'expired' => '期限切れ',
                            ])
                            ->required()
                            ->default('pending')
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
                Tables\Columns\TextColumn::make('provider')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('type')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('amount_yen')
                    ->label('金額')
                    ->formatStateUsing(fn ($state) => '¥' . number_format($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'declined' => 'danger',
                        'refunded' => 'secondary',
                        'voided' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state)))
                    ->sortable(),
                Tables\Columns\TextColumn::make('paymentStatus.name')
                    ->label('支払いステータス')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Authorized' => 'info',
                        'Captured' => 'success',
                        'Failed' => 'danger',
                        'Refunded' => 'secondary',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('processed_at')
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
                        'created' => '作成済み',
                        'pending' => '保留中',
                        'approved' => '承認済み',
                        'declined' => '拒否',
                        'refunded' => '返金済み',
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
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'auth' => 'オーソリゼーション',
                        'capture' => 'キャプチャ',
                        'refund' => '返金',
                        'one_time' => 'ワンタイム',
                        'subscription' => '定期購読',
                        'installment' => '分割払い',
                    ])
                    ->placeholder('すべてのタイプ'),
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
            ->defaultSort('processed_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PaymentTransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
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