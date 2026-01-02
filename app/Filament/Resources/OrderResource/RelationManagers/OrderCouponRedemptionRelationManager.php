<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\CouponRedemption;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderCouponRedemptionRelationManager extends RelationManager
{
    protected static string $relationship = 'couponRedemption';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Coupon Redemption Information')
                    ->description('Information about the coupon redemption')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('coupon_id')
                                    ->relationship('coupon', 'code')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('Coupon'),
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->label('User (Optional)'),
                            ]),
                        Forms\Components\DateTimePicker::make('redeemed_at')
                            ->required()
                            ->label('Redeemed At'),
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
                Tables\Columns\TextColumn::make('coupon.code')
                    ->label('Coupon Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Redeemed By')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('redeemed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('coupon_id')
                    ->relationship('coupon', 'code')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Coupons'),
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Users'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => '/admin/coupon-redemptions/' . $record->id)
                    ->openUrlInNewTab(false),
                Tables\Actions\EditAction::make()
                    ->url(fn ($record) => '/admin/coupon-redemptions/' . $record->id . '/edit')
                    ->openUrlInNewTab(false),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}