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

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-on-square';

    protected static ?string $navigationGroup = 'E-commerce';

    protected static ?int $navigationSort = 13;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Redemption Information')
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
                                Forms\Components\Select::make('order_id')
                                    ->relationship('order', 'order_number')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('Order'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->label('User (Optional)'),
                                Forms\Components\DateTimePicker::make('redeemed_at')
                                    ->required()
                                    ->label('Redeemed At'),
                            ]),
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
                    ->label('Coupon Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('coupon.description')
                    ->label('Coupon Description')
                    ->searchable()
                    ->limit(50)
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
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
                    ->placeholder('All Coupons'),
                Tables\Filters\SelectFilter::make('order_id')
                    ->relationship('order', 'order_number')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Orders'),
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Users'),
                Tables\Filters\Filter::make('redeemed_at')
                    ->form([
                        Forms\Components\DatePicker::make('redeemed_from')
                            ->label('Redeemed From'),
                        Forms\Components\DatePicker::make('redeemed_until')
                            ->label('Redeemed Until'),
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
            RelationManagers\CouponRedemptionCouponRelationManager::class,
            RelationManagers\CouponRedemptionOrderRelationManager::class,
            RelationManagers\CouponRedemptionUserRelationManager::class,
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

    public static function can(string $action, $record = null): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->isAdmin();
    }
}