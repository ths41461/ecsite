<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('order_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('order_status_id')
                    ->relationship('orderStatus', 'name')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_number')
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_yen')
                    ->label('Total')
                    ->formatStateUsing(function ($state) {
                        return '¥' . number_format($state);
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('orderStatus.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Paid' => 'success',
                        'Fulfilled' => 'info',
                        'Cancelled' => 'danger',
                        'Refunded' => 'gray',
                        default => 'secondary',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('ordered_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('order_status_id')
                    ->relationship('orderStatus', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Statuses'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
            ->defaultSort('ordered_at', 'desc');
    }
}