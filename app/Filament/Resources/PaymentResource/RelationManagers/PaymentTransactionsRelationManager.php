<?php

namespace App\Filament\Resources\PaymentResource\RelationManagers;

use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'paymentTransactions';

    protected static ?string $recordTitleAttribute = 'status';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('provider')
                    ->options([
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'bank_transfer' => 'Bank Transfer',
                        'cash_on_delivery' => 'Cash on Delivery',
                        'other' => 'Other',
                        'mock' => 'Mock',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('ext_id')
                    ->label('External ID')
                    ->maxLength(191),
                Forms\Components\TextInput::make('amount_yen')
                    ->label('Amount (¥)')
                    ->numeric()
                    ->prefix('¥'),
                Forms\Components\TextInput::make('currency')
                    ->maxLength(10)
                    ->default('JPY'),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'authorized' => 'Authorized',
                        'captured' => 'Captured',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                        'approved' => 'Approved',
                        'declined' => 'Declined',
                        'voided' => 'Voided',
                        'created' => 'Created',
                        'succeeded' => 'Succeeded',
                        'payment_failed' => 'Payment Failed',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('payload_json')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('occurred_at'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->columns([
                Tables\Columns\TextColumn::make('provider')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ext_id')
                    ->label('External ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_yen')
                    ->label('Amount')
                    ->formatStateUsing(function ($state) {
                        return $state ? '¥' . number_format($state) : 'N/A';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending', 'created' => 'warning',
                        'authorized' => 'info',
                        'captured', 'approved', 'succeeded' => 'success',
                        'failed', 'declined', 'payment_failed' => 'danger',
                        'refunded' => 'gray',
                        'voided', 'void' => 'secondary',
                        default => 'primary',
                    })
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
                        'pending' => 'Pending',
                        'authorized' => 'Authorized',
                        'captured' => 'Captured',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                        'approved' => 'Approved',
                        'declined' => 'Declined',
                        'voided' => 'Voided',
                        'created' => 'Created',
                        'succeeded' => 'Succeeded',
                        'payment_failed' => 'Payment Failed',
                    ])
                    ->placeholder('All Statuses'),
                Tables\Filters\SelectFilter::make('provider')
                    ->options([
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'bank_transfer' => 'Bank Transfer',
                        'cash_on_delivery' => 'Cash on Delivery',
                        'other' => 'Other',
                        'mock' => 'Mock',
                    ])
                    ->placeholder('All Providers'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}