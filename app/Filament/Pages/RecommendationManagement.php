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

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 99;

    protected static ?string $title = 'Recommendation System';

    protected static ?string $navigationLabel = 'Recommendations';

    public ?string $lastRankingRun = null;

    public ?string $lastMetricsRun = null;

    public function mount(): void
    {
        // For now, we'll just initialize the properties
        $this->lastRankingRun = 'Never run';
        $this->lastMetricsRun = 'Never run';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('compute-rankings')
                ->label('Compute Rankings')
                ->button()
                ->color('primary')
                ->action(function (): void {
                    $exitCode = Artisan::call('rank:refresh');

                    if ($exitCode === 0) {
                        $this->dispatch('notification', [
                            'title' => 'Success',
                            'message' => 'Rankings computed successfully!',
                            'status' => 'success'
                        ]);
                        $this->lastRankingRun = now()->format('Y-m-d H:i:s');
                    } else {
                        $this->dispatch('notification', [
                            'title' => 'Error',
                            'message' => 'Failed to compute rankings. Check logs for details.',
                            'status' => 'error'
                        ]);
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Compute Rankings')
                ->modalDescription('This will recompute all product rankings based on current metrics. This may take a few moments.')
                ->modalSubmitActionLabel('Compute'),

            Action::make('recompute-metrics')
                ->label('Recompute Metrics')
                ->button()
                ->color('warning')
                ->action(function (): void {
                    $exitCode = Artisan::call('metrics:recompute-products');

                    if ($exitCode === 0) {
                        $this->dispatch('notification', [
                            'title' => 'Success',
                            'message' => 'Metrics recomputed successfully!',
                            'status' => 'success'
                        ]);
                        $this->lastMetricsRun = now()->format('Y-m-d H:i:s');
                    } else {
                        $this->dispatch('notification', [
                            'title' => 'Error',
                            'message' => 'Failed to recompute metrics. Check logs for details.',
                            'status' => 'error'
                        ]);
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Recompute Metrics')
                ->modalDescription('This will recompute all product metrics from recent events and orders. This may take a few moments.')
                ->modalSubmitActionLabel('Recompute'),
        ];
    }
}