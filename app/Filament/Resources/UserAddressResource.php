<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserAddressResource\Pages;
use App\Filament\Resources\UserAddressResource\RelationManagers;
use App\Models\UserAddress;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserAddressResource extends Resource
{
    protected static ?string $model = UserAddress::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Address Information')
                    ->description('User address information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('User'),
                        
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Users'),
                
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserAddresses::route('/'),
            'create' => Pages\CreateUserAddress::route('/create'),
            'view' => Pages\ViewUserAddress::route('/{record}'),
            'edit' => Pages\EditUserAddress::route('/{record}/edit'),
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