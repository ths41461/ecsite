<?php

namespace App\Filament\Resources\ProductMetricsDailyResource\Pages;

use App\Filament\Resources\ProductMetricsDailyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProductMetricsDaily extends ViewRecord
{
    protected static string $resource = ProductMetricsDailyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}