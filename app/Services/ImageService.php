<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ImageService
{
    /**
     * Process and store product image with potential derivatives
     * Note: This is a placeholder implementation. In a production environment,
     * you would use a library like Intervention Image to create actual derivatives.
     *
     * @param UploadedFile $file The uploaded image file
     * @param string $directory Directory to store the image
     * @return string Path to the stored image
     */
    public function storeProductImage(UploadedFile $file, string $directory = 'product-images'): string
    {
        // Store the original image
        $path = $file->store($directory, 'public');

        // In a real implementation, you would create derivatives here
        // For now, this is a placeholder for where derivative creation would happen
        $this->createImageDerivatives($path);

        return $path;
    }

    /**
     * Create image derivatives for a product image
     * This is a placeholder method that would use a library like Intervention Image
     * to create different sizes of the image for various uses (thumbnails, etc.)
     *
     * @param string $imagePath Path to the original image
     * @return void
     */
    public function createImageDerivatives(string $imagePath): void
    {
        // This is where you would implement actual image processing
        // using a library like Intervention Image to create thumbnails, etc.
        // For example:
        // - Create a thumbnail for product listings
        // - Create a medium size for product detail pages
        // - Create a large size for lightbox views
    }

    /**
     * Delete all image files including derivatives when the original image is deleted
     *
     * @param string $imagePath Path to the original image
     * @return void
     */
    public function deleteImageWithDerivatives(string $imagePath): void
    {
        // Delete the original image
        if (Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }

        // In a real implementation, you would also delete all derivative images here
        // This is where you would delete the thumbnail, medium, large versions, etc.
    }
}