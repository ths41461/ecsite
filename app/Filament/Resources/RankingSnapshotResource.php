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

    protected static ?string $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Ranking Information')
                    ->description('Information about the product ranking')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Product'),
                        Forms\Components\TextInput::make('scope')
                            ->required()
                            ->maxLength(255)
                            ->default('overall')
                            ->label('Scope'),
                        Forms\Components\TextInput::make('rank')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->label('Rank'),
                        Forms\Components\TextInput::make('score')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->label('Score'),
                        Forms\Components\DateTimePicker::make('computed_at')
                            ->required()
                            ->label('Computed At'),
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
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scope')
                    ->label('Scope')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rank')
                    ->label('Rank')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
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
                        'overall' => 'Overall',
                        'category' => 'By Category',
                        'brand' => 'By Brand',
                    ])
                    ->placeholder('All Scopes'),
                Tables\Filters\Filter::make('computed_at')
                    ->form([
                        Forms\Components\DatePicker::make('computed_from')
                            ->label('Computed From'),
                        Forms\Components\DatePicker::make('computed_until')
                            ->label('Computed Until'),
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
                    ->label('Category')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('product.brand_id')
                    ->relationship('product.brand', 'name')
                    ->label('Brand')
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
}