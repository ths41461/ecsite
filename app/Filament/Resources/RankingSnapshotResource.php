<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RankingSnapshotResource\Pages;
use App\Filament\Resources\RankingSnapshotResource\RelationManagers;
use App\Models\RankingSnapshot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RankingSnapshotResource extends Resource
{
    protected static ?string $model = RankingSnapshot::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = '分析';

    protected static ?string $navigationLabel = 'ランキングスナップショット';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('ランキング情報')
                    ->description('商品ランキングに関する情報')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('商品'),
                        Forms\Components\TextInput::make('scope')
                            ->required()
                            ->maxLength(255)
                            ->default('overall')
                            ->label('範囲'),
                        Forms\Components\TextInput::make('rank')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->label('順位'),
                        Forms\Components\TextInput::make('score')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->label('スコア'),
                        Forms\Components\DateTimePicker::make('computed_at')
                            ->required()
                            ->label('計算日時'),
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
                Tables\Columns\TextColumn::make('product.name')
                    ->label('商品')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scope')
                    ->label('範囲')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rank')
                    ->label('順位')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('score')
                    ->label('スコア')
                    ->numeric()
                    ->formatStateUsing(fn ($state) => number_format($state, 2))
                    ->sortable(),
                Tables\Columns\TextColumn::make('computed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('scope')
                    ->options([
                        'overall' => '全体',
                        'category' => 'カテゴリ別',
                        'brand' => 'ブランド別',
                    ])
                    ->placeholder('すべての範囲'),
                Tables\Filters\Filter::make('computed_at')
                    ->form([
                        Forms\Components\DatePicker::make('computed_from')
                            ->label('計算日範囲（開始）'),
                        Forms\Components\DatePicker::make('computed_until')
                            ->label('計算日範囲（終了）'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['computed_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('computed_at', '>=', $date)
                            )
                            ->when(
                                $data['computed_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('computed_at', '<=', $date)
                            );
                    }),
                Tables\Filters\SelectFilter::make('product.category_id')
                    ->relationship('product.category', 'name')
                    ->label('カテゴリ')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('product.brand_id')
                    ->relationship('product.brand', 'name')
                    ->label('ブランド')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('rank', 'asc');
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
            'index' => Pages\ListRankingSnapshots::route('/'),
            'create' => Pages\CreateRankingSnapshot::route('/create'),
            'view' => Pages\ViewRankingSnapshot::route('/{record}'),
            'edit' => Pages\EditRankingSnapshot::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'ランキングスナップショット';
    }

    public static function getPluralModelLabel(): string
    {
        return 'ランキングスナップショット';
    }
}