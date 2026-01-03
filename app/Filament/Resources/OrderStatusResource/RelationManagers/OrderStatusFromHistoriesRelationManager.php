<?php

namespace App\Filament\Resources\OrderStatusResource\RelationManagers;

use App\Models\OrderStatusHistory;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderStatusFromHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'fromStatusHistories';

    protected static ?string $title = 'From Status Changes';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->order ? route('filament.admin.resources.orders.view', ['record' => $record->order->id]) : null)
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('toStatus.name')
                    ->label('To Status')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('changed_by')
                    ->label('Changed By')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('changed_at')
                    ->label('Changed At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('to_status_id')
                    ->label('To Status')
                    ->relationship('toStatus', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('changed_at')
                    ->form([
                        Tables\Components\DatePicker::make('changed_from')
                            ->label('Changed From'),
                        Tables\Components\DatePicker::make('changed_until')
                            ->label('Changed Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['changed_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('changed_at', '>=', $date)
                            )
                            ->when(
                                $data['changed_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('changed_at', '<=', $date)
                            );
                    }),
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ])
            ->defaultSort('changed_at', 'desc');
    }
}