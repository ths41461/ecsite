<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SliderResource\Pages;
use App\Filament\Resources\SliderResource\RelationManagers;
use App\Models\Slider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SliderResource extends Resource
{
    protected static ?string $model = Slider::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'マーケティング';

    protected static ?string $navigationLabel = 'スライダー';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('スライダー情報')
                    ->description('基本的なスライダー情報')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\FileUpload::make('image_path')
                                    ->label('画像')
                                    ->required()
                                    ->image()
                                    ->directory('sliders')
                                    ->visibility('public')
                                    ->imageEditor()
                                    ->maxSize(2048)
                                    ->placeholder('スライダー画像をアップロード')
                                    ->helperText('推奨サイズ：1200x400ピクセル'),
                                Forms\Components\TextInput::make('tagline')
                                    ->maxLength(80)
                                    ->placeholder('タグラインを入力してください（任意）')
                                    ->helperText('タイトルの上に表示される短いタグライン'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->maxLength(120)
                                    ->placeholder('タイトルを入力してください（任意）')
                                    ->helperText('スライダーのメインタイトルテキスト'),
                                Forms\Components\TextInput::make('subtitle')
                                    ->maxLength(160)
                                    ->placeholder('サブタイトルを入力してください（任意）')
                                    ->helperText('タイトルの下に表示されるサブタイトルテキスト'),
                            ]),
                        Forms\Components\TextInput::make('link_url')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('リンクURLを入力してください（任意）')
                            ->helperText('スライダーがクリックされたときに移動するURL'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('スケジュールとステータス')
                    ->description('スライダーがアクティブになる時期とそのステータスを設定')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('有効')
                                    ->default(true)
                                    ->inline(false)
                                    ->helperText('このスライダーを有効/無効にするトグル'),
                                Forms\Components\TextInput::make('sort')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('並び順（小さい数字が最初に表示されます）'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('starts_at')
                                    ->label('開始日時')
                                    ->helperText('スライダーがこの時間にアクティブになります'),
                                Forms\Components\DateTimePicker::make('ends_at')
                                    ->label('終了日時')
                                    ->helperText('スライダーがこの時間の後に非アクティブになります'),
                            ]),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('画像')
                    ->size(80)
                    ->defaultImageUrl(asset('images/slider-placeholder.png')),
                Tables\Columns\TextColumn::make('title')
                    ->label('タイトル')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tagline')
                    ->label('タグライン')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subtitle')
                    ->label('サブタイトル')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('link_url')
                    ->label('リンク')
                    ->url(fn (Slider $record): ?string => $record->link_url)
                    ->openUrlInNewTab()
                    ->limit(40),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('有効')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('開始日時')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('終了日時')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort')
                    ->label('並び順')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('有効'),
                Tables\Filters\Filter::make('starts_at')
                    ->label('アクティブ期間')
                    ->form([
                        Forms\Components\DatePicker::make('starts_at')
                            ->label('開始日'),
                        Forms\Components\DatePicker::make('ends_at')
                            ->label('終了日'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['starts_at'],
                                fn (Builder $query, $value): Builder => $query->where('starts_at', '>=', $value)
                            )
                            ->when(
                                $data['ends_at'],
                                fn (Builder $query, $value): Builder => $query->where('starts_at', '<=', $value)
                            );
                    }),
                Tables\Filters\Filter::make('current')
                    ->label('現在アクティブ')
                    ->query(fn (Builder $query): Builder => $query->current()),
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
            ->defaultSort('sort', 'asc')
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            // No relations for Slider model
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSliders::route('/'),
            'create' => Pages\CreateSlider::route('/create'),
            'view' => Pages\ViewSlider::route('/{record}'),
            'edit' => Pages\EditSlider::route('/{record}/edit'),
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