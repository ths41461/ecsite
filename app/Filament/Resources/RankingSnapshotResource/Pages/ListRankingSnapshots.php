<?php

namespace App\Filament\Resources\RankingSnapshotResource\Pages;

use App\Filament\Resources\RankingSnapshotResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRankingSnapshots extends ListRecords
{
    protected static string $resource = RankingSnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}