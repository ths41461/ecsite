<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Select product'),
                Forms\Components\TextInput::make('user_hash')
                    ->maxLength(255)
                    ->placeholder('Enter user hash (for anonymous users)'),
                Forms\Components\Select::make('event_type')
                    ->options([
                        'view' => 'View',
                        'add_to_cart' => 'Add to Cart',
                        'remove_from_cart' => 'Remove from Cart',
                        'checkout_start' => 'Checkout Start',
                        'checkout_complete' => 'Checkout Complete',
                        'search' => 'Search',
                        'filter' => 'Filter',
                        'sort' => 'Sort',
                        'wishlist_add' => 'Wishlist Add',
                        'wishlist_remove' => 'Wishlist Remove',
                        'review' => 'Review',
                        'share' => 'Share',
                    ])
                    ->required()
                    ->searchable()
                    ->placeholder('Select event type'),
                Forms\Components\TextInput::make('value')
                    ->numeric()
                    ->step('0.01')
                    ->placeholder('Enter value (optional)'),
                Forms\Components\DateTimePicker::make('occurred_at')
                    ->required()
                    ->placeholder('Select occurrence time'),
                Forms\Components\Textarea::make('meta_json')
                    ->columnSpanFull()
                    ->rows(4)
                    ->placeholder('Enter metadata as JSON (optional)'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event_type')
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_hash')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('event_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'view' => 'info',
                        'add_to_cart' => 'warning',
                        'remove_from_cart' => 'gray',
                        'checkout_start' => 'primary',
                        'checkout_complete' => 'success',
                        'search' => 'secondary',
                        'filter' => 'purple',
                        'sort' => 'indigo',
                        'wishlist_add' => 'pink',
                        'wishlist_remove' => 'red',
                        'review' => 'green',
                        'share' => 'blue',
                        default => 'secondary',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('value')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('occurred_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event_type')
                    ->options([
                        'view' => 'View',
                        'add_to_cart' => 'Add to Cart',
                        'remove_from_cart' => 'Remove from Cart',
                        'checkout_start' => 'Checkout Start',
                        'checkout_complete' => 'Checkout Complete',
                        'search' => 'Search',
                        'filter' => 'Filter',
                        'sort' => 'Sort',
                        'wishlist_add' => 'Wishlist Add',
                        'wishlist_remove' => 'Wishlist Remove',
                        'review' => 'Review',
                        'share' => 'Share',
                    ])
                    ->placeholder('All Event Types'),
                Tables\Filters\SelectFilter::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Products'),
                Tables\Filters\Filter::make('occurred_at')
                    ->form([
                        Forms\Components\DatePicker::make('occurred_from')
                            ->label('Occurred from'),
                        Forms\Components\DatePicker::make('occurred_until')
                            ->label('Occurred until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['occurred_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('occurred_at', '>=', $date),
                            )
                            ->when(
                                $data['occurred_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('occurred_at', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}