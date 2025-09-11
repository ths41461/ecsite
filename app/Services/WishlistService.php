<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class WishlistService
{
    private string $prefix = 'wishlist:';
    private int $ttlSeconds = 60 * 60 * 24 * 30; // 30 days

    private function key(string $sessionId): string
    {
        return $this->prefix . $sessionId;
    }

    /**
     * Add a product to wishlist (DB if logged in, Redis if guest).
     * Returns the normalized items list.
     */
    public function add(string $sessionId, int $productId): array
    {
        $userId = Auth::id();

        if ($userId) {
            Wishlist::firstOrCreate([
                'user_id'    => $userId,
                'product_id' => $productId,
            ]);
        } else {
            $key = $this->key($sessionId);
            Redis::sadd($key, (string) $productId);
            Redis::expire($key, $this->ttlSeconds);
        }

        return $this->items($sessionId);
    }

    /**
     * Remove a product from wishlist.
     */
    public function remove(string $sessionId, int $productId): array
    {
        $userId = Auth::id();

        if ($userId) {
            Wishlist::where('user_id', $userId)->where('product_id', $productId)->delete();
        } else {
            $key = $this->key($sessionId);
            Redis::srem($key, (string) $productId);
        }

        return $this->items($sessionId);
    }

    /**
     * List wishlist items as lightweight product cards.
     */
    public function items(string $sessionId): array
    {
        $userId = Auth::id();
        $ids = [];

        if ($userId) {
            $ids = Wishlist::where('user_id', $userId)->pluck('product_id')->all();
        } else {
            $key = $this->key($sessionId);
            $ids = array_map('intval', Redis::smembers($key) ?? []);
        }

        $ids = array_values(array_unique(array_filter($ids)));

        if (empty($ids)) {
            return ['items' => [], 'count' => 0];
        }

        // Fetch minimal fields and hero image; products table has no direct `image` column.
        $products = Product::query()
            ->whereIn('id', $ids)
            ->select(['id', 'name', 'slug'])
            ->with(['heroImage:id,product_id,path,alt,rank'])
            ->get();

        // Preserve user’s add order roughly by reordering to $ids
        $byId = [];
        foreach ($products as $p) {
            $imagePath = $p->heroImage?->path;
            $imageUrl  = $imagePath
                ? (str_starts_with($imagePath, 'http') || str_starts_with($imagePath, '/') ? $imagePath : Storage::url($imagePath))
                : null;
            $byId[$p->id] = [
                'id'    => $p->id,
                'name'  => $p->name,
                'slug'  => $p->slug,
                'image' => $imageUrl,
            ];
        }
        $items = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) $items[] = $byId[$id];
        }

        return ['items' => $items, 'count' => count($items)];
    }

    /**
     * Call later when auth exists: merge guest Redis set into DB wishlist.
     */
    public function merge(string $sessionId, int $userId): void
    {
        $key = $this->key($sessionId);
        $ids = array_map('intval', Redis::smembers($key) ?? []);

        foreach (array_unique($ids) as $pid) {
            Wishlist::firstOrCreate(['user_id' => $userId, 'product_id' => $pid]);
        }
        if (!empty($ids)) {
            Redis::del($key);
        }
    }
}
