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

    protected static ?string $navigationGroup = 'E-commerce';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Coupon Information')
                    ->description('Basic coupon information and settings')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->required()
                                    ->maxLength(40)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Enter coupon code')
                                    ->helperText('Unique code for this coupon'),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'percent' => 'Percentage',
                                        'fixed' => 'Fixed Amount',
                                    ])
                                    ->required()
                                    ->default('percent')
                                    ->live()
                                    ->helperText('Type of discount to apply'),
                                Forms\Components\TextInput::make('value')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->placeholder('Enter discount value')
                                    ->helperText('Discount value (percentage or fixed amount in yen)'),
                                Forms\Components\Textarea::make('description')
                                    ->maxLength(160)
                                    ->rows(2)
                                    ->placeholder('Enter coupon description')
                                    ->helperText('Optional description for this coupon'),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Validity Period')
                    ->description('Set when this coupon is valid')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('starts_at')
                                    ->label('Start Date')
                                    ->helperText('When the coupon becomes active'),
                                Forms\Components\DatePicker::make('ends_at')
                                    ->label('End Date')
                                    ->helperText('When the coupon expires'),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Usage Limits')
                    ->description('Set limits on coupon usage')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('max_uses')
                                    ->label('Max Uses')
                                    ->numeric()
                                    ->minValue(0)
                                    ->placeholder('Leave empty for unlimited')
                                    ->helperText('Maximum total uses of this coupon'),
                                Forms\Components\TextInput::make('max_uses_per_user')
                                    ->label('Max Uses Per User')
                                    ->numeric()
                                    ->minValue(0)
                                    ->placeholder('Leave empty for unlimited')
                                    ->helperText('Maximum times a single user can use this coupon'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('min_subtotal_yen')
                                    ->label('Minimum Subtotal (¥)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->placeholder('Leave empty for no minimum')
                                    ->helperText('Minimum cart subtotal required to use this coupon'),
                                Forms\Components\TextInput::make('max_discount_yen')
                                    ->label('Max Discount (¥)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->placeholder('Leave empty for no maximum')
                                    ->helperText('Maximum discount amount for this coupon'),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Settings')
                    ->description('Additional coupon settings')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->inline(false)
                                    ->helperText('Whether this coupon is currently active'),
                                Forms\Components\Toggle::make('exclude_sale_items')
                                    ->label('Exclude Sale Items')
                                    ->inline(false)
                                    ->helperText('If checked, this coupon cannot be applied to sale items'),
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
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
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
                    ->label('Value')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->type === 'percent') {
                            return $state . '%';
                        } else {
                            return '¥' . number_format($state);
                        }
                    }),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Starts At')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Ends At')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_uses')
                    ->label('Max Uses')
                    ->formatStateUsing(function ($state) {
                        return $state ?? 'Unlimited';
                    }),
                Tables\Columns\TextColumn::make('used_count')
                    ->label('Used Count')
                    ->sortable(),
                Tables\Columns\TextColumn::make('redemption_count')
                    ->label('Redemptions')
                    ->counts('redemptions')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
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
                        'percent' => 'Percentage',
                        'fixed' => 'Fixed Amount',
                    ])
                    ->placeholder('All Types'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\Filter::make('starts_at')
                    ->form([
                        Forms\Components\DatePicker::make('starts_after')
                            ->label('Starts After'),
                        Forms\Components\DatePicker::make('starts_before')
                            ->label('Starts Before'),
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
                            ->label('Ends After'),
                        Forms\Components\DatePicker::make('ends_before')
                            ->label('Ends Before'),
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
}