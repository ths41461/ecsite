<?php

namespace App\Filament\Pages;

use Filament\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Illuminate\Support\Facades\Auth;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithFormActions;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.settings';

    protected static ?string $title = 'ユーザー設定';

    protected static ?string $navigationLabel = '設定';

    protected static ?string $slug = 'settings';

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();
        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('プロフィール情報')
                    ->description('アカウントプロフィール情報を更新')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('名前'),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(table: 'users', ignorable: fn () => auth()->user())
                            ->label('メールアドレス'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data')
            ->model(Auth::user());
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('設定を保存')
                ->button()
                ->action('save'),
            Actions\Action::make('cancel')
                ->label('キャンセル')
                ->color('secondary')
                ->url(static::getUrl()),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $user = Auth::user();
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $this->notify('success', '設定が正常に保存されました！');
    }
}