<?php

namespace App\Filament\Resources\ShipmentResource\RelationManagers;

use App\Models\ShipmentTrack;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShipmentTracksRelationManager extends RelationManager
{
    protected static string $relationship = 'shipmentTracks';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('carrier')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('track_no')
                    ->label('Tracking Number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(50),
                Forms\Components\DateTimePicker::make('event_time')
                    ->required(),
                Forms\Components\Textarea::make('raw_event_json')
                    ->label('Raw Event Data')
                    ->rows(5)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('carrier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('track_no')
                    ->label('Tracking Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'packed' => 'info',
                        'in_transit' => 'warning',
                        'delivered' => 'success',
                        'returned' => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('event_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
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
                Tables\Actions\ViewAction::make(),
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