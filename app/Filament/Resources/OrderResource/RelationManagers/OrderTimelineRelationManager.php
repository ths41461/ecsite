<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\OrderStatusHistory;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderTimelineRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistory';

    protected static ?string $title = 'Timeline';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status_name')
                    ->label('Status')
                    ->sortable(),
                Tables\Columns\TextColumn::make('changed_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('changed_by')
                    ->label('Changed By')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ])
            ->paginated(false);
    }

    protected function getTableQuery(): Builder
    {
        return OrderStatusHistory::where('order_id', $this->getOwnerRecord()->id)
            ->join('order_statuses', 'order_statuses.id', '=', 'order_status_history.to_status_id')
            ->select('order_status_history.*', 'order_statuses.name as status_name')
            ->orderBy('order_status_history.changed_at', 'asc');
    }

    public function getTableHeading(): string
    {
        return 'Timeline';
    }
}