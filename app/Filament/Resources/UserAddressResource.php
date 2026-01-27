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

    protected static ?string $navigationGroup = 'ユーザー管理';

    protected static ?string $navigationLabel = 'ユーザー住所';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('住所情報')
                    ->description('ユーザーの住所情報')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('ユーザー'),
                        
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('氏名'),
                        
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20)
                            ->label('電話番号'),
                        
                        Forms\Components\Textarea::make('address_line1')
                            ->required()
                            ->maxLength(255)
                            ->label('住所1'),
                        
                        Forms\Components\Textarea::make('address_line2')
                            ->maxLength(255)
                            ->label('住所2（任意）'),
                        
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('city')
                                    ->required()
                                    ->maxLength(100)
                                    ->label('市区町村'),
                                
                                Forms\Components\TextInput::make('state')
                                    ->required()
                                    ->maxLength(100)
                                    ->label('都道府県'),
                                
                                Forms\Components\TextInput::make('zip')
                                    ->required()
                                    ->maxLength(20)
                                    ->label('郵便番号'),
                            ]),
                        
                        Forms\Components\Select::make('country')
                            ->options([
                                'JP' => '日本',
                                'US' => 'アメリカ合衆国',
                                'CA' => 'カナダ',
                                'GB' => 'イギリス',
                                'AU' => 'オーストラリア',
                                'DE' => 'ドイツ',
                                'FR' => 'フランス',
                                'IT' => 'イタリア',
                                'ES' => 'スペイン',
                                'NL' => 'オランダ',
                                'IN' => 'インド',
                                'CN' => '中国',
                                'KR' => '韓国',
                                'SG' => 'シンガポール',
                                'HK' => '香港',
                            ])
                            ->required()
                            ->default('JP')
                            ->label('国'),
                        
                        Forms\Components\Toggle::make('is_default')
                            ->label('デフォルト住所')
                            ->inline(false)
                            ->helperText('ユーザーのデフォルト住所として設定'),
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
                    ->label('ユーザー')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('氏名')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('phone')
                    ->label('電話')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('address_line1')
                    ->label('住所')
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
                    ->label('郵便番号')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('country')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_default')
                    ->label('デフォルト')
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
                    ->placeholder('すべてのユーザー'),
                
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('デフォルト住所'),
                
                Tables\Filters\SelectFilter::make('country')
                    ->options([
                        'JP' => '日本',
                        'US' => 'アメリカ合衆国',
                        'CA' => 'カナダ',
                        'GB' => 'イギリス',
                        'AU' => 'オーストラリア',
                        'DE' => 'ドイツ',
                        'FR' => 'フランス',
                        'IT' => 'イタリア',
                        'ES' => 'スペイン',
                        'NL' => 'オランダ',
                        'IN' => 'インド',
                        'CN' => '中国',
                        'KR' => '韓国',
                        'SG' => 'シンガポール',
                        'HK' => '香港',
                    ])
                    ->placeholder('すべての国'),
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