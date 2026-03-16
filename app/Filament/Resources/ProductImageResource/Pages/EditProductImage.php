<?php

namespace App\Filament\Resources\ProductImageResource\Pages;

use App\Filament\Resources\ProductImageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductImage extends EditRecord
{
    protected static string $resource = ProductImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // If this image is marked as hero, ensure no other image for this product is hero
        if ($data['is_hero'] ?? false) {
            \App\Models\ProductImage::where('product_id', $data['product_id'])
                ->where('id', '!=', $this->record->id)
                ->where('is_hero', true)
                ->update(['is_hero' => false]);
        }

        return $data;
    }
}