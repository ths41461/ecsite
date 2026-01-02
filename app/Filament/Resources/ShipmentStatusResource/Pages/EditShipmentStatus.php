<?php

namespace App\Filament\Resources\ShipmentStatusResource\Pages;

use App\Filament\Resources\ShipmentStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShipmentStatus extends EditRecord
{
    protected static string $resource = ShipmentStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}