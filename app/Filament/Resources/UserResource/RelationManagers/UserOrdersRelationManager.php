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
                    ->label('注文番号')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_yen')
                    ->label('合計')
                    ->formatStateUsing(function ($state) {
                        return '¥' . number_format($state);
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('orderStatus.name')
                    ->label('ステータス')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '保留中' => 'warning',
                        '支払済み' => 'success',
                        '履行済み' => 'info',
                        'キャンセル' => 'danger',
                        '返金済み' => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'Pending' => '保留中',
                        'Paid' => '支払済み',
                        'Fulfilled' => '履行済み',
                        'Cancelled' => 'キャンセル',
                        'Refunded' => '返金済み',
                        default => $state,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('ordered_at')
                    ->label('日付')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('order_status_id')
                    ->relationship('orderStatus', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('すべてのステータス'),
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