<?php

namespace App\Filament\Resources\UserAddressResource\Pages;

use App\Filament\Resources\UserAddressResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUserAddress extends ViewRecord
{
    protected static string $resource = UserAddressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}