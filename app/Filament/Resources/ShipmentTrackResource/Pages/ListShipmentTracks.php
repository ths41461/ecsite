<?php

namespace App\Filament\Resources\ShipmentTrackResource\Pages;

use App\Filament\Resources\ShipmentTrackResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShipmentTracks extends ListRecords
{
    protected static string $resource = ShipmentTrackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}