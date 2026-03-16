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
                    ->maxLength(100)
                    ->label('運送会社'),
                Forms\Components\TextInput::make('tracking_number')
                    ->required()
                    ->maxLength(255)
                    ->label('追跡番号'),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => '保留中',
                        'packed' => '梱包済み',
                        'in_transit' => '輸送中',
                        'delivered' => '配達済み',
                        'returned' => '返品',
                    ])
                    ->required()
                    ->label('ステータス'),
                Forms\Components\DateTimePicker::make('shipped_at')
                    ->label('発送日時'),
                Forms\Components\DateTimePicker::make('delivered_at')
                    ->label('配達日時'),
                Forms\Components\Textarea::make('timeline_json')
                    ->columnSpanFull()
                    ->label('タイムラインデータ'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tracking_number')
            ->columns([
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
                        'pending' => 'warning',
                        'packed' => 'info',
                        'in_transit' => 'primary',
                        'delivered' => 'success',
                        'returned' => 'danger',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending' => '保留中',
                        'packed' => '梱包済み',
                        'in_transit' => '輸送中',
                        'delivered' => '配達済み',
                        'returned' => '返品',
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => '保留中',
                        'packed' => '梱包済み',
                        'in_transit' => '輸送中',
                        'delivered' => '配達済み',
                        'returned' => '返品',
                    ])
                    ->placeholder('すべてのステータス'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('作成'),
            ])
            ->actions([
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