import { HomeNavigation } from '@/components/homeNavigation';
import ImprovedCarousel from '@/Components/ImprovedCarousel';
import ProductCard from '@/Components/ProductCard';
import RatingStars from '@/components/RatingStars';
import ReviewForm from '@/components/ReviewForm';
import ReviewList from '@/components/ReviewList';
import { Head } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import CartDrawer, { type Cart as DrawerCart, type Line as DrawerLine } from '../../components/CartDrawer';

type Variant = {
    id?: number; // Prefer sending this from backend (needed for 4.3)
    sku: string;
    price_cents: number;
    compare_at_cents: number | null;
    stock?: number | null;
    safety_stock?: number | null;
    managed?: boolean;
    options?: {
        // Add options for gender and size
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
        fragrance_type?: string;
        attributes_json?: {
            notes?: {
                top?: string;
                middle?: string;
                base?: string;
            };
        };
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
    const [reviews, setReviews] = useState<
        {
            id: number;
            rating: number;
            body: string | null;
            created_at: string;
            user: { name: string } | null;
        }[]
    >([]);
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
            <div className="mx-auto w-full max-w-[1440px] px-4 py-6">
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
                                            className={`aspect-square w-16 overflow-hidden border ${
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
                        <div className="aspect-square overflow-hidden border border-gray-200 bg-[#FCFCF7]" style={{ minWidth: 0, flex: 1 }}>
                            <MainImage />
                        </div>
                    </div>

                    {/* DETAILS */}
                    <div className="flex flex-col gap-3.5" style={{ width: '298px' }}>
                        {/* Product Name and Brand */}
                        <div className="flex w-full items-center justify-center gap-2.5">
                            <h1 className="text-lg font-semibold" style={{ color: '#363842', fontFamily: 'Hiragino Mincho ProN', fontSize: '20px' }}>
                                {product.name}
                            </h1>
                            <div className="text-xs" style={{ color: '#888888', fontFamily: 'Noto Sans JP' }}>
                                {product.brand?.name || ''}
                            </div>
                        </div>

                        {/* Price and Stock */}
                        {selectedVariant && (
                            <div className="flex items-center justify-between">
                                <div className="text-xl font-bold" style={{ color: '#363842' }}>
                                    {yen(selectedVariant.price_cents)}
                                </div>
                                <div
                                    className={`px-2 py-1 text-xs ${
                                        stockBadgeFor(selectedVariant) === '在庫切れ'
                                            ? 'bg-rose-100 text-rose-700'
                                            : stockBadgeFor(selectedVariant) === '在庫僅少'
                                              ? 'bg-amber-100 text-amber-700'
                                              : 'bg-emerald-100 text-emerald-700'
                                    }`}
                                >
                                    {stockBadgeFor(selectedVariant)}
                                </div>
                            </div>
                        )}

                        {/* Content Volume Heading */}
                        <div className="flex w-full items-stretch justify-stretch gap-2.5">
                            <div className="text-sm font-medium" style={{ color: '#888888', fontFamily: 'Noto Sans JP' }}>
                                内容量
                            </div>
                        </div>

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
                                                isSelected ? 'border-gray-800' : 'border border-gray-300 hover:border-gray-400'
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

                        {/* Price Label Section */}
                        <div className="flex w-full items-center justify-center gap-1.5">
                            <div className="text-lg font-medium" style={{ color: '#888888', fontFamily: 'Noto Sans JP' }}>
                                価格
                            </div>
                            <div className="text-base font-medium" style={{ color: '#888888', fontFamily: 'Noto Sans JP' }}>
                                (税込)
                            </div>
                            <div className="text-lg font-medium" style={{ color: '#363842', fontFamily: 'Noto Sans JP' }}>
                                {yen(selectedVariant?.price_cents)}
                            </div>
                        </div>

                        {/* Fragrance Type */}
                        <div className="flex w-full items-center justify-between gap-8.5">
                            <div className="text-base font-medium" style={{ color: '#888888', fontFamily: 'Noto Sans JP' }}>
                                香りのタイプ
                            </div>
                            <div className="text-base font-medium" style={{ color: '#444444', fontFamily: 'Noto Sans JP' }}>
                                {product.fragrance_type || product.attributes_json?.notes?.middle}
                            </div>
                        </div>

                        {/* Wishlist Button */}
                        <div className="flex w-full justify-center">
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

                        {/* Add to Cart Button */}
                        {selectedVariant && (
                            <div className="flex w-full items-center justify-center gap-2.5 p-8" style={{ backgroundColor: '#EAB308' }}>
                                <button
                                    onClick={handleAddToCartEvent}
                                    disabled={isAddingToCart || !selectedVariant}
                                    className="font-medium disabled:cursor-not-allowed disabled:opacity-50"
                                    style={{
                                        color: '#FFFFFF',
                                        fontFamily: 'Sora',
                                        fontSize: '14px',
                                    }}
                                >
                                    {isAddingToCart ? '追加中...' : 'カートに追加'}
                                </button>
                            </div>
                        )}

                        {/* Delivery Information */}
                        <div className="flex w-full flex-col gap-4">
                            <div className="flex w-full items-stretch justify-stretch gap-2.5 p-2" style={{ backgroundColor: '#D0D5DD' }}>
                                <div className="font-medium" style={{ color: '#000000', fontFamily: 'Sora', fontSize: '14px' }}>
                                    配送について
                                </div>
                            </div>
                            <div className="flex w-full flex-col gap-2.5">
                                <div className="flex w-full flex-col p-4" style={{ border: '1px solid #AAB4C3' }}>
                                    <div className="flex justify-between" style={{ color: '#888888' }}>
                                        <div className="text-xs" style={{ fontFamily: 'Noto Sans JP' }}>
                                            配送料：
                                        </div>
                                        <div className="text-sm" style={{ color: '#444444', fontFamily: 'Noto Sans JP' }}>
                                            無料配送
                                        </div>
                                    </div>
                                    <div className="mt-2.5 text-xs" style={{ color: '#888888', fontFamily: 'Noto Sans JP' }}>
                                        9,350円(税込)～購入で無料配送
                                    </div>
                                    <div className="mt-6 flex justify-between" style={{ color: '#888888' }}>
                                        <div className="text-xs" style={{ fontFamily: 'Noto Sans JP' }}>
                                            配送料：
                                        </div>
                                        <div className="text-xs" style={{ color: '#444444', fontFamily: 'Noto Sans JP' }}>
                                            ご注文完了から2日～7日前後のお届け
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Payment Methods - Placeholder */}
                        <div className="flex w-full items-center gap-2.5">{/* Payment methods would be implemented as SVG */}</div>

                        {/* Social Icons */}
                        <div className="flex w-full items-center justify-center gap-4 py-4">
                            {/* Facebook Icon */}
                            <a href="#" className="flex h-10 w-10 items-center justify-center rounded-full">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_346_4511)">
<path d="M12 0C18.6274 0 24 5.37259 24 12C24 18.1352 19.3955 23.1944 13.4538 23.9121V15.667L16.7001 15.667L17.3734 12H13.4538V10.7031C13.4538 9.73417 13.6439 9.06339 14.0799 8.63483C14.5159 8.20627 15.1979 8.01993 16.1817 8.01993C16.4307 8.01993 16.6599 8.02241 16.8633 8.02736C17.1591 8.03456 17.4002 8.047 17.568 8.06467V4.74048C17.501 4.72184 17.4218 4.70321 17.3331 4.68486C17.1321 4.6433 16.8822 4.60324 16.6136 4.56806C16.0523 4.49453 15.4093 4.4423 14.9594 4.4423C13.1424 4.4423 11.7692 4.83102 10.8107 5.63619C9.65388 6.60791 9.10108 8.18622 9.10108 10.4199V12H6.62659V15.667H9.10108V23.6466C3.87432 22.3498 0 17.6277 0 12C0 5.37259 5.37259 0 12 0Z" fill="#444444"/>
</g>
<defs>
<clipPath id="clip0_346_4511">
<rect width="24" height="24" fill="white"/>
</clipPath>
</defs>
</svg>
                            </a>
                            
                            {/* Instagram Icon */}
                            <a href="#" className="flex h-10 w-10 items-center justify-center rounded-full">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_346_4517)">
<path d="M18.3952 7.02212C17.6005 7.02368 16.9543 6.3802 16.9528 5.58548C16.9512 4.79076 17.5947 4.14457 18.3898 4.14302C19.1848 4.14146 19.831 4.78531 19.8326 5.58004C19.8338 6.37476 19.1903 7.02057 18.3952 7.02212Z" fill="white"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M12.0115 18.161C8.60909 18.1676 5.8451 15.4149 5.8385 12.0117C5.83188 8.60923 8.58536 5.84481 11.9878 5.8382C15.3909 5.83159 18.1553 8.5859 18.1619 11.9879C18.1685 15.3912 15.4143 18.1544 12.0115 18.161ZM11.992 8.00035C9.78365 8.00424 7.99594 9.79858 7.99983 12.0074C8.0041 14.2166 9.79882 16.0039 12.0072 15.9996C14.2164 15.9954 16.0041 14.2014 15.9998 11.9922C15.9955 9.78302 14.2008 7.99608 11.992 8.00035Z" fill="#444444"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M4.1192 0.646479C4.88126 0.347876 5.75333 0.143362 7.03015 0.0830982C8.31011 0.0216726 8.71872 0.00767102 11.9769 0.00145262C15.2358 -0.00476578 15.6444 0.00766862 16.9244 0.0644334C18.2016 0.119643 19.0741 0.321049 19.8377 0.616544C20.6277 0.920974 21.298 1.33078 21.966 1.99603C22.6339 2.66205 23.0453 3.33002 23.3536 4.1189C23.6518 4.88174 23.8563 5.75306 23.917 7.03068C23.9776 8.31023 23.9924 8.71847 23.9986 11.9771C24.0048 15.2353 23.9916 15.6443 23.9356 16.925C23.88 18.2014 23.679 19.0743 23.3835 19.8375C23.0783 20.6276 22.6693 21.2979 22.004 21.9659C21.3388 22.6342 20.6701 23.0452 19.8812 23.3539C19.1184 23.6517 18.2471 23.8562 16.9702 23.9173C15.6903 23.9779 15.2817 23.9923 12.0224 23.9985C8.76459 24.0048 8.35598 23.9923 7.07605 23.9359C5.79882 23.88 4.92597 23.6789 4.16275 23.3838C3.37271 23.0782 2.70242 22.6696 2.03446 22.004C1.36611 21.3383 0.954386 20.67 0.646458 19.8811C0.347858 19.1186 0.144107 18.2469 0.0830727 16.9705C0.0220359 15.6901 0.00765506 15.2811 0.00143906 12.0229C-0.00480094 8.76435 0.00803667 8.35611 0.0640167 7.07616C0.1204 5.79855 0.320637 4.92606 0.61613 4.16206C0.921328 3.37239 1.33035 2.70248 1.99637 2.03413C2.6616 1.36616 3.33033 0.954017 4.1192 0.646479ZM4.94154 21.3679C5.36494 21.5308 6.00023 21.7252 7.17014 21.7761C8.43607 21.8309 8.81514 21.843 12.0185 21.8368C15.223 21.8309 15.6021 21.8173 16.8676 21.7579C18.0363 21.7022 18.6716 21.5055 19.0939 21.3407C19.6541 21.1218 20.0531 20.8601 20.4722 20.4406C20.8913 20.0195 21.1506 19.6194 21.3676 19.0591C21.5309 18.6354 21.7249 17.9996 21.7758 16.8297C21.8314 15.5646 21.8431 15.1851 21.8368 11.9809C21.831 8.77757 21.8174 8.3981 21.7572 7.13254C21.7019 5.96339 21.5056 5.32808 21.3404 4.90623C21.1215 4.34519 20.8606 3.94705 20.4399 3.52753C20.0192 3.10801 19.6191 2.84945 19.0581 2.6325C18.6355 2.46881 17.9994 2.27518 16.8303 2.22426C15.5643 2.16865 15.1849 2.15737 11.9808 2.1636C8.77743 2.16982 8.39836 2.18264 7.13281 2.24253C5.9633 2.29812 5.32877 2.49447 4.90575 2.65972C4.34587 2.87861 3.94696 3.13872 3.52746 3.5598C3.10871 3.98087 2.84938 4.38018 2.63244 4.94161C2.46993 5.36464 2.27434 6.00072 2.2242 7.16987C2.16898 8.43581 2.15733 8.81529 2.16355 12.0187C2.16939 15.2228 2.18298 15.6023 2.24248 16.8671C2.29729 18.037 2.49518 18.6715 2.65966 19.0949C2.87855 19.6544 3.13944 20.0533 3.55973 20.4729C3.98081 20.8908 4.38088 21.1509 4.94154 21.3679Z" fill="#444444"/>
</g>
<defs>
<clipPath id="clip0_346_4517">
<rect width="24" height="24" fill="white"/>
</clipPath>
</defs>
</svg>
                            </a>
                            
                            {/* X (Twitter) Icon */}
                            <a href="#" className="flex h-10 w-10 items-center justify-center rounded-full">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M0 12C0 18.6274 5.37258 24 12 24C18.6274 24 24 18.6274 24 12C24 5.37258 18.6274 0 12 0C5.37258 0 0 5.37258 0 12Z" fill="#444444"/>
<path d="M7.02438 7L10.8853 12.5156L7 17H7.87442L11.276 13.0738L14.0243 17H17L12.9219 11.1742L16.5383 7H15.6638L12.5312 10.6159L10.0001 7H7.02438ZM8.31028 7.68817H9.67731L15.7139 16.3117H14.3469L8.31028 7.68817Z" fill="white"/>
</svg>
                            </a>
                            

                            
                            {/* Reddit Icon */}
                            <a href="#" className="flex h-10 w-10 items-center justify-center rounded-full">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M0 12C0 18.6274 5.37258 24 12 24C18.6274 24 24 18.6274 24 12C24 5.37258 18.6274 0 12 0C5.37258 0 0 5.37258 0 12Z" fill="#444444"/>
<path d="M17.9789 12.1921C17.9789 11.4658 17.3895 10.8763 16.6632 10.8763C16.3053 10.8763 15.9895 11.0132 15.7579 11.2447C14.8632 10.6026 13.6211 10.1816 12.2526 10.1289L12.8526 7.31842L14.8 7.72895C14.8211 8.22368 15.2316 8.62368 15.7368 8.62368C16.2526 8.62368 16.6737 8.20263 16.6737 7.68684C16.6737 7.17105 16.2526 6.75 15.7368 6.75C15.3684 6.75 15.0526 6.96053 14.9053 7.27632L12.7263 6.81316C12.6632 6.80263 12.6 6.81316 12.5474 6.84474C12.4947 6.87632 12.4632 6.92895 12.4421 6.99211L11.7789 10.1289C10.3789 10.1711 9.12632 10.5816 8.22105 11.2447C7.98947 11.0237 7.66316 10.8763 7.31579 10.8763C6.58947 10.8763 6 11.4658 6 12.1921C6 12.7289 6.31579 13.1816 6.77895 13.3921C6.75789 13.5184 6.74737 13.6553 6.74737 13.7921C6.74737 15.8132 9.09474 17.4447 12 17.4447C14.9053 17.4447 17.2526 15.8132 17.2526 13.7921C17.2526 13.6553 17.2421 13.5289 17.2211 13.4026C17.6526 13.1921 17.9789 12.7289 17.9789 12.1921ZM8.97895 13.1289C8.97895 12.6132 9.4 12.1921 9.91579 12.1921C10.4316 12.1921 10.8526 12.6132 10.8526 13.1289C10.8526 13.6447 10.4316 14.0658 9.91579 14.0658C9.4 14.0658 8.97895 13.6447 8.97895 13.1289ZM14.2105 15.6026C13.5684 16.2447 12.3474 16.2868 11.9895 16.2868C11.6316 16.2868 10.4 16.2342 9.76842 15.6026C9.67368 15.5079 9.67368 15.35 9.76842 15.2553C9.86316 15.1605 10.0211 15.1605 10.1158 15.2553C10.5158 15.6553 11.3789 15.8026 12 15.8026C12.6211 15.8026 13.4737 15.6553 13.8842 15.2553C13.9789 15.1605 14.1368 15.1605 14.2316 15.2553C14.3053 15.3605 14.3053 15.5079 14.2105 15.6026ZM14.0421 14.0658C13.5263 14.0658 13.1053 13.6447 13.1053 13.1289C13.1053 12.6132 13.5263 12.1921 14.0421 12.1921C14.5579 12.1921 14.9789 12.6132 14.9789 13.1289C14.9789 13.6447 14.5579 14.0658 14.0421 14.0658Z" fill="white"/>
</svg>
                            </a>
                            {/* YouTube Icon */}
                            <a href="#" className="flex h-10 w-10 items-center justify-center rounded-full">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_346_4513)">
<path fill-rule="evenodd" clip-rule="evenodd" d="M22.7466 4.83407C23.119 5.20883 23.3864 5.67482 23.5221 6.18541C24.0239 8.06995 24.0239 12 24.0239 12C24.0239 12 24.0239 15.93 23.5221 17.8145C23.3864 18.3251 23.119 18.7911 22.7466 19.1658C22.3743 19.5406 21.91 19.811 21.4003 19.95C19.5239 20.4545 12.0239 20.4545 12.0239 20.4545C12.0239 20.4545 4.52393 20.4545 2.64756 19.95C2.13786 19.811 1.67358 19.5406 1.30121 19.1658C0.928842 18.7911 0.661431 18.3251 0.525744 17.8145C0.0239258 15.93 0.0239258 12 0.0239258 12C0.0239258 12 0.0239258 8.06995 0.525744 6.18541C0.661431 5.67482 0.928842 5.20883 1.30121 4.83407C1.67358 4.4593 2.13786 4.18891 2.64756 4.04996C4.52393 3.54541 12.0239 3.54541 12.0239 3.54541C12.0239 3.54541 19.5239 3.54541 21.4003 4.04996C21.91 4.18891 22.3743 4.4593 22.7466 4.83407ZM15.8421 12L9.5694 8.43135V15.5686L15.8421 12Z" fill="#444444"/>
</g>
<defs>
<clipPath id="clip0_346_4513">
<rect width="24" height="24" fill="white"/>
</clipPath>
</defs>
</svg>
                            </a>
                        </div>
                    </div>
                </div>

                {/* Product Detail Sections from Figma */}
                <div className="mt-4 border-t border-gray-200 pt-4">
                    {/* Tab Navigation */}
                    <div className="mb-4 flex space-x-6">
                        <button
                            className={`text-sm ${activeTab === 'details' ? 'border-b-2 border-gray-800 pb-1 text-gray-800' : 'border-b border-transparent pb-1 text-gray-500'}`}
                            onClick={() => setActiveTab('details')}
                        >
                            製品詳細
                        </button>
                        <button
                            className={`text-sm ${activeTab === 'notes' ? 'border-b-2 border-gray-800 pb-1 text-gray-800' : 'border-b border-transparent pb-1 text-gray-500'}`}
                            onClick={() => setActiveTab('notes')}
                        >
                            香りノート
                        </button>
                        <button
                            className={`text-sm ${activeTab === 'chart' ? 'border-b-2 border-gray-800 pb-1 text-gray-800' : 'border-b border-transparent pb-1 text-gray-500'}`}
                            onClick={() => setActiveTab('chart')}
                        >
                            レーダーチャート
                        </button>
                        <button
                            className={`text-sm ${activeTab === 'reviews' ? 'border-b-2 border-gray-800 pb-1 text-gray-800' : 'border-b border-transparent pb-1 text-gray-500'}`}
                            onClick={() => setActiveTab('reviews')}
                        >
                            レビュー
                        </button>
                    </div>

                    {/* Tab Content */}
                    <div>
                        {/* Product Details Tab */}
                        {activeTab === 'details' && (
                            <div className="mb-4 border border-gray-300 p-3 text-sm">
                                <div className="whitespace-pre-wrap text-gray-700">{product.long_desc}</div>
                            </div>
                        )}

                        {/* Fragrance Notes Tab */}
                        {activeTab === 'notes' && (
                            <div className="mb-4 border border-gray-300 p-3 text-sm">
                                <div className="text-gray-700">香りノートのコンテンツがここに表示されます。</div>
                            </div>
                        )}

                        {/* Radar Chart Tab */}
                        {activeTab === 'chart' && (
                            <div className="mb-4 border border-gray-300 p-3 text-sm">
                                <div className="text-gray-700">レーダーチャートのコンテンツがここに表示されます。</div>
                            </div>
                        )}

                        {/* Reviews Tab */}
                        {activeTab === 'reviews' && (
                            <div id="reviews">
                                <div className="mb-3 flex items-center justify-between">
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
                                        {loadingReviews ? (
                                            <div className="py-3 text-center text-gray-500">レビューを読み込み中...</div>
                                        ) : (
                                            <ReviewList reviews={reviews} productId={product.id} />
                                        )}
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
                                                    averageRating:
                                                        (productRatings.averageRating * productRatings.reviewCount + rating) /
                                                        (productRatings.reviewCount + 1),
                                                    reviewCount: productRatings.reviewCount + 1,
                                                };

                                                setProductRatings(newRatingData);

                                                // Store the updated ratings in the fresh review cache
                                                import('@/lib/review-cache').then(({ updateProductReviewData }) => {
                                                    updateProductReviewData(
                                                        product.id,
                                                        productRatings.averageRating,
                                                        productRatings.reviewCount,
                                                        rating,
                                                    );
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
                                <h2 className="mb-6 font-['Hiragino_Mincho_ProN'] text-2xl leading-tight font-semibold text-gray-800">関連商品</h2>
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
                                                slug={p.slug}
                                                id={p.id}
                                                showRatingIcon={true}
                                                showGenderIcon={false}
                                                showWishlistIcon={true}
                                            />
                                        ))}
                                    </ImprovedCarousel>
                                </div>
                                <div className="hidden grid-cols-2 justify-items-center gap-4 sm:grid sm:gap-6 md:grid-cols-3 lg:grid-cols-4">
                                    {related.map((p, index) => (
                                        <ProductCard
                                            key={p.id}
                                            productImageSrc={p.image || '/perfume-images/perfume-1.png'}
                                            category={product.brand?.name || 'ブランド名'}
                                            productName={p.name}
                                            price={yen(p.price_cents)}
                                            slug={p.slug}
                                            id={p.id}
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
                                success: 'border border-gray-200 bg-[#FCFCF7] text-gray-800',
                                warning: 'border border-gray-200 bg-[#FCFCF7] text-gray-800',
                                error: 'border border-gray-200 bg-[#FCFCF7] text-gray-800',
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
