<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\Shipment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderShipmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'shipments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('carrier')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('tracking_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'packed' => 'Packed',
                        'in_transit' => 'In Transit',
                        'delivered' => 'Delivered',
                        'returned' => 'Returned',
                    ])
                    ->required(),
                Forms\Components\DateTimePicker::make('shipped_at'),
                Forms\Components\DateTimePicker::make('delivered_at'),
                Forms\Components\Textarea::make('timeline_json')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tracking_number')
            ->columns([
                Tables\Columns\TextColumn::make('carrier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tracking_number')
                    ->label('Tracking #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'packed' => 'info',
                        'in_transit' => 'primary',
                        'delivered' => 'success',
                        'returned' => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('shipped_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivered_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'packed' => 'Packed',
                        'in_transit' => 'In Transit',
                        'delivered' => 'Delivered',
                        'returned' => 'Returned',
                    ])
                    ->placeholder('All Statuses'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
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