<?php

namespace App\Filament\Resources\ProductMetricsCurrentResource\Pages;

use App\Filament\Resources\ProductMetricsCurrentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProductMetricsCurrent extends ViewRecord
{
    protected static string $resource = ProductMetricsCurrentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}