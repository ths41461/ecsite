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

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Slider Information')
                    ->description('Basic slider information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\FileUpload::make('image_path')
                                    ->label('Image')
                                    ->required()
                                    ->image()
                                    ->directory('sliders')
                                    ->visibility('public')
                                    ->imageEditor()
                                    ->maxSize(2048)
                                    ->placeholder('Upload slider image')
                                    ->helperText('Recommended size: 1200x400 pixels'),
                                Forms\Components\TextInput::make('tagline')
                                    ->maxLength(80)
                                    ->placeholder('Enter tagline (optional)')
                                    ->helperText('Short tagline displayed above title'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->maxLength(120)
                                    ->placeholder('Enter title (optional)')
                                    ->helperText('Main title text for the slider'),
                                Forms\Components\TextInput::make('subtitle')
                                    ->maxLength(160)
                                    ->placeholder('Enter subtitle (optional)')
                                    ->helperText('Subtitle text displayed below the title'),
                            ]),
                        Forms\Components\TextInput::make('link_url')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('Enter link URL (optional)')
                            ->helperText('URL to navigate when slider is clicked'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Schedule & Status')
                    ->description('Set when the slider is active and its status')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->inline(false)
                                    ->helperText('Toggle to enable/disable this slider'),
                                Forms\Components\TextInput::make('sort')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Sort order (lower numbers appear first)'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('starts_at')
                                    ->label('Start Date/Time')
                                    ->helperText('Slider will become active at this time'),
                                Forms\Components\DateTimePicker::make('ends_at')
                                    ->label('End Date/Time')
                                    ->helperText('Slider will become inactive after this time'),
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
                    ->label('Image')
                    ->size(80)
                    ->defaultImageUrl(asset('images/slider-placeholder.png')),
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tagline')
                    ->label('Tagline')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subtitle')
                    ->label('Subtitle')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('link_url')
                    ->label('Link')
                    ->url(fn (Slider $record): ?string => $record->link_url)
                    ->openUrlInNewTab()
                    ->limit(40),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Starts At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Ends At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort')
                    ->label('Sort')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\Filter::make('starts_at')
                    ->label('Active From')
                    ->form([
                        Forms\Components\DatePicker::make('starts_at')
                            ->label('Start Date'),
                        Forms\Components\DatePicker::make('ends_at')
                            ->label('End Date'),
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
                    ->label('Currently Active')
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