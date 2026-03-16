<?php

namespace App\Filament\Resources\ProductImageResource\Pages;

use App\Filament\Resources\ProductImageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductImage extends CreateRecord
{
    protected static string $resource = ProductImageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If this image is marked as hero, ensure no other image for this product is hero
        if ($data['is_hero'] ?? false) {
            \App\Models\ProductImage::where('product_id', $data['product_id'])
                ->where('is_hero', true)
                ->update(['is_hero' => false]);
        }

        return $data;
    }
}