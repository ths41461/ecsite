<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShipmentTrackResource\Pages;
use App\Filament\Resources\ShipmentTrackResource\RelationManagers;
use App\Models\ShipmentTrack;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShipmentTrackResource extends Resource
{
    protected static ?string $model = ShipmentTrack::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = '注文';

    protected static ?string $navigationLabel = '出荷追跡';

    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('追跡イベント情報')
                    ->description('出荷追跡イベントに関する情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('shipment_id')
                                    ->relationship('shipment', 'tracking_number')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('出荷'),
                                Forms\Components\TextInput::make('carrier')
                                    ->required()
                                    ->maxLength(100)
                                    ->label('運送会社'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('track_no')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('追跡番号'),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'packed' => '梱包済み',
                                        'label_created' => 'ラベル作成済み',
                                        'in_transit' => '輸送中',
                                        'out_for_delivery' => '配達準備中',
                                        'delivered' => '配達済み',
                                        'exception' => '例外',
                                        'returned' => '返品',
                                    ])
                                    ->required()
                                    ->label('ステータス'),
                            ]),
                        Forms\Components\DateTimePicker::make('event_time')
                            ->required()
                            ->label('イベント日時'),
                        Forms\Components\Textarea::make('raw_event_json')
                            ->rows(4)
                            ->columnSpanFull()
                            ->label('生イベントデータ')
                            ->helperText('運送会社ウェブフックからのJSONデータ'),
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
                Tables\Columns\TextColumn::make('shipment.tracking_number')
                    ->label('追跡番号')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('carrier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('track_no')
                    ->label('追跡番号')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'packed' => 'info',
                        'label_created' => 'info',
                        'in_transit' => 'warning',
                        'out_for_delivery' => 'primary',
                        'delivered' => 'success',
                        'exception' => 'danger',
                        'returned' => 'secondary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state)))
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
                        'packed' => '梱包済み',
                        'label_created' => 'ラベル作成済み',
                        'in_transit' => '輸送中',
                        'out_for_delivery' => '配達準備中',
                        'delivered' => '配達済み',
                        'exception' => '例外',
                        'returned' => '返品',
                    ])
                    ->placeholder('すべてのステータス'),
                Tables\Filters\SelectFilter::make('carrier')
                    ->options([
                        'fedex' => 'FedEx',
                        'ups' => 'UPS',
                        'dhl' => 'DHL',
                        'usps' => 'USPS',
                        'jp_post' => '日本郵便',
                        'other' => 'その他',
                    ])
                    ->placeholder('すべての運送会社'),
                Tables\Filters\Filter::make('event_time')
                    ->form([
                        Forms\Components\DatePicker::make('event_from')
                            ->label('イベント日範囲（開始）'),
                        Forms\Components\DatePicker::make('event_until')
                            ->label('イベント日範囲（終了）'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['event_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('event_time', '>=', $date)
                            )
                            ->when(
                                $data['event_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('event_time', '<=', $date)
                            );
                    }),
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
            ->defaultSort('event_time', 'desc');
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
            'index' => Pages\ListShipmentTracks::route('/'),
            'create' => Pages\CreateShipmentTrack::route('/create'),
            'view' => Pages\ViewShipmentTrack::route('/{record}'),
            'edit' => Pages\EditShipmentTrack::route('/{record}/edit'),
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