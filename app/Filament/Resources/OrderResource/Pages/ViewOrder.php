<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            \App\Filament\Resources\OrderResource\RelationManagers\OrderItemsRelationManager::class,
            \App\Filament\Resources\OrderResource\RelationManagers\OrderShipmentsRelationManager::class,
            \App\Filament\Resources\OrderResource\RelationManagers\OrderPaymentsRelationManager::class,
            \App\Filament\Resources\OrderResource\RelationManagers\OrderTimelineRelationManager::class,
        ];
    }
}