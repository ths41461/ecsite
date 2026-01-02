<?php

namespace App\Filament\Resources\ProductMetricsDailyResource\Pages;

use App\Filament\Resources\ProductMetricsDailyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductMetricsDailies extends ListRecords
{
    protected static string $resource = ProductMetricsDailyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}