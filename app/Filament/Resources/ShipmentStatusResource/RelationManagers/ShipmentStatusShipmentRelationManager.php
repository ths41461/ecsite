<?php

namespace App\Filament\Resources\ShipmentStatusResource\RelationManagers;

use App\Models\Shipment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShipmentStatusShipmentRelationManager extends RelationManager
{
    protected static string $relationship = 'shipments';

    protected static ?string $recordTitleAttribute = 'tracking_number';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('order_id')
                    ->relationship('order', 'id')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('注文'),
                Forms\Components\TextInput::make('carrier')
                    ->required()
                    ->maxLength(100)
                    ->label('運送会社'),
                Forms\Components\TextInput::make('tracking_number')
                    ->required()
                    ->maxLength(255)
                    ->label('追跡番号'),
                Forms\Components\Select::make('status')
                    ->options([
                        'label' => 'ラベル作成済み',
                        'shipped' => '発送済み',
                        'in_transit' => '輸送中',
                        'delivered' => '配達済み',
                        'exception' => '例外',
                    ])
                    ->default('label')
                    ->required()
                    ->label('ステータス'),
                Forms\Components\DateTimePicker::make('shipped_at')
                    ->label('発送日時'),
                Forms\Components\DateTimePicker::make('delivered_at')
                    ->label('配達日時'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.id')
                    ->label('注文ID')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('carrier')
                    ->label('運送会社')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tracking_number')
                    ->label('追跡番号')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'label' => 'info',
                        'shipped' => 'primary',
                        'in_transit' => 'warning',
                        'delivered' => 'success',
                        'exception' => 'danger',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'label' => 'ラベル作成済み',
                        'shipped' => '発送済み',
                        'in_transit' => '輸送中',
                        'delivered' => '配達済み',
                        'exception' => '例外',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('shipped_at')
                    ->label('発送日時')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivered_at')
                    ->label('配達日時')
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
                        'label' => 'ラベル作成済み',
                        'shipped' => '発送済み',
                        'in_transit' => '輸送中',
                        'delivered' => '配達済み',
                        'exception' => '例外',
                    ])
                    ->placeholder('すべてのステータス'),
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('削除'),
                ]),
            ]);
    }
}