<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'システム';

    protected static ?string $navigationLabel = '監査ログ';

    protected static ?int $navigationSort = 99;

    public static function getNavigationLabel(): string
    {
        return '監査ログ';
    }

    public static function getModelLabel(): string
    {
        return '監査ログ';
    }

    public static function getPluralModelLabel(): string
    {
        return '監査ログ';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Select::make('user_id')
                    ->label('ユーザー')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                
                \Filament\Forms\Components\TextInput::make('user_name')
                    ->label('ユーザー名')
                    ->maxLength(255),
                
                \Filament\Forms\Components\Select::make('action')
                    ->options([
                        'created' => '作成',
                        'updated' => '更新',
                        'deleted' => '削除',
                        'restored' => '復元',
                        'viewed' => '表示',
                        'exported' => 'エクスポート',
                    ])
                    ->required()
                    ->searchable(),
                
                \Filament\Forms\Components\TextInput::make('model_type')
                    ->label('モデルタイプ')
                    ->maxLength(255)
                    ->required(),
                
                \Filament\Forms\Components\TextInput::make('model_id')
                    ->label('モデルID')
                    ->maxLength(255),
                
                \Filament\Forms\Components\TextInput::make('model_name')
                    ->label('モデル名')
                    ->maxLength(255),
                
                \Filament\Forms\Components\Textarea::make('old_values')
                    ->label('旧値')
                    ->columnSpanFull(),
                
                \Filament\Forms\Components\Textarea::make('new_values')
                    ->label('新値')
                    ->columnSpanFull(),
                
                \Filament\Forms\Components\TextInput::make('url')
                    ->label('URL')
                    ->maxLength(255)
                    ->columnSpanFull(),
                
                \Filament\Forms\Components\TextInput::make('ip_address')
                    ->label('IPアドレス')
                    ->maxLength(45),
                
                \Filament\Forms\Components\Textarea::make('user_agent')
                    ->label('ユーザーエージェント')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('ユーザー')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('user_name')
                    ->label('ユーザー名')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'viewed' => 'info',
                        'exported' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('model_type')
                    ->label('モデルタイプ')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(function ($state) {
                        return class_basename($state);
                    }),
                
                Tables\Columns\TextColumn::make('model_id')
                    ->label('モデルID')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('model_name')
                    ->label('モデル名')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('日時')
                    ->dateTime()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IPアドレス')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options([
                        'created' => '作成',
                        'updated' => '更新',
                        'deleted' => '削除',
                        'restored' => '復元',
                        'viewed' => '表示',
                        'exported' => 'エクスポート',
                    ]),
                
                Tables\Filters\SelectFilter::make('model_type')
                    ->options([
                        'App\Models\Product' => '商品',
                        'App\Models\User' => 'ユーザー',
                        'App\Models\Order' => '注文',
                        'App\Models\Category' => 'カテゴリ',
                        'App\Models\Brand' => 'ブランド',
                        'App\Models\Coupon' => 'クーポン',
                        'App\Models\Shipment' => '出荷',
                        'App\Models\Slider' => 'スライダー',
                        'App\Models\Review' => 'レビュー',
                        'App\Models\Inventory' => '在庫',
                    ])
                    ->label('モデルタイプ'),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('作成日範囲（開始）'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('作成日範囲（終了）'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }

    public static function can(string $action, $record = null): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->isAdmin();
    }
}