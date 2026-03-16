<?php

namespace App\Filament\Resources\CouponRedemptionResource\Pages;

use App\Filament\Resources\CouponRedemptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCouponRedemption extends ViewRecord
{
    protected static string $resource = CouponRedemptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}