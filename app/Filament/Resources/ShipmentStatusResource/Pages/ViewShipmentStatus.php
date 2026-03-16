<?php

namespace App\Filament\Resources\ShipmentStatusResource\Pages;

use App\Filament\Resources\ShipmentStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewShipmentStatus extends ViewRecord
{
    protected static string $resource = ShipmentStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}