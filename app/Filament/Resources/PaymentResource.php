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

    protected static ?string $navigationGroup = 'Orders';

    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Information')
                    ->description('Information about the payment transaction')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('order_id')
                                    ->relationship('order', 'order_number')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('Order'),
                                Forms\Components\Select::make('payment_status_id')
                                    ->relationship('paymentStatus', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('Payment Status'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('provider')
                                    ->options([
                                        'stripe' => 'Stripe',
                                        'paypal' => 'PayPal',
                                        'bank_transfer' => 'Bank Transfer',
                                        'cash_on_delivery' => 'Cash on Delivery',
                                        'other' => 'Other',
                                    ])
                                    ->required()
                                    ->label('Provider'),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'one_time' => 'One Time',
                                        'subscription' => 'Subscription',
                                        'installment' => 'Installment',
                                    ])
                                    ->required()
                                    ->label('Type'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount_yen')
                                    ->required()
                                    ->numeric()
                                    ->prefix('¥')
                                    ->label('Amount (¥)'),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'processing' => 'Processing',
                                        'succeeded' => 'Succeeded',
                                        'failed' => 'Failed',
                                        'canceled' => 'Canceled',
                                        'refunded' => 'Refunded',
                                    ])
                                    ->required()
                                    ->label('Status'),
                            ]),
                        Forms\Components\DateTimePicker::make('processed_at')
                            ->label('Processed At'),
                        Forms\Components\Textarea::make('payload_json')
                            ->rows(5)
                            ->columnSpanFull()
                            ->label('Payload Data')
                            ->helperText('Raw payment provider data'),
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
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => route('filament.admin.resources.orders.edit', ['record' => $record->order_id]))
                    ->openUrlInNewTab(false),
                Tables\Columns\TextColumn::make('provider')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('type')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('amount_yen')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => '¥' . number_format($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('paymentStatus.name')
                    ->label('Payment Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Completed', 'Confirmed', 'Verified' => 'success',
                        'Pending', 'Processing' => 'warning',
                        'Failed', 'Rejected', 'Cancelled' => 'danger',
                        default => 'gray',
                    })
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
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
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
                Tables\Filters\SelectFilter::make('payment_status_id')
                    ->relationship('paymentStatus', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Payment Statuses'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'succeeded' => 'Succeeded',
                        'failed' => 'Failed',
                        'canceled' => 'Canceled',
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
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'one_time' => 'One Time',
                        'subscription' => 'Subscription',
                        'installment' => 'Installment',
                    ])
                    ->placeholder('All Types'),
                Tables\Filters\Filter::make('processed_at')
                    ->form([
                        Forms\Components\DatePicker::make('processed_from')
                            ->label('Processed From'),
                        Forms\Components\DatePicker::make('processed_until')
                            ->label('Processed Until'),
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
                            ->label('Min Amount')
                            ->numeric()
                            ->prefix('¥'),
                        Forms\Components\TextInput::make('max_amount')
                            ->label('Max Amount')
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
            //
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