<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class WishlistService
{
    private string $sessionPrefix = 'wishlist:';
    private string $userPrefix = 'wishlist:user:';
    private int $ttlSeconds = 60 * 60 * 24 * 30; // 30 days

    private function key(string $sessionId, ?int $userId = null): string
    {
        if ($userId) {
            return $this->userPrefix . $userId;
        }
        return $this->sessionPrefix . $sessionId;
    }

    /**
     * Add a product to wishlist (DB if logged in, Redis if guest).
     * Returns the normalized items list.
     */
    public function add(string $sessionId, int $productId, ?int $userId = null): array
    {
        $authenticatedUserId = $userId ?? Auth::id();

        if ($authenticatedUserId) {
            // For authenticated users, also store in Redis with user prefix for consistency
            $key = $this->key($sessionId, $authenticatedUserId);
            Redis::sadd($key, (string) $productId);
            Redis::expire($key, $this->ttlSeconds);
            
            // Also store in DB for persistence across sessions
            Wishlist::firstOrCreate([
                'user_id'    => $authenticatedUserId,
                'product_id' => $productId,
            ]);
        } else {
            // For guest users, store only in Redis with session key
            $key = $this->key($sessionId);
            Redis::sadd($key, (string) $productId);
            Redis::expire($key, $this->ttlSeconds);
        }

        return $this->items($sessionId, $authenticatedUserId);
    }

    /**
     * Remove a product from wishlist.
     */
    public function remove(string $sessionId, int $productId, ?int $userId = null): array
    {
        $authenticatedUserId = $userId ?? Auth::id();

        if ($authenticatedUserId) {
            // Remove from both Redis and DB for consistency
            $key = $this->key($sessionId, $authenticatedUserId);
            Redis::srem($key, (string) $productId);
            
            Wishlist::where('user_id', $authenticatedUserId)->where('product_id', $productId)->delete();
        } else {
            $key = $this->key($sessionId);
            Redis::srem($key, (string) $productId);
        }

        return $this->items($sessionId, $authenticatedUserId);
    }

    /**
     * List wishlist items as lightweight product cards.
     */
    public function items(string $sessionId, ?int $userId = null): array
    {
        $authenticatedUserId = $userId ?? Auth::id();
        $ids = [];

        if ($authenticatedUserId) {
            // For authenticated users, prioritize DB data but also check Redis for current session state
            $dbIds = Wishlist::where('user_id', $authenticatedUserId)->pluck('product_id')->all();
            
            // Also get Redis stored IDs for consistency
            $redisKey = $this->key($sessionId, $authenticatedUserId);
            $redisIds = array_map('intval', Redis::smembers($redisKey) ?? []);
            if (!empty($redisIds)) {
                Redis::expire($redisKey, $this->ttlSeconds);
            }
            
            // Merge and de-duplicate IDs, prioritizing data from both sources
            $ids = array_values(array_unique(array_merge($dbIds, $redisIds)));
        } else {
            $redisKey = $this->key($sessionId);
            $ids = array_map('intval', Redis::smembers($redisKey) ?? []);
            if (!empty($ids)) {
                Redis::expire($redisKey, $this->ttlSeconds);
            }
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

        if (!$authenticatedUserId && isset($redisKey)) {
            $missingIds = array_diff($ids, array_keys($byId));
            if (!empty($missingIds)) {
                Redis::srem($redisKey, ...array_map('strval', $missingIds));
            }
        }

        return ['items' => $items, 'count' => count($items)];
    }

    /**
     * Clear guest session wishlist data (for logout).
     */
    public function clearGuestSession(string $sessionId): void
    {
        $key = $this->key($sessionId);
        Redis::del($key);
    }

    /**
     * Call later when auth exists: merge guest Redis set into DB wishlist and user Redis storage.
     */
    public function merge(string $sessionId, int $userId): void
    {
        $guestKey = $this->key($sessionId); // Old guest session key (wishlist:sessionid)
        $userKey = $this->key($sessionId, $userId); // New user key (wishlist:user:userid)
        
        // Get guest wishlist items
        $guestIds = array_map('intval', Redis::smembers($guestKey) ?? []);

        // Add items to user's DB wishlist
        foreach (array_unique($guestIds) as $pid) {
            Wishlist::firstOrCreate(['user_id' => $userId, 'product_id' => $pid]);
        }

        // Add items to user's Redis storage as well for consistency
        if (!empty($guestIds)) {
            Redis::sadd($userKey, ...array_map('strval', $guestIds));
            Redis::expire($userKey, $this->ttlSeconds);

            // Remove the old guest key
            Redis::del($guestKey);
        }
    }
}
