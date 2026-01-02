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
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'bank_transfer' => 'Bank Transfer',
                        'cash_on_delivery' => 'Cash on Delivery',
                        'other' => 'Other',
                    ])
                    ->required(),
                Forms\Components\Select::make('type')
                    ->options([
                        'payment' => 'Payment',
                        'refund' => 'Refund',
                        'authorization' => 'Authorization',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('amount_yen')
                    ->required()
                    ->numeric()
                    ->prefix('¥'),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'authorized' => 'Authorized',
                        'captured' => 'Captured',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('payload_json')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('processed_at'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('provider')
            ->columns([
                Tables\Columns\TextColumn::make('provider')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_yen')
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('processed_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'authorized' => 'Authorized',
                        'captured' => 'Captured',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ])
                    ->placeholder('All Statuses'),
                Tables\Filters\SelectFilter::make('provider')
                    ->options([
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'bank_transfer' => 'Bank Transfer',
                        'cash_on_delivery' => 'Cash on Delivery',
                        'other' => 'Other',
                    ])
                    ->placeholder('All Providers'),
            ])
            ->headerActions([
                // Removed CreateAction to redirect to PaymentResource
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => '/admin/payments/' . $record->id),
                Tables\Actions\EditAction::make()
                    ->url(fn ($record) => '/admin/payments/' . $record->id . '/edit'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Removed DeleteBulkAction to maintain data integrity
                ]),
            ]);
    }
}