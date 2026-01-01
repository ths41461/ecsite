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

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 99;

    public static function getNavigationLabel(): string
    {
        return 'Audit Logs';
    }

    public static function getModelLabel(): string
    {
        return 'Audit Log';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Audit Logs';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                
                \Filament\Forms\Components\TextInput::make('user_name')
                    ->label('User Name')
                    ->maxLength(255),
                
                \Filament\Forms\Components\Select::make('action')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'restored' => 'Restored',
                        'viewed' => 'Viewed',
                        'exported' => 'Exported',
                    ])
                    ->required()
                    ->searchable(),
                
                \Filament\Forms\Components\TextInput::make('model_type')
                    ->label('Model Type')
                    ->maxLength(255)
                    ->required(),
                
                \Filament\Forms\Components\TextInput::make('model_id')
                    ->label('Model ID')
                    ->maxLength(255),
                
                \Filament\Forms\Components\TextInput::make('model_name')
                    ->label('Model Name')
                    ->maxLength(255),
                
                \Filament\Forms\Components\Textarea::make('old_values')
                    ->label('Old Values')
                    ->columnSpanFull(),
                
                \Filament\Forms\Components\Textarea::make('new_values')
                    ->label('New Values')
                    ->columnSpanFull(),
                
                \Filament\Forms\Components\TextInput::make('url')
                    ->label('URL')
                    ->maxLength(255)
                    ->columnSpanFull(),
                
                \Filament\Forms\Components\TextInput::make('ip_address')
                    ->label('IP Address')
                    ->maxLength(45),
                
                \Filament\Forms\Components\Textarea::make('user_agent')
                    ->label('User Agent')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('user_name')
                    ->label('User Name')
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
                    ->label('Model Type')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(function ($state) {
                        return class_basename($state);
                    }),
                
                Tables\Columns\TextColumn::make('model_id')
                    ->label('Model ID')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('model_name')
                    ->label('Model Name')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'restored' => 'Restored',
                        'viewed' => 'Viewed',
                        'exported' => 'Exported',
                    ]),
                
                Tables\Filters\SelectFilter::make('model_type')
                    ->options([
                        'App\Models\Product' => 'Product',
                        'App\Models\User' => 'User',
                        'App\Models\Order' => 'Order',
                        'App\Models\Category' => 'Category',
                        'App\Models\Brand' => 'Brand',
                        'App\Models\Coupon' => 'Coupon',
                        'App\Models\Shipment' => 'Shipment',
                        'App\Models\Slider' => 'Slider',
                        'App\Models\Review' => 'Review',
                        'App\Models\Inventory' => 'Inventory',
                    ])
                    ->label('Model Type'),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
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