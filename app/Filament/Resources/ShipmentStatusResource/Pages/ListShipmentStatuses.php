<?php

namespace App\Filament\Resources\ShipmentStatusResource\Pages;

use App\Filament\Resources\ShipmentStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShipmentStatuses extends ListRecords
{
    protected static string $resource = ShipmentStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}