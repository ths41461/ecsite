<?php

namespace App\Observers;

use App\Models\ProductImage;
use App\Services\ImageService;

class ProductImageObserver
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function created(ProductImage $productImage): void
    {
        // In a real implementation, this would process the image and create derivatives
        // $this->imageService->createImageDerivatives($productImage->path);
    }

    public function updated(ProductImage $productImage): void
    {
        // Handle updates if needed
    }

    public function deleted(ProductImage $productImage): void
    {
        // Delete the image file and its derivatives
        $this->imageService->deleteImageWithDerivatives($productImage->path);
    }

    public function restored(ProductImage $productImage): void
    {
        // Handle restoration if needed
    }
}