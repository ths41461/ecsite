<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class RecommendationManagement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static string $view = 'filament.pages.recommendation-management';

    protected static ?string $navigationGroup = 'システム';

    protected static ?int $navigationSort = 99;

    protected static ?string $title = 'レコメンデーションシステム';

    protected static ?string $navigationLabel = 'レコメンデーション';

    public ?string $lastRankingRun = null;

    public ?string $lastMetricsRun = null;

    public function mount(): void
    {
        // For now, we'll just initialize the properties
        $this->lastRankingRun = '実行されていません';
        $this->lastMetricsRun = '実行されていません';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('compute-rankings')
                ->label('ランキングを計算')
                ->button()
                ->color('primary')
                ->action(function (): void {
                    $exitCode = Artisan::call('rank:refresh');

                    if ($exitCode === 0) {
                        $this->dispatch('notification', [
                            'title' => '成功',
                            'message' => 'ランキングが正常に計算されました！',
                            'status' => 'success'
                        ]);
                        $this->lastRankingRun = now()->format('Y-m-d H:i:s');
                    } else {
                        $this->dispatch('notification', [
                            'title' => 'エラー',
                            'message' => 'ランキングの計算に失敗しました。詳細はログを確認してください。',
                            'status' => 'error'
                        ]);
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('ランキングを計算')
                ->modalDescription('これは現在のメトリクスに基づいてすべての商品ランキングを再計算します。少し時間がかかる場合があります。')
                ->modalSubmitActionLabel('計算'),

            Action::make('recompute-metrics')
                ->label('メトリクスを再計算')
                ->button()
                ->color('warning')
                ->action(function (): void {
                    $exitCode = Artisan::call('metrics:recompute-products');

                    if ($exitCode === 0) {
                        $this->dispatch('notification', [
                            'title' => '成功',
                            'message' => 'メトリクスが正常に再計算されました！',
                            'status' => 'success'
                        ]);
                        $this->lastMetricsRun = now()->format('Y-m-d H:i:s');
                    } else {
                        $this->dispatch('notification', [
                            'title' => 'エラー',
                            'message' => 'メトリクスの再計算に失敗しました。詳細はログを確認してください。',
                            'status' => 'error'
                        ]);
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('メトリクスを再計算')
                ->modalDescription('これは最近のイベントと注文からすべての商品メトリクスを再計算します。少し時間がかかる場合があります。')
                ->modalSubmitActionLabel('再計算'),
        ];
    }
}