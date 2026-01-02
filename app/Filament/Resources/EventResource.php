<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Filament\Resources\EventResource\RelationManagers;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 16;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Event Information')
                    ->description('Information about the user behavior event')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->label('Product (Optional)')
                                    ->placeholder('Select a product if applicable'),
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->label('User (Optional)')
                                    ->placeholder('Select a user if logged in'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('user_hash')
                                    ->maxLength(255)
                                    ->label('User Hash')
                                    ->placeholder('Anonymous user identifier'),
                                Forms\Components\Select::make('event_type')
                                    ->options([
                                        'product_view' => 'Product View',
                                        'add_to_cart' => 'Add to Cart',
                                        'remove_from_cart' => 'Remove from Cart',
                                        'checkout_start' => 'Checkout Start',
                                        'checkout_complete' => 'Checkout Complete',
                                        'search' => 'Search',
                                        'category_view' => 'Category View',
                                        'brand_view' => 'Brand View',
                                        'wishlist_add' => 'Wishlist Add',
                                        'wishlist_remove' => 'Wishlist Remove',
                                    ])
                                    ->required()
                                    ->label('Event Type'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('value')
                                    ->numeric()
                                    ->minValue(0)
                                    ->label('Value (Optional)')
                                    ->placeholder('Numeric value associated with event'),
                                Forms\Components\DateTimePicker::make('occurred_at')
                                    ->required()
                                    ->label('Occurred At'),
                            ]),
                        Forms\Components\Textarea::make('meta_json')
                            ->rows(4)
                            ->columnSpanFull()
                            ->label('Metadata')
                            ->helperText('Additional event metadata in JSON format'),
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
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_hash')
                    ->label('User Hash')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('event_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'product_view' => 'info',
                        'add_to_cart' => 'warning',
                        'remove_from_cart' => 'gray',
                        'checkout_start' => 'primary',
                        'checkout_complete' => 'success',
                        'search' => 'secondary',
                        'category_view' => 'purple',
                        'brand_view' => 'amber',
                        'wishlist_add' => 'pink',
                        'wishlist_remove' => 'red',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'product_view' => 'Product View',
                        'add_to_cart' => 'Add to Cart',
                        'remove_from_cart' => 'Remove from Cart',
                        'checkout_start' => 'Checkout Start',
                        'checkout_complete' => 'Checkout Complete',
                        'search' => 'Search',
                        'category_view' => 'Category View',
                        'brand_view' => 'Brand View',
                        'wishlist_add' => 'Wishlist Add',
                        'wishlist_remove' => 'Wishlist Remove',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('value')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('occurred_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event_type')
                    ->options([
                        'product_view' => 'Product View',
                        'add_to_cart' => 'Add to Cart',
                        'remove_from_cart' => 'Remove from Cart',
                        'checkout_start' => 'Checkout Start',
                        'checkout_complete' => 'Checkout Complete',
                        'search' => 'Search',
                        'category_view' => 'Category View',
                        'brand_view' => 'Brand View',
                        'wishlist_add' => 'Wishlist Add',
                        'wishlist_remove' => 'Wishlist Remove',
                    ])
                    ->placeholder('All Event Types'),
                Tables\Filters\SelectFilter::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Products'),
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Users'),
                Tables\Filters\Filter::make('occurred_at')
                    ->form([
                        Forms\Components\DatePicker::make('occurred_from')
                            ->label('Occurred From'),
                        Forms\Components\DatePicker::make('occurred_until')
                            ->label('Occurred Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['occurred_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('occurred_at', '>=', $date)
                            )
                            ->when(
                                $data['occurred_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('occurred_at', '<=', $date)
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
            ->defaultSort('occurred_at', 'desc');
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
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'view' => Pages\ViewEvent::route('/{record}'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }

    public static function can(string $action, $record = null): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->isAdmin() || $user->isStaff();
    }
}