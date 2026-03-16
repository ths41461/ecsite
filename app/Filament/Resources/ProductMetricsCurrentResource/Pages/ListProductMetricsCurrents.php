<?php

namespace App\Filament\Resources\ProductMetricsCurrentResource\Pages;

use App\Filament\Resources\ProductMetricsCurrentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductMetricsCurrents extends ListRecords
{
    protected static string $resource = ProductMetricsCurrentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}