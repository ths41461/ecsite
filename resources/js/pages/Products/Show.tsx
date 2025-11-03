import RatingStars from '@/components/RatingStars';
import ReviewForm from '@/components/ReviewForm';
import ReviewList from '@/components/ReviewList';
import { Head, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import CartDrawer, { type Cart as DrawerCart, type Line as DrawerLine } from '../../components/CartDrawer';
import { HomeNavigation } from '@/components/homeNavigation';
import ProductCard from '@/Components/ProductCard';
import ImprovedCarousel from '@/Components/ImprovedCarousel';

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
    // Tab state
    const [activeTab, setActiveTab] = useState<'details' | 'notes' | 'chart' | 'reviews'>('details');
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
            return <div className="grid h-full w-full place-items-center text-sm text-gray-500">画像なし</div>;
        }
        return <img src={src} alt={hero?.alt ?? product.name} className="h-full w-full object-cover" />;
    };

    return (
        <div className="min-h-screen bg-gray-50">
            <HomeNavigation />
            <div className="mx-auto max-w-[1440px] px-4 py-6 w-full">
                <Head title={product.name} />
                <div className="grid gap-6 md:grid-cols-2">
                {/* GALLERY */}
                <div className="flex space-x-3">
                    {gallery.length > 1 && (
                        <div className="flex flex-col gap-2">
                            {gallery.map((g, i) => {
                                const selected = i === activeIndex;
                                return (
                                    <button
                                        key={i}
                                        type="button"
                                        onClick={() => setActiveIndex(i)}
                                        className={`w-16 aspect-square overflow-hidden border ${
                                            selected ? 'border-2 border-gray-800' : 'border border-gray-300 hover:border-gray-400'
                                        }`}
                                        aria-label={`サムネイル ${i + 1}`}
                                    >
                                        {g.url ? (
                                            <img src={g.url} alt={g.alt ?? product.name} className="h-full w-full object-cover" />
                                        ) : (
                                            <div className="h-full w-full bg-gray-100" />
                                        )}
                                    </button>
                                );
                            })}
                        </div>
                    )}
                    <div className="aspect-square overflow-hidden bg-[#FCFCF7] border border-gray-200" style={{minWidth: 0, flex: 1}}>
                        <MainImage />
                    </div>
                </div>

                {/* DETAILS */}
                <div className="space-y-4">
                    {/* Product Name and Brand */}
                    <div className="space-y-1">
                        {product.brand?.name && (
                            <div className="text-sm" style={{ color: '#888888' }}>{product.brand.name}</div>
                        )}
                        <h1 className="text-xl font-semibold" style={{ color: '#363842', fontFamily: 'Hiragino Mincho ProN' }}>{product.name}</h1>
                    </div>
                    
                    {/* Price and Stock */}
                    {selectedVariant && (
                        <div className="flex items-center justify-between">
                            <div className="text-xl font-bold" style={{ color: '#363842' }}>{yen(selectedVariant.price_cents)}</div>
                            <div className={`px-2 py-1 text-xs ${ 
                                stockBadgeFor(selectedVariant) === '在庫切れ' 
                                    ? 'bg-rose-100 text-rose-700' 
                                    : stockBadgeFor(selectedVariant) === '在庫僅少' 
                                        ? 'bg-amber-100 text-amber-700' 
                                        : 'bg-emerald-100 text-emerald-700'
                            }`}>
                                {stockBadgeFor(selectedVariant)}
                            </div>
                        </div>
                    )}

                    {/* Content Volume Heading */}
                    <div className="text-sm font-medium" style={{ color: '#888888' }}>内容量</div>

                    {/* Size Options */}
                    {product.variants.length > 1 && (
                        <div className="flex gap-2">
                            {product.variants.map((v) => {
                                const isSelected = selectedVariant?.sku === v.sku;
                                return (
                                    <button
                                        key={v.sku}
                                        onClick={() => setSelectedVariant(v)}
                                        className={`flex-1 border p-2 text-center text-sm ${
                                            isSelected 
                                                ? 'border-gray-800' 
                                                : 'border border-gray-300 hover:border-gray-400'
                                        }`}
                                        style={{ color: isSelected ? '#363842' : '#000000' }}
                                    >
                                        <span>{v.options?.size_ml}ml</span>
                                    </button>
                                );
                            })}
                        </div>
                    )}

                    {/* Single variant display */}
                    {product.variants.length === 1 && selectedVariant && (
                        <div className="border border-gray-300 p-2 text-center text-sm" style={{ color: '#363842' }}>
                            <span>{selectedVariant.options?.size_ml}ml</span>
                        </div>
                    )}

                    {/* Quantity Selector */}
                    {selectedVariant && (
                        <div>
                            <div className="text-xs font-medium" style={{ color: '#888888' }}>数量</div>
                            <div className="flex items-center gap-2 mt-1">
                                <button
                                    onClick={() => setQuantity(Math.max(1, quantity - 1))}
                                    className="border border-gray-300 w-8 h-8 flex items-center justify-center text-sm hover:bg-gray-50"
                                >
                                    -
                                </button>
                                <span className="w-8 text-center text-sm font-medium">{quantity}</span>
                                <button
                                    onClick={() => setQuantity(Math.min(20, quantity + 1))}
                                    className="border border-gray-300 w-8 h-8 flex items-center justify-center text-sm hover:bg-gray-50"
                                >
                                    +
                                </button>
                            </div>
                        </div>
                    )}

                    {/* Add to Cart Button */}
                    {selectedVariant && (
                        <div>
                            <button
                                onClick={handleAddToCartEvent}
                                disabled={isAddingToCart || !selectedVariant}
                                className="w-full bg-[#EAB308] py-3 font-medium hover:bg-amber-500 disabled:cursor-not-allowed disabled:opacity-50"
                                style={{ color: '#FFFFFF' }}
                            >
                                {isAddingToCart ? '追加中...' : 'カートに追加'}
                            </button>
                        </div>
                    )}
                    
                    {/* Wishlist Button */}
                    <div className="flex justify-center">
                        <button
                            onClick={handleWishlistAdd}
                            disabled={isWishlisting || isWishlisted}
                            className="border border-gray-300 px-3 py-2 hover:bg-gray-100 disabled:cursor-not-allowed disabled:bg-gray-100"
                            style={{ color: '#363842' }}
                            aria-label={isWishlisted ? 'お気に入り登録済み' : 'お気に入りに追加'}
                        >
                            {isWishlisting ? '...' : isWishlisted ? '♥' : '♡'}
                        </button>
                    </div>

                    {/* Fragrance Type - From Figma design */}
                    <div className="flex justify-between items-center border-b border-gray-200 pb-1">
                        <div className="text-sm font-medium" style={{ color: '#888888' }}>香りのタイプ</div>
                        <div className="text-sm font-medium" style={{ color: '#444444' }}>フローラル</div>
                    </div>

                    {/* Delivery Information */}
                    <div className="border border-gray-200 p-3">
                        <div className="text-sm font-medium mb-1" style={{ color: '#000000' }}>配送について</div>
                        <div className="border border-gray-300 p-3">
                            <div className="flex justify-between text-xs">
                                <div style={{ color: '#888888' }}>配送料：</div>
                                <div style={{ color: '#444444' }}>無料配送</div>
                            </div>
                            <div className="mt-1 text-xs" style={{ color: '#888888' }}>9,350円(税込)～購入で無料配送</div>
                            <div className="flex justify-between mt-2 text-xs">
                                <div style={{ color: '#888888' }}>配送料：</div>
                                <div style={{ color: '#444444' }}>ご注文完了から2日～7日前後のお届け</div>
                            </div>
                        </div>
                    </div>

                    {/* Product Description */}
                    {product.long_desc && (
                        <div>
                            <div className="text-lg font-medium mb-2" style={{ color: '#888888' }}>製品詳細</div>
                            <div className="border border-gray-300 p-4" style={{ color: '#444444' }}>
                                <div className="prose max-w-none whitespace-pre-wrap">{product.long_desc}</div>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Product Detail Sections from Figma */}
            <div className="border-t border-gray-200 pt-4 mt-4">
                {/* Tab Navigation */}
                <div className="flex space-x-6 mb-4">
                    <button 
                        className={`text-sm ${activeTab === 'details' ? 'text-gray-800 pb-1 border-b-2 border-gray-800' : 'text-gray-500 pb-1 border-b border-transparent'}`}
                        onClick={() => setActiveTab('details')}
                    >
                        製品詳細
                    </button>
                    <button 
                        className={`text-sm ${activeTab === 'notes' ? 'text-gray-800 pb-1 border-b-2 border-gray-800' : 'text-gray-500 pb-1 border-b border-transparent'}`}
                        onClick={() => setActiveTab('notes')}
                    >
                        香りノート
                    </button>
                    <button 
                        className={`text-sm ${activeTab === 'chart' ? 'text-gray-800 pb-1 border-b-2 border-gray-800' : 'text-gray-500 pb-1 border-b border-transparent'}`}
                        onClick={() => setActiveTab('chart')}
                    >
                        レーダーチャート
                    </button>
                    <button 
                        className={`text-sm ${activeTab === 'reviews' ? 'text-gray-800 pb-1 border-b-2 border-gray-800' : 'text-gray-500 pb-1 border-b border-transparent'}`}
                        onClick={() => setActiveTab('reviews')}
                    >
                        レビュー
                    </button>
                </div>

                {/* Tab Content */}
                <div>
                    {/* Product Details Tab */}
                    {activeTab === 'details' && (
                        <div className="border border-gray-300 p-3 mb-4 text-sm">
                            <div className="whitespace-pre-wrap text-gray-700">{product.long_desc}</div>
                        </div>
                    )}

                    {/* Fragrance Notes Tab */}
                    {activeTab === 'notes' && (
                        <div className="border border-gray-300 p-3 mb-4 text-sm">
                            <div className="text-gray-700">香りノートのコンテンツがここに表示されます。</div>
                        </div>
                    )}

                    {/* Radar Chart Tab */}
                    {activeTab === 'chart' && (
                        <div className="border border-gray-300 p-3 mb-4 text-sm">
                            <div className="text-gray-700">レーダーチャートのコンテンツがここに表示されます。</div>
                        </div>
                    )}

                    {/* Reviews Tab */}
                    {activeTab === 'reviews' && (
                        <div id="reviews">
                            <div className="flex items-center justify-between mb-3">
                                <h2 className="text-lg font-semibold text-gray-800">レビュー</h2>
                                {productRatings.averageRating > 0 && productRatings.reviewCount > 0 ? (
                                    <div className="flex items-center">
                                        <RatingStars rating={productRatings.averageRating} size="sm" showLabel />
                                        <span className="ml-2 text-sm text-gray-600">({productRatings.reviewCount} 件のレビュー)</span>
                                    </div>
                                ) : (
                                    <span className="text-sm text-gray-500">レビューがまだありません</span>
                                )}
                            </div>

                            {productRatings.averageRating > 0 && productRatings.reviewCount > 0 && (
                                <div className="mb-4">
                                    {loadingReviews ? <div className="text-center py-3 text-gray-500">レビューを読み込み中...</div> : <ReviewList reviews={reviews} productId={product.id} />}
                                </div>
                            )}

                            <div>
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
                    )}
                </div>
            </div>

            {related.length > 0 && (
                <section className="w-full border-t border-b border-[#888888] bg-[#FCFCF7] py-4">
                    <div className="container mx-auto px-4">
                        {/* Section Title */}
                        <div className="mb-6 text-center">
                            <h2 className="font-['Hiragino_Mincho_ProN'] mb-6 text-2xl leading-tight font-semibold text-gray-800">関連商品</h2>
                        </div>

                        {/* Product Cards - Carousel for Mobile, Grid for Desktop */}
                        <div className="w-full">
                            <div className="block sm:hidden">
                                <ImprovedCarousel itemsToShow={1} slideOffset={1}>
                                    {related.map((p, index) => (
                                        <ProductCard
                                            key={p.id}
                                            productImageSrc={p.image || '/perfume-images/perfume-1.png'}
                                            category={product.brand?.name || 'ブランド名'}
                                            productName={p.name}
                                            price={yen(p.price_cents)}
                                            showRatingIcon={true}
                                            showGenderIcon={false}
                                            showWishlistIcon={true}
                                        />
                                    ))}
                                </ImprovedCarousel>
                            </div>
                            <div className="hidden sm:grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6 justify-items-center">
                                {related.map((p, index) => (
                                    <ProductCard
                                        key={p.id}
                                        productImageSrc={p.image || '/perfume-images/perfume-1.png'}
                                        category={product.brand?.name || 'ブランド名'}
                                        productName={p.name}
                                        price={yen(p.price_cents)}
                                        showRatingIcon={true}
                                        showGenderIcon={false}
                                        showWishlistIcon={true}
                                    />
                                ))}
                            </div>
                        </div>
                    </div>
                </section>
            )}
            {/* Lightweight toast */}
            {toast && (
                <div
                    role="status"
                    aria-live="polite"
                    className={`fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-lg px-4 py-2 text-sm transition ${
                        {
                            success: 'bg-[#FCFCF7] border border-gray-200 text-gray-800',
                            warning: 'bg-[#FCFCF7] border border-gray-200 text-gray-800',
                            error: 'bg-[#FCFCF7] border border-gray-200 text-gray-800',
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
