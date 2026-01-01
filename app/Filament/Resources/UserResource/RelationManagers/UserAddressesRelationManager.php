<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\UserAddress;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserAddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),
                    ]),
                Forms\Components\TextInput::make('company')
                    ->maxLength(255)
                    ->nullable(),
                Forms\Components\TextInput::make('address_line1')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('address_line2')
                    ->maxLength(255)
                    ->nullable(),
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('city')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('state')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('zip')
                            ->required()
                            ->maxLength(20),
                    ]),
                Forms\Components\Select::make('country')
                    ->options([
                        'JP' => 'Japan',
                        'US' => 'United States',
                        'CA' => 'Canada',
                        'GB' => 'United Kingdom',
                        'AU' => 'Australia',
                    ])
                    ->required()
                    ->default('JP'),
                Forms\Components\Toggle::make('is_default')
                    ->label('Default Address')
                    ->inline(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('first_name')
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('address_line1')
                    ->label('Address')
                    ->description(fn (UserAddress $record) => $record->city . ', ' . $record->state . ' ' . $record->zip)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('country')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->label('Default'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Default Address'),
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
            ])
            ->defaultSort('is_default', 'desc');
    }
}