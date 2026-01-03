<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\OrderStatusHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderStatusHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistory';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Status Change Information')
                    ->description('Information about the status change')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('from_status_id')
                                    ->relationship('fromStatus', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('From Status'),
                                Forms\Components\Select::make('to_status_id')
                                    ->relationship('toStatus', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('To Status'),
                            ]),
                        Forms\Components\Select::make('changed_by')
                            ->relationship('changedByUser', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Changed By (User ID)')
                            ->helperText('User who changed the status'),
                        Forms\Components\DateTimePicker::make('changed_at')
                            ->required()
                            ->label('Changed At'),
                        Forms\Components\Textarea::make('note')
                            ->rows(3)
                            ->maxLength(65535)
                            ->label('Note')
                            ->helperText('Optional note about the status change')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fromStatus.name')
                    ->label('From Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'gray',
                        'Processing' => 'info',
                        'Shipped' => 'warning',
                        'Delivered' => 'success',
                        'Cancelled' => 'danger',
                        'Refunded' => 'secondary',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('toStatus.name')
                    ->label('To Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'gray',
                        'Processing' => 'info',
                        'Shipped' => 'warning',
                        'Delivered' => 'success',
                        'Cancelled' => 'danger',
                        'Refunded' => 'secondary',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('changedByUser.name')
                    ->label('Changed By')
                    ->searchable()
                    ->sortable()
                    ->placeholder('System'),
                Tables\Columns\TextColumn::make('changed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('note')
                    ->label('Note')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('from_status_id')
                    ->relationship('fromStatus', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All From Statuses'),
                Tables\Filters\SelectFilter::make('to_status_id')
                    ->relationship('toStatus', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All To Statuses'),
                Tables\Filters\Filter::make('changed_at')
                    ->form([
                        Forms\Components\DatePicker::make('changed_from')
                            ->label('Changed From'),
                        Forms\Components\DatePicker::make('changed_until')
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
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AssociateAction::make(),
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
            ]);
    }
}