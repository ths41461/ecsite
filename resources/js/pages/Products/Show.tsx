import RatingStars from '@/components/RatingStars';
import ReviewForm from '@/components/ReviewForm';
import ReviewList from '@/components/ReviewList';
import { Head, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import CartDrawer, { type Cart as DrawerCart, type Line as DrawerLine } from '../../components/CartDrawer';
import { HomeNavigation } from '@/components/homeNavigation';

type Variant = {
    id?: number; // Prefer sending this from backend (needed for 4.3)
    sku: string;
    price_cents: number;
    compare_at_cents: number | null;
    stock?: number | null;
    safety_stock?: number | null;
    managed?: boolean;
    options?: { // Add options for gender and size
        gender?: string;
        size_ml?: number;
    };
};

type Props = {
    product: {
        id: number;
        name: string;
        slug: string;
        brand?: { name?: string | null; slug?: string | null } | null;
        image?: string | null;
        short_desc?: string | null;
        long_desc?: string | null;
        variants: Variant[];
        average_rating?: number;
        review_count?: number;
    };
    gallery: { url: string | null; alt?: string | null; is_hero: boolean }[];
    related: {
        id: number;
        name: string;
        slug: string;
        brand?: string | null;
        price_cents: number;
        compare_at_cents: number | null;
        image?: string | null;
    }[];
};

function yen(cents: number) {
    return (cents / 100).toLocaleString(undefined, { style: 'currency', currency: 'JPY' });
}

export default function Show({ product, gallery, related }: Props) {
    // --- NEW: Clickable gallery state (pick hero if present) ---
    const initialIndex = useMemo(() => {
        const i = gallery.findIndex((g) => g.is_hero);
        return i >= 0 ? i : 0;
    }, [gallery]);
    const [activeIndex, setActiveIndex] = useState<number>(initialIndex);

    // State for product ratings
    const [productRatings, setProductRatings] = useState({
        averageRating: product.average_rating || 0,
        reviewCount: product.review_count || 0,
    });

    // State for reviews
    const [reviews, setReviews] = useState<{
        id: number;
        rating: number;
        body: string | null;
        created_at: string;
        user: { name: string } | null;
    }[]>([]);
    const [loadingReviews, setLoadingReviews] = useState(true);

    const [selectedVariant, setSelectedVariant] = useState<Variant | null>(product.variants[0] || null);
    const [quantity, setQuantity] = useState(1);
    const [isAddingToCart, setIsAddingToCart] = useState(false);
    const [isWishlisting, setIsWishlisting] = useState(false);
    const [isWishlisted, setIsWishlisted] = useState(false);
    const [toast, setToast] = useState<null | { message: string; kind: 'success' | 'warning' | 'error' }>(null);
    // Drawer state
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [drawerCart, setDrawerCart] = useState<DrawerCart | null>(null);
    const [busyLineId, setBusyLineId] = useState<string | null>(null);
    const [couponBusy, setCouponBusy] = useState(false);

    function showToast(message: string, kind: 'success' | 'warning' | 'error' = 'success') {
        setToast({ message, kind });
        // Auto-hide after 3 seconds
        window.setTimeout(() => setToast(null), 3000);
    }

    useEffect(() => {
        // PDP view event (spec-defined)
        const getCookie = (name: string) => {
            const parts = document.cookie.split('; ').map((c) => c.split('='));
            const found = parts.find(([k]) => k === name);
            return found ? decodeURIComponent(found[1] ?? '') : null;
        };
        const xsrf = getCookie('XSRF-TOKEN');
        fetch('/e/pdp-view', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
            },
            body: JSON.stringify({ product_id: product.id }),
            credentials: 'same-origin',
        }).catch(() => {});
    }, [product.id]);

    // Fetch reviews when component mounts
    useEffect(() => {
        const fetchReviews = async () => {
            try {
                const response = await fetch(`/products/${product.slug}/reviews`);
                if (response.ok) {
                    const data = await response.json();
                    setReviews(data.data || []);
                }
            } catch (error) {
                console.error('Error fetching reviews:', error);
            } finally {
                setLoadingReviews(false);
            }
        };

        if (productRatings.reviewCount > 0) {
            fetchReviews();
        } else {
            setLoadingReviews(false);
        }
    }, [product.slug, productRatings.reviewCount]);

    const postJson = (url: string, payload: Record<string, unknown>) => {
        const getCookie = (name: string) => {
            const parts = document.cookie.split('; ').map((c) => c.split('='));
            const found = parts.find(([k]) => k === name);
            return found ? decodeURIComponent(found[1] ?? '') : null;
        };
        const xsrf = getCookie('XSRF-TOKEN');
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
            },
            body: JSON.stringify(payload),
            credentials: 'same-origin',
        });
    };

    // Same headers used by Cart page for PATCH/DELETE
    function xsrfHeaders(): HeadersInit {
        const parts = document.cookie.split('; ').map((c) => c.split('='));
        const found = parts.find(([k]) => k === 'XSRF-TOKEN');
        const xsrf = found ? decodeURIComponent(found[1] ?? '') : null;
        return {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
        };
    }

    const handleAddToCartEvent = async () => {
        if (!selectedVariant) return;
        setIsAddingToCart(true);
        try {
            // 1) Analytics/event (non-blocking if it fails)
            postJson('/e/add-to-cart', {
                product_id: product.id,
                variant_id: selectedVariant.id ?? null,
                sku: selectedVariant.sku,
                qty: quantity,
            }).catch(() => {});

            // 2) Actual cart mutation
            if (selectedVariant.id == null) {
                throw new Error('商品バリエーションIDが見つかりません');
            }
            const res = await postJson('/cart', {
                variant_id: selectedVariant.id,
                qty: quantity,
            });
            if (!res.ok) {
                const text = await res.text();
                throw new Error(text || 'カートへの追加に失敗しました');
            }
            // Inspect response to detect clamp notice for a better message and open drawer with server cart
            let cart: DrawerCart | null = null;
            try {
                cart = (await res.json()) as DrawerCart;
            } catch {}

            const line = cart?.lines?.find((l) => l.variant_id === selectedVariant.id);
            if (line && line.notice && line.notice.code === 'qty_clamped_to_available') {
                showToast(`在庫は${line.notice.available}個のみです。数量を調整しました。`, 'warning');
            } else {
                showToast('カートに追加しました。', 'success');
            }

            // Prefer using the POST response; fallback to GET /cart if parsing fails
            if (cart) {
                setDrawerCart(cart);
                setDrawerOpen(true);
                // Update localStorage to notify other tabs/components
                try {
                    if (typeof window !== 'undefined' && window.localStorage) {
                        localStorage.setItem('cart-state', JSON.stringify(cart));
                    }
                    
                    // Dispatch custom event to notify same-tab components
                    window.dispatchEvent(new CustomEvent('cart-updated', { detail: { cart } }));
                } catch (error) {
                    console.error('Failed to update cart in localStorage:', error);
                }
            } else {
                try {
                    const fres = await fetch('/cart', { headers: { Accept: 'application/json' } });
                    const fdata = (await fres.json()) as DrawerCart;
                    setDrawerCart(fdata);
                    setDrawerOpen(true);
                    // Update localStorage to notify other tabs/components
                    try {
                        if (typeof window !== 'undefined' && window.localStorage) {
                            localStorage.setItem('cart-state', JSON.stringify(fdata));
                        }
                        
                        // Dispatch custom event to notify same-tab components
                        window.dispatchEvent(new CustomEvent('cart-updated', { detail: { cart: fdata } }));
                    } catch (error) {
                        console.error('Failed to update cart in localStorage:', error);
                    }
                } catch {
                    // swallow; drawer just won't open if something went wrong
                }
            }
        } catch (error) {
            showToast('カートへの追加に失敗しました。もう一度お試しください。', 'error');
        } finally {
            setIsAddingToCart(false);
        }
    };

    // (removed unused drawerRefresh/drawer* functions)

    const handleWishlistAdd = async () => {
        setIsWishlisting(true);
        try {
            const res = await postJson('/wishlist', { product_id: product.id });
            if (!res.ok) {
                const text = await res.text();
                throw new Error(text || `お気に入り追加に失敗しました (${res.status})`);
            }
            showToast('お気に入りに追加しました。', 'success');
            // Toggle UI to indicate wishlisted (filled heart)
            setIsWishlisted(true);
        } catch (err) {
            showToast('お気に入りへの追加に失敗しました。もう一度お試しください。', 'error');
        } finally {
            setIsWishlisting(false);
        }
    };

    const stockBadgeFor = (v: Variant) => {
        const stock = v.stock ?? null;
        const managed = v.managed ?? false;
        if (!managed) return '在庫あり';
        if ((stock ?? 0) <= 0) return '在庫切れ';
        if (stock! <= (v.safety_stock ?? 1)) return '在庫僅少';
        return '在庫あり';
    };

    // Drawer actions reuse same endpoints as Cart page
    async function updateDrawerQty(line: DrawerLine, qty: number) {
        setBusyLineId(line.line_id);
        try {
            const res = await fetch(`/cart/${encodeURIComponent(line.line_id)}`, {
                method: 'PATCH',
                headers: xsrfHeaders(),
                body: JSON.stringify({ qty }),
                credentials: 'same-origin',
            });
            const data = (await res.json()) as DrawerCart;
            setDrawerCart(data);
            // Update localStorage to notify other tabs/components
            try {
                if (typeof window !== 'undefined' && window.localStorage) {
                    localStorage.setItem('cart-state', JSON.stringify(data));
                }
                
                // Dispatch custom event to notify same-tab components
                window.dispatchEvent(new CustomEvent('cart-updated', { detail: { cart: data } }));
            } catch (error) {
                console.error('Failed to update cart in localStorage:', error);
            }
        } finally {
            setBusyLineId(null);
        }
    }

    async function removeDrawerLine(line: DrawerLine) {
        setBusyLineId(line.line_id);
        try {
            const res = await fetch(`/cart/${encodeURIComponent(line.line_id)}`, {
                method: 'DELETE',
                headers: xsrfHeaders(),
                credentials: 'same-origin',
            });
            const data = (await res.json()) as DrawerCart;
            setDrawerCart(data);
            // Update localStorage to notify other tabs/components
            try {
                if (typeof window !== 'undefined' && window.localStorage) {
                    localStorage.setItem('cart-state', JSON.stringify(data));
                }
                
                // Dispatch custom event to notify same-tab components
                window.dispatchEvent(new CustomEvent('cart-updated', { detail: { cart: data } }));
            } catch (error) {
                console.error('Failed to update cart in localStorage:', error);
            }
        } finally {
            setBusyLineId(null);
        }
    }

    async function removeDrawerCoupon() {
        setCouponBusy(true);
        try {
            const res = await fetch('/cart/coupon', {
                method: 'DELETE',
                headers: xsrfHeaders(),
                credentials: 'same-origin',
            });
            if (!res.ok) {
                const data = await res.json().catch(() => null);
                const message = data?.errors?.code?.[0] || data?.message || 'クーポンの削除に失敗しました';
                throw new Error(message);
            }
            const data = (await res.json()) as DrawerCart;
            setDrawerCart(data);
            // Update localStorage to notify other tabs/components
            try {
                if (typeof window !== 'undefined' && window.localStorage) {
                    localStorage.setItem('cart-state', JSON.stringify(data));
                }
                
                // Dispatch custom event to notify same-tab components
                window.dispatchEvent(new CustomEvent('cart-updated', { detail: { cart: data } }));
            } catch (error) {
                console.error('Failed to update cart in localStorage:', error);
            }
            showToast('クーポンを削除しました。', 'success');
        } catch (error: any) {
            showToast(error?.message || 'クーポンの削除に失敗しました。もう一度お試しください。', 'error');
        } finally {
            setCouponBusy(false);
        }
    }

    const MainImage = () => {
        const hero = gallery[activeIndex];
        const src = hero?.url ?? product.image ?? null;
        if (!src) {
            return <div className="grid h-full w-full place-items-center text-sm text-neutral-500">画像なし</div>;
        }
        return <img src={src} alt={hero?.alt ?? product.name} className="h-full w-full object-cover" />;
    };

    return (
        <div className="min-h-screen bg-white">
            <HomeNavigation />
            <div className="mx-auto max-w-5xl px-4 py-6">
                <Head title={product.name} />
                <div className="grid gap-6 md:grid-cols-2">
                {/* GALLERY */}
                <div>
                    <div className="aspect-square overflow-hidden rounded-xl bg-neutral-100">
                        <MainImage />
                    </div>

                    {gallery.length > 1 && (
                        <div className="mt-3 grid grid-cols-4 gap-2">
                            {gallery.map((g, i) => {
                                const selected = i === activeIndex;
                                return (
                                    <button
                                        key={i}
                                        type="button"
                                        onClick={() => setActiveIndex(i)}
                                        className={`aspect-square overflow-hidden rounded border transition ${
                                            selected ? 'ring-2 ring-black' : 'hover:border-neutral-300'
                                        }`}
                                        aria-label={`サムネイル ${i + 1}`}
                                    >
                                        {g.url ? (
                                            <img src={g.url} alt={g.alt ?? product.name} className="h-full w-full object-cover" />
                                        ) : (
                                            <div className="h-full w-full bg-neutral-100" />
                                        )}
                                    </button>
                                );
                            })}
                        </div>
                    )}
                </div>

                {/* DETAILS */}
                <div>
                    {product.brand?.name && <div className="text-sm text-neutral-500">{product.brand.name}</div>}
                    <h1 className="mb-2 text-2xl font-semibold">{product.name}</h1>
                    {product.short_desc && <p className="mb-4 text-neutral-700">{product.short_desc}</p>}

                    <div className="space-y-4">
                        {/* VARIANT PICKER (inline for now; will extract later) */}
                                                    {product.variants.length > 1 && (
                            <div>
                                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">バリエーションを選択</label>
                                <div className="space-y-2">
                                    {product.variants.map((v) => {
                                        const badge = stockBadgeFor(v);
                                        const isSelected = selectedVariant?.sku === v.sku;
                                        const isOut = badge === '在庫切れ';
                                        return (
                                            <button
                                                key={v.sku}
                                                onClick={() => setSelectedVariant(v)}
                                                disabled={isOut}
                                                className={`w-full rounded-lg border p-3 text-left transition-colors ${
                                                    isSelected
                                                        ? 'border-rose-500 bg-rose-50 dark:bg-rose-900/20'
                                                        : isOut
                                                          ? 'border-gray-200 bg-gray-50 text-gray-400 dark:border-gray-700 dark:bg-gray-800'
                                                          : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600'
                                                }`}
                                            >
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-baseline gap-2">
                                                        <span className="font-semibold text-rose-700 dark:text-rose-400">{yen(v.price_cents)}</span>
                                                        {v.compare_at_cents != null && (
                                                            <span className="text-sm text-neutral-500 line-through">{yen(v.compare_at_cents)}</span>
                                                        )}
                                                        <span className="text-xs text-neutral-500">SKU: {v.sku}</span>
                                                        {/* Gender and Size Icons for Variants */}
                                                        {v.options && (
                                                            <div className="flex gap-1">
                                                                {v.options.gender && (
                                                                    <span 
                                                                        className="inline-flex items-center justify-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                                                                        title={v.options.gender === 'men' ? 'メンズ' : v.options.gender === 'women' ? 'レディース' : 'ユニセックス'}
                                                                    >
                                                                        {v.options.gender === 'men' ? '♂' : v.options.gender === 'women' ? '♀' : '⚥'}
                                                                    </span>
                                                                )}
                                                                {v.options.size_ml && (
                                                                    <span 
                                                                        className="inline-flex items-center justify-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200"
                                                                        title={`${v.options.size_ml}ml`}
                                                                    >
                                                                        {v.options.size_ml}ml
                                                                    </span>
                                                                )}
                                                            </div>
                                                        )}
                                                    </div>
                                                    <span
                                                        className={`rounded px-2 py-0.5 text-xs ${
                                                            badge === '在庫切れ'
                                                                ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'
                                                                : badge === '在庫僅少'
                                                                  ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                                                                  : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                                        }`}
                                                    >
                                                        {badge}
                                                    </span>
                                                </div>
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        )}

                        {/* Single variant display */}
                        {product.variants.length === 1 &&
                            (() => {
                                const v = product.variants[0];
                                const badge = stockBadgeFor(v);
                                return (
                                    <div className="flex items-baseline gap-2 text-lg">
                                        <span className="font-semibold text-rose-700 dark:text-rose-400">{yen(v.price_cents)}</span>
                                        {v.compare_at_cents != null && (
                                            <span className="text-sm text-neutral-500 line-through">{yen(v.compare_at_cents)}</span>
                                        )}
                                        <span className="ml-2 text-xs text-neutral-500">SKU: {v.sku}</span>
                                        {/* Gender and Size Icons for Single Variant */}
                                        {v.options && (
                                            <div className="flex gap-1">
                                                {v.options.gender && (
                                                    <span 
                                                        className="inline-flex items-center justify-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                                                        title={v.options.gender === 'men' ? 'メンズ' : v.options.gender === 'women' ? 'レディース' : 'ユニセックス'}
                                                    >
                                                        {v.options.gender === 'men' ? '♂' : v.options.gender === 'women' ? '♀' : '⚥'}
                                                    </span>
                                                )}
                                                {v.options.size_ml && (
                                                    <span 
                                                        className="inline-flex items-center justify-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200"
                                                        title={`${v.options.size_ml}ml`}
                                                    >
                                                        {v.options.size_ml}ml
                                                    </span>
                                                )}
                                            </div>
                                        )}
                                        <span
                                            className={`ml-2 rounded px-2 py-0.5 text-xs ${
                                                badge === '在庫切れ'
                                                    ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'
                                                    : badge === '在庫僅少'
                                                      ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                                                      : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                            }`}
                                        >
                                            {badge}
                                        </span>
                                    </div>
                                );
                            })()}

                        {/* Quantity + Actions */}
                        {selectedVariant && (
                            <div className="space-y-4">
                                <div>
                                    <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">数量</label>
                                    <div className="flex items-center gap-2">
                                        <button
                                            onClick={() => setQuantity(Math.max(1, quantity - 1))}
                                            className="rounded-lg border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-800"
                                        >
                                            -
                                        </button>
                                        <span className="w-12 text-center text-sm font-medium">{quantity}</span>
                                        <button
                                            onClick={() => setQuantity(Math.min(20, quantity + 1))}
                                            className="rounded-lg border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-800"
                                        >
                                            +
                                        </button>
                                    </div>
                                </div>

                                <div className="flex gap-3">
                                    <button
                                        onClick={handleAddToCartEvent}
                                        disabled={isAddingToCart || !selectedVariant}
                                        className="flex-1 rounded-lg bg-rose-600 px-6 py-3 font-medium text-white hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-gray-400 dark:bg-rose-500 dark:hover:bg-rose-600"
                                    >
                                        {isAddingToCart ? '追加中...' : 'カートに追加'}
                                    </button>
                                    <button
                                        onClick={handleWishlistAdd}
                                        disabled={isWishlisting || isWishlisted}
                                        className="rounded-lg border border-gray-300 px-4 py-3 text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                                        aria-label={isWishlisted ? 'お気に入り登録済み' : 'お気に入りに追加'}
                                    >
                                        {isWishlisting ? '...' : isWishlisted ? '♥' : '♡'}
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>

                    {product.long_desc && <div className="prose mt-6 max-w-none whitespace-pre-wrap">{product.long_desc}</div>}
                </div>
            </div>

            {/* Reviews Section */}
            <div className="mt-10 border-t border-gray-200 pt-10">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-bold">レビュー</h2>
                    {productRatings.averageRating > 0 && productRatings.reviewCount > 0 ? (
                        <div className="flex items-center">
                            <RatingStars rating={productRatings.averageRating} size="md" showLabel />
                            <span className="ml-2 text-sm text-gray-600">({productRatings.reviewCount} 件のレビュー)</span>
                        </div>
                    ) : (
                        <span className="text-sm text-gray-500">レビューがまだありません</span>
                    )}
                </div>

                {productRatings.averageRating > 0 && productRatings.reviewCount > 0 && (
                    <div className="mt-6">
                        {loadingReviews ? <div>Loading reviews...</div> : <ReviewList reviews={reviews} productId={product.id} />}
                    </div>
                )}

                <div className="mt-8">
                    <ReviewForm
                        productId={product.id}
                        onSubmit={async (rating, comment) => {
                            try {
                                // Get CSRF token from cookie using the same approach as other pages
                                function getCookie(name: string) {
                                    const parts = document.cookie.split('; ').map((c) => c.split('='));
                                    const found = parts.find(([k]) => k === name);
                                    return found ? decodeURIComponent(found[1] ?? '') : null;
                                }

                                function xsrfHeaders(): HeadersInit {
                                    const xsrf = getCookie('XSRF-TOKEN');
                                    return {
                                        'Content-Type': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                        ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
                                    };
                                }

                                const response = await fetch(`/products/${product.slug}/reviews`, {
                                    method: 'POST',
                                    headers: xsrfHeaders(),
                                    credentials: 'same-origin',
                                    body: JSON.stringify({
                                        rating: rating,
                                        body: comment,
                                    }),
                                });

                                if (!response.ok) {
                                    const errorData = await response.json().catch(() => ({}));
                                    throw new Error(errorData.message || 'レビューの送信に失敗しました。');
                                }

                                const reviewData = await response.json();
                                console.log('Review submitted successfully:', reviewData);

                                // Update the reviews state to include the new review
                                setReviews((prevReviews) => [
                                    {
                                        id: reviewData.id,
                                        rating: reviewData.rating,
                                        body: reviewData.body,
                                        created_at: reviewData.created_at,
                                        user: reviewData.user || { name: '匿名ユーザー' },
                                    },
                                    ...prevReviews,
                                ]);

                                // Update the product ratings state
                                const newRatingData = {
                                    averageRating: (productRatings.averageRating * productRatings.reviewCount + rating) / (productRatings.reviewCount + 1),
                                    reviewCount: productRatings.reviewCount + 1,
                                };
                                
                                setProductRatings(newRatingData);

                                // Store the updated ratings in the fresh review cache
                                import('@/lib/review-cache').then(({ updateProductReviewData }) => {
                                    updateProductReviewData(product.id, productRatings.averageRating, productRatings.reviewCount, rating);
                                });

                                // Show success toast notification
                                showToast('レビューを送信しました！', 'success');

                                return reviewData;
                            } catch (error: any) {
                                console.error('Error submitting review:', error);
                                showToast(error.message || 'レビューの送信に失敗しました。', 'error');
                                throw error;
                            }
                        }}
                    />
                </div>
            </div>

            {related.length > 0 && (
                <div className="mt-10">
                    <h2 className="mb-3 text-lg font-semibold">関連商品</h2>
                    <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                        {related.map((p) => (
                            <div key={p.id} className="rounded-xl border p-2">
                                {p.image ? (
                                    <img src={p.image} alt={p.name} className="aspect-square w-full rounded-lg object-cover" />
                                ) : (
                                    <div className="aspect-square w-full rounded-lg bg-neutral-100" />
                                )}
                                <div className="mt-2 text-sm font-medium">{p.name}</div>
                            </div>
                        ))}
                    </div>
                </div>
            )}
            {/* Lightweight toast */}
            {toast && (
                <div
                    role="status"
                    aria-live="polite"
                    className={`fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-lg px-4 py-2 text-sm shadow-lg transition ${
                        {
                            success: 'bg-emerald-600 text-white',
                            warning: 'bg-amber-600 text-white',
                            error: 'bg-rose-600 text-white',
                        }[toast.kind]
                    }`}
                >
                    {toast.message}
                </div>
            )}

            <CartDrawer
                open={drawerOpen}
                cart={drawerCart}
                onClose={() => setDrawerOpen(false)}
                onUpdateQty={updateDrawerQty}
                onRemoveLine={removeDrawerLine}
                onRemoveCoupon={removeDrawerCoupon}
                couponBusy={couponBusy}
                busyLineId={busyLineId}
            />
        </div>
        </div>
    );
}

// Render CartDrawer at the end of the component output
