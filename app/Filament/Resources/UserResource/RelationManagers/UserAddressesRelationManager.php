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
                Forms\Components\Section::make('Address Information')
                    ->description('User address information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Full Name'),
                        
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20)
                            ->label('Phone Number'),
                        
                        Forms\Components\Textarea::make('address_line1')
                            ->required()
                            ->maxLength(255)
                            ->label('Address Line 1'),
                        
                        Forms\Components\Textarea::make('address_line2')
                            ->maxLength(255)
                            ->label('Address Line 2 (Optional)'),
                        
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('city')
                                    ->required()
                                    ->maxLength(100)
                                    ->label('City'),
                                
                                Forms\Components\TextInput::make('state')
                                    ->required()
                                    ->maxLength(100)
                                    ->label('State/Province'),
                                
                                Forms\Components\TextInput::make('zip')
                                    ->required()
                                    ->maxLength(20)
                                    ->label('ZIP/Postal Code'),
                            ]),
                        
                        Forms\Components\Select::make('country')
                            ->options([
                                'JP' => 'Japan',
                                'US' => 'United States',
                                'CA' => 'Canada',
                                'GB' => 'United Kingdom',
                                'AU' => 'Australia',
                                'DE' => 'Germany',
                                'FR' => 'France',
                                'IT' => 'Italy',
                                'ES' => 'Spain',
                                'NL' => 'Netherlands',
                                'IN' => 'India',
                                'CN' => 'China',
                                'KR' => 'South Korea',
                                'SG' => 'Singapore',
                                'HK' => 'Hong Kong',
                            ])
                            ->required()
                            ->default('JP')
                            ->label('Country'),
                        
                        Forms\Components\Toggle::make('is_default')
                            ->label('Default Address')
                            ->inline(false)
                            ->helperText('Set as the user\'s default address'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Full Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('address_line1')
                    ->label('Address')
                    ->description(fn (UserAddress $record) => 
                        collect([$record->city, $record->state, $record->zip, $record->country])
                            ->filter()
                            ->join(', ')
                    )
                    ->searchable()
                    ->limit(50),
                
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('state')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('zip')
                    ->label('ZIP')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('country')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Default Address'),
                
                Tables\Filters\SelectFilter::make('country')
                    ->options([
                        'JP' => 'Japan',
                        'US' => 'United States',
                        'CA' => 'Canada',
                        'GB' => 'United Kingdom',
                        'AU' => 'Australia',
                        'DE' => 'Germany',
                        'FR' => 'France',
                        'IT' => 'Italy',
                        'ES' => 'Spain',
                        'NL' => 'Netherlands',
                        'IN' => 'India',
                        'CN' => 'China',
                        'KR' => 'South Korea',
                        'SG' => 'Singapore',
                        'HK' => 'Hong Kong',
                    ])
                    ->placeholder('All Countries'),
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