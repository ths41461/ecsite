<?php

namespace App\Filament\Resources\RankingSnapshotResource\Pages;

use App\Filament\Resources\RankingSnapshotResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRankingSnapshot extends EditRecord
{
    protected static string $resource = RankingSnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}