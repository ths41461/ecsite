<?php

namespace App\Filament\Resources\PaymentStatusResource\Pages;

use App\Filament\Resources\PaymentStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentStatus extends ViewRecord
{
    protected static string $resource = PaymentStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}