<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Filament\Resources\CouponResource\RelationManagers;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'ECサイト';

    protected static ?string $navigationLabel = 'クーポン';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('クーポン情報')
                    ->description('基本的なクーポン情報と設定')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->required()
                                    ->maxLength(40)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('クーポンコードを入力してください')
                                    ->helperText('このクーポンのユニークコード'),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'percent' => 'パーセンテージ',
                                        'fixed' => '固定金額',
                                    ])
                                    ->required()
                                    ->default('percent')
                                    ->live()
                                    ->helperText('適用する割引の種類'),
                                Forms\Components\TextInput::make('value')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->placeholder('割引値を入力してください')
                                    ->helperText('割引値（パーセンテージまたは円単位の固定金額）'),
                                Forms\Components\Textarea::make('description')
                                    ->maxLength(160)
                                    ->rows(2)
                                    ->placeholder('クーポンの説明を入力してください')
                                    ->helperText('このクーポンのオプション説明'),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('有効期間')
                    ->description('このクーポンが有効になる時期を設定')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('starts_at')
                                    ->label('開始日')
                                    ->helperText('クーポンが有効になる日時'),
                                Forms\Components\DatePicker::make('ends_at')
                                    ->label('終了日')
                                    ->helperText('クーポンが期限切れになる日時'),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('使用制限')
                    ->description('クーポン使用の制限を設定')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('max_uses')
                                    ->label('最大使用回数')
                                    ->numeric()
                                    ->minValue(0)
                                    ->placeholder('無制限の場合は空のままにしてください')
                                    ->helperText('このクーポンの最大総使用回数'),
                                Forms\Components\TextInput::make('max_uses_per_user')
                                    ->label('ユーザーごとの最大使用回数')
                                    ->numeric()
                                    ->minValue(0)
                                    ->placeholder('無制限の場合は空のままにしてください')
                                    ->helperText('1人のユーザーがこのクーポンを使用できる最大回数'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('min_subtotal_yen')
                                    ->label('最小小計（¥）')
                                    ->numeric()
                                    ->minValue(0)
                                    ->placeholder('最小金額がない場合は空のままにしてください')
                                    ->helperText('このクーポンを使用するために必要な最小カート小計'),
                                Forms\Components\TextInput::make('max_discount_yen')
                                    ->label('最大割引（¥）')
                                    ->numeric()
                                    ->minValue(0)
                                    ->placeholder('最大金額がない場合は空のままにしてください')
                                    ->helperText('このクーポンの最大割引額'),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('追加設定')
                    ->description('追加のクーポン設定')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('有効')
                                    ->default(true)
                                    ->inline(false)
                                    ->helperText('このクーポンが現在有効かどうか'),
                                Forms\Components\Toggle::make('exclude_sale_items')
                                    ->label('セール品を除外')
                                    ->inline(false)
                                    ->helperText('チェックした場合、このクーポンはセール品に適用できません'),
                            ]),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('redemptions');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('コード')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('説明')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('type')
                    ->label('タイプ')
                    ->formatStateUsing(function ($state) {
                        return ucfirst($state);
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'percent' => 'primary',
                        'fixed' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('value')
                    ->label('値')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->type === 'percent') {
                            return $state . '%';
                        } else {
                            return '¥' . number_format($state);
                        }
                    }),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('開始日時')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('終了日時')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_uses')
                    ->label('最大使用回数')
                    ->formatStateUsing(function ($state) {
                        return $state ?? '無制限';
                    }),
                Tables\Columns\TextColumn::make('used_count')
                    ->label('使用回数')
                    ->sortable(),
                Tables\Columns\TextColumn::make('redemption_count')
                    ->label('償還回数')
                    ->counts('redemptions')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('有効')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'percent' => 'パーセンテージ',
                        'fixed' => '固定金額',
                    ])
                    ->placeholder('すべてのタイプ'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('有効'),
                Tables\Filters\Filter::make('starts_at')
                    ->form([
                        Forms\Components\DatePicker::make('starts_after')
                            ->label('開始日以降'),
                        Forms\Components\DatePicker::make('starts_before')
                            ->label('開始日前'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['starts_after'],
                                fn (Builder $query, $value): Builder => $query->where('starts_at', '>=', $value)
                            )
                            ->when(
                                $data['starts_before'],
                                fn (Builder $query, $value): Builder => $query->where('starts_at', '<=', $value)
                            );
                    }),
                Tables\Filters\Filter::make('ends_at')
                    ->form([
                        Forms\Components\DatePicker::make('ends_after')
                            ->label('終了日以降'),
                        Forms\Components\DatePicker::make('ends_before')
                            ->label('終了日前'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['ends_after'],
                                fn (Builder $query, $value): Builder => $query->where('ends_at', '>=', $value)
                            )
                            ->when(
                                $data['ends_before'],
                                fn (Builder $query, $value): Builder => $query->where('ends_at', '<=', $value)
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CouponProductsRelationManager::class,
            RelationManagers\CouponCategoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'view' => Pages\ViewCoupon::route('/{record}'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
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