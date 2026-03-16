<?php

namespace App\Filament\Resources\ProductMetricsDailyResource\Pages;

use App\Filament\Resources\ProductMetricsDailyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductMetricsDaily extends EditRecord
{
    protected static string $resource = ProductMetricsDailyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}