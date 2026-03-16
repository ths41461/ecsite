<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentStatusResource\Pages;
use App\Filament\Resources\PaymentStatusResource\RelationManagers;
use App\Models\PaymentStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentStatusResource extends Resource
{
    protected static ?string $model = PaymentStatus::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = '注文';

    protected static ?string $navigationLabel = '支払いステータス';

    protected static ?int $navigationSort = 17;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('ステータス情報')
                    ->description('支払いステータスに関する情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->required()
                                    ->maxLength(50)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('ステータスコードを入力してください（例：pending, paid）')
                                    ->helperText('このステータスのユニークコード'),
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('ステータス名を入力してください（例：Pending, Paid）')
                                    ->helperText('このステータスの表示名'),
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->maxLength(65535)
                            ->placeholder('このステータスの説明を入力してください')
                            ->helperText('このステータスを説明するオプションの説明'),
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
                Tables\Columns\TextColumn::make('code')
                    ->label('コード')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('名前')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('説明')
                    ->limit(50)
                    ->sortable(),
                Tables\Columns\TextColumn::make('payments_count')
                    ->label('支払い数')
                    ->counts('payments')
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
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('作成日範囲（開始）'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('作成日範囲（終了）'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date)
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
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PaymentStatusPaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentStatuses::route('/'),
            'create' => Pages\CreatePaymentStatus::route('/create'),
            'view' => Pages\ViewPaymentStatus::route('/{record}'),
            'edit' => Pages\EditPaymentStatus::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return '支払いステータス';
    }

    public static function getPluralModelLabel(): string
    {
        return '支払いステータス';
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