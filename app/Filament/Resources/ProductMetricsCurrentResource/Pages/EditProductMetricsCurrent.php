<?php

namespace App\Filament\Resources\ProductMetricsCurrentResource\Pages;

use App\Filament\Resources\ProductMetricsCurrentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductMetricsCurrent extends EditRecord
{
    protected static string $resource = ProductMetricsCurrentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}