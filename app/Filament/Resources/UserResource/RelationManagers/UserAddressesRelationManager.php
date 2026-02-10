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
                Forms\Components\Section::make('住所情報')
                    ->description('ユーザーの住所情報')
                    ->schema([
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
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
                    ->label('国')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state) => match ($state) {
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
                        default => $state,
                    }),
                
                Tables\Columns\IconColumn::make('is_default')
                    ->label('デフォルト')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
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
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('作成'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('表示'),
                Tables\Actions\EditAction::make()
                    ->label('編集'),
                Tables\Actions\DeleteAction::make()
                    ->label('削除'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('削除'),
                ]),
            ]);
    }
}