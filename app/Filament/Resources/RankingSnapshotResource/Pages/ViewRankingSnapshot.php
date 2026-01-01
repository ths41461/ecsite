<?php

namespace App\Filament\Resources\RankingSnapshotResource\Pages;

use App\Filament\Resources\RankingSnapshotResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRankingSnapshot extends ViewRecord
{
    protected static string $resource = RankingSnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}