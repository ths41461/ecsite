<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderPaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                Forms\Components\Select::make('type')
                    ->options([
                        'payment' => '支払い',
                        'refund' => '返金',
                        'authorization' => 'オーソリゼーション',
                    ])
                    ->required()
                    ->label('タイプ'),
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
                    ])
                    ->required()
                    ->label('ステータス'),
                Forms\Components\Textarea::make('payload_json')
                    ->columnSpanFull()
                    ->label('ペイロードデータ'),
                Forms\Components\DateTimePicker::make('processed_at')
                    ->label('処理日時'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('provider')
            ->columns([
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
                        'payment' => '支払い',
                        'refund' => '返金',
                        'authorization' => 'オーソリゼーション',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    }),
                Tables\Columns\TextColumn::make('amount_yen')
                    ->label('金額')
                    ->formatStateUsing(function ($state) {
                        return '¥' . number_format($state);
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'authorized' => 'info',
                        'captured' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending' => '保留中',
                        'authorized' => '承認済み',
                        'captured' => 'キャプチャ済み',
                        'failed' => '失敗',
                        'refunded' => '返金済み',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('processed_at')
                    ->label('処理日時')
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
            ])
            ->headerActions([
                // Removed CreateAction to redirect to PaymentResource
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('表示')
                    ->url(fn ($record) => '/admin/payments/' . $record->id),
                Tables\Actions\EditAction::make()
                    ->label('編集')
                    ->url(fn ($record) => '/admin/payments/' . $record->id . '/edit'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Removed DeleteBulkAction to maintain data integrity
                ]),
            ]);
    }
}