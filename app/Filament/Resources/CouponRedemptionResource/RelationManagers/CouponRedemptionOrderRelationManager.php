<?php

namespace App\Filament\Resources\CouponRedemptionResource\RelationManagers;

use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CouponRedemptionOrderRelationManager extends RelationManager
{
    protected static string $relationship = 'order';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('注文情報')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->label('注文番号'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'ordered' => '注文済み',
                                'processing' => '処理中',
                                'shipped' => '発送済み',
                                'delivered' => '配達済み',
                                'canceled' => 'キャンセル',
                                'refunded' => '返金済み',
                            ])
                            ->required()
                            ->label('ステータス'),
                        Forms\Components\TextInput::make('total_yen')
                            ->required()
                            ->numeric()
                            ->prefix('¥')
                            ->label('合計（¥）'),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('有効'),
                    ])
                    ->columns(2),
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
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ordered' => 'gray',
                        'processing' => 'warning',
                        'shipped' => 'info',
                        'delivered' => 'success',
                        'canceled' => 'danger',
                        'refunded' => 'secondary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'ordered' => '注文済み',
                        'processing' => '処理中',
                        'shipped' => '発送済み',
                        'delivered' => '配達済み',
                        'canceled' => 'キャンセル',
                        'refunded' => '返金済み',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    }),
                Tables\Columns\TextColumn::make('total_yen')
                    ->label('合計')
                    ->formatStateUsing(fn ($state) => '¥' . number_format($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('作成'),
                Tables\Actions\AttachAction::make()
                    ->label('関連付け'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('表示'),
                Tables\Actions\EditAction::make()
                    ->label('編集'),
                Tables\Actions\DetachAction::make()
                    ->label('解除'),
                Tables\Actions\DeleteAction::make()
                    ->label('削除'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('解除'),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('削除'),
                ]),
            ]);
    }
}