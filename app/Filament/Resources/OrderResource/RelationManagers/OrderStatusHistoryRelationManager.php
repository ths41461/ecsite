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
                Forms\Components\Section::make('ステータス変更情報')
                    ->description('ステータス変更に関する情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('from_status_id')
                                    ->relationship('fromStatus', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('変更前ステータス'),
                                Forms\Components\Select::make('to_status_id')
                                    ->relationship('toStatus', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('変更後ステータス'),
                            ]),
                        Forms\Components\Select::make('changed_by')
                            ->relationship('changedByUser', 'name')
                            ->searchable()
                            ->preload()
                            ->label('変更者（ユーザーID）')
                            ->helperText('ステータスを変更したユーザー'),
                        Forms\Components\DateTimePicker::make('changed_at')
                            ->required()
                            ->label('変更日時'),
                        Forms\Components\Textarea::make('note')
                            ->rows(3)
                            ->maxLength(65535)
                            ->label('備考')
                            ->helperText('ステータス変更に関するオプションの備考')
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
                    ->label('変更前ステータス')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '保留中' => 'gray',
                        '処理中' => 'info',
                        '発送済み' => 'warning',
                        '配達済み' => 'success',
                        'キャンセル' => 'danger',
                        '返金済み' => 'secondary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'Pending' => '保留中',
                        'Processing' => '処理中',
                        'Shipped' => '発送済み',
                        'Delivered' => '配達済み',
                        'Cancelled' => 'キャンセル',
                        'Refunded' => '返金済み',
                        default => $state,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('toStatus.name')
                    ->label('変更後ステータス')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '保留中' => 'gray',
                        '処理中' => 'info',
                        '発送済み' => 'warning',
                        '配達済み' => 'success',
                        'キャンセル' => 'danger',
                        '返金済み' => 'secondary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'Pending' => '保留中',
                        'Processing' => '処理中',
                        'Shipped' => '発送済み',
                        'Delivered' => '配達済み',
                        'Cancelled' => 'キャンセル',
                        'Refunded' => '返金済み',
                        default => $state,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('changedByUser.name')
                    ->label('変更者')
                    ->searchable()
                    ->sortable()
                    ->placeholder('システム'),
                Tables\Columns\TextColumn::make('changed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('note')
                    ->label('備考')
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
                    ->placeholder('すべての変更前ステータス'),
                Tables\Filters\SelectFilter::make('to_status_id')
                    ->relationship('toStatus', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('すべての変更後ステータス'),
                Tables\Filters\Filter::make('changed_at')
                    ->form([
                        Forms\Components\DatePicker::make('changed_from')
                            ->label('変更日範囲（開始）'),
                        Forms\Components\DatePicker::make('changed_until')
                            ->label('変更日範囲（終了）'),
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