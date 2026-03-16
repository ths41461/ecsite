<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponRedemptionResource\Pages;
use App\Filament\Resources\CouponRedemptionResource\RelationManagers;
use App\Models\CouponRedemption;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CouponRedemptionResource extends Resource
{
    protected static ?string $model = CouponRedemption::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'マーケティング';

    protected static ?string $navigationLabel = 'クーポン使用記録';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('使用記録情報')
                    ->description('クーポン使用記録に関する情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('coupon_id')
                                    ->relationship('coupon', 'code')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('クーポン'),
                                Forms\Components\Select::make('order_id')
                                    ->relationship('order', 'order_number')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('注文'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->label('ユーザー（任意）'),
                                Forms\Components\TextInput::make('amount_yen')
                                    ->required()
                                    ->numeric()
                                    ->prefix('¥')
                                    ->label('割引額（¥）'),
                            ]),
                        Forms\Components\DateTimePicker::make('redeemed_at')
                            ->required()
                            ->label('使用日時'),
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
                Tables\Columns\TextColumn::make('coupon.code')
                    ->label('クーポンコード')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('注文番号')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('使用者')
                    ->searchable()
                    ->sortable()
                    ->placeholder('ゲスト'),
                Tables\Columns\TextColumn::make('amount_yen')
                    ->label('割引額')
                    ->formatStateUsing(fn ($state) => '¥' . number_format($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('redeemed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('coupon_id')
                    ->relationship('coupon', 'code')
                    ->searchable()
                    ->preload()
                    ->placeholder('すべてのクーポン'),
                Tables\Filters\SelectFilter::make('order_id')
                    ->relationship('order', 'order_number')
                    ->searchable()
                    ->preload()
                    ->placeholder('すべての注文'),
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('すべてのユーザー'),
                Tables\Filters\Filter::make('redeemed_at')
                    ->form([
                        Forms\Components\DatePicker::make('redeemed_from')
                            ->label('使用日範囲（開始）'),
                        Forms\Components\DatePicker::make('redeemed_until')
                            ->label('使用日範囲（終了）'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['redeemed_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('redeemed_at', '>=', $date)
                            )
                            ->when(
                                $data['redeemed_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('redeemed_at', '<=', $date)
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
            ->defaultSort('redeemed_at', 'desc');
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
            'index' => Pages\ListCouponRedemptions::route('/'),
            'create' => Pages\CreateCouponRedemption::route('/create'),
            'view' => Pages\ViewCouponRedemption::route('/{record}'),
            'edit' => Pages\EditCouponRedemption::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'クーポン使用記録';
    }

    public static function getPluralModelLabel(): string
    {
        return 'クーポン使用記録';
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