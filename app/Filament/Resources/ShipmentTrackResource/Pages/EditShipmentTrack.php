<?php

namespace App\Filament\Resources\ShipmentTrackResource\Pages;

use App\Filament\Resources\ShipmentTrackResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShipmentTrack extends EditRecord
{
    protected static string $resource = ShipmentTrackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}