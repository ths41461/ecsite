<?php

namespace App\Filament\Resources\CategoryResource\RelationManagers;

use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoryCouponsRelationManager extends RelationManager
{
    protected static string $relationship = 'coupons';

    protected static ?string $recordTitleAttribute = 'code';

    public function form(Form $form): Form
    {
        return $form;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('クーポンコード')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('説明')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('タイプ')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'percentage' => 'warning',
                        'fixed_amount' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('value')
                    ->label('値')
                    ->formatStateUsing(function ($record) {
                        if ($record->type === 'percentage') {
                            return $record->value . '%';
                        } else {
                            return '¥' . number_format($record->value);
                        }
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('有効')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('有効状態'),
                Tables\Filters\Filter::make('type')
                    ->form([
                        Forms\Components\Select::make('type')
                            ->options([
                                'percentage' => 'パーセンテージ',
                                'fixed_amount' => '固定金額',
                            ])
                            ->placeholder('タイプを選択'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['type'],
                            fn (Builder $query, $type) => $query->where('type', $type)
                        );
                    }),
            ])
            ->headerActions([
                Tables\Actions\AssociateAction::make()
                    ->label('関連付け')
                    ->multiple()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function (Builder $query) use ($table) {
                        return $query->whereDoesntHave('categories', function ($query) use ($table) {
                            $query->where('categories.id', $table->getOwnerRecord()->getKey());
                        })->orderBy('code');
                    }),
            ])
            ->actions([
                Tables\Actions\DissociateAction::make()
                    ->label('解除')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DissociateBulkAction::make()
                        ->label('解除')
                        ->requiresConfirmation(),
                ]),
            ]);
    }
}