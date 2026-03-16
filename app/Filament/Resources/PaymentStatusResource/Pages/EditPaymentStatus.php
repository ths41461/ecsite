<?php

namespace App\Filament\Resources\PaymentStatusResource\Pages;

use App\Filament\Resources\PaymentStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentStatus extends EditRecord
{
    protected static string $resource = PaymentStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}