import { Head } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

type Variant = {
    id?: number; // Prefer sending this from backend (needed for 4.3)
    sku: string;
    price_cents: number;
    compare_at_cents: number | null;
    stock?: number | null;
    safety_stock?: number | null;
    managed?: boolean;
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

    const [selectedVariant, setSelectedVariant] = useState<Variant | null>(product.variants[0] || null);
    const [quantity, setQuantity] = useState(1);
    const [isAddingToCart, setIsAddingToCart] = useState(false);
    const [isWishlisting, setIsWishlisting] = useState(false);

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

    const handleAddToCartEvent = async () => {
        if (!selectedVariant) return;
        setIsAddingToCart(true);
        try {
            // Spec expects `variant_id`; we also include `sku` temporarily for compatibility.
            // Server should ignore extra fields. Later (4.3) we will also call /cart for actual add.
            await postJson('/e/add-to-cart', {
                product_id: product.id,
                variant_id: selectedVariant.id ?? null, // preferred
                sku: selectedVariant.sku, // fallback (remove when backend mapping is live)
                qty: quantity,
            });
            // TODO: toast success
        } catch (error) {
            console.error('Failed to add to cart event:', error);
            alert('Failed to add to cart. Please try again.');
        } finally {
            setIsAddingToCart(false);
        }
    };

    const handleWishlistAdd = async () => {
        setIsWishlisting(true);
        try {
            // Use real wishlist route from your routes spec (not /e/wishlist-add)
            await postJson('/wishlist', { product_id: product.id });
            // TODO: toggle UI state (filled heart) if you want
        } catch (error) {
            console.error('Failed to add to wishlist:', error);
            alert('Failed to add to wishlist. Please try again.');
        } finally {
            setIsWishlisting(false);
        }
    };

    const stockBadgeFor = (v: Variant) => {
        const stock = v.stock ?? null;
        const managed = v.managed ?? false;
        if (!managed) return 'In stock';
        if ((stock ?? 0) <= 0) return 'Out of stock';
        if (stock! <= (v.safety_stock ?? 1)) return 'Low stock';
        return 'In stock';
    };

    const MainImage = () => {
        const hero = gallery[activeIndex];
        const src = hero?.url ?? product.image ?? null;
        if (!src) {
            return <div className="grid h-full w-full place-items-center text-sm text-neutral-500">No image</div>;
        }
        return <img src={src} alt={hero?.alt ?? product.name} className="h-full w-full object-cover" />;
    };

    return (
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
                                        aria-label={`Thumbnail ${i + 1}`}
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
                                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Select Variant</label>
                                <div className="space-y-2">
                                    {product.variants.map((v) => {
                                        const badge = stockBadgeFor(v);
                                        const isSelected = selectedVariant?.sku === v.sku;
                                        const isOut = badge === 'Out of stock';
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
                                                    </div>
                                                    <span
                                                        className={`rounded px-2 py-0.5 text-xs ${
                                                            badge === 'Out of stock'
                                                                ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'
                                                                : badge === 'Low stock'
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
                                        <span
                                            className={`ml-2 rounded px-2 py-0.5 text-xs ${
                                                badge === 'Out of stock'
                                                    ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'
                                                    : badge === 'Low stock'
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
                                    <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Quantity</label>
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
                                        {isAddingToCart ? 'Adding...' : 'Add to Cart'}
                                    </button>
                                    <button
                                        onClick={handleWishlistAdd}
                                        disabled={isWishlisting}
                                        className="rounded-lg border border-gray-300 px-4 py-3 text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                                        aria-label="Add to wishlist"
                                    >
                                        {isWishlisting ? '...' : '♡'}
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>

                    {product.long_desc && <div className="prose mt-6 max-w-none whitespace-pre-wrap">{product.long_desc}</div>}
                </div>
            </div>

            {related.length > 0 && (
                <div className="mt-10">
                    <h2 className="mb-3 text-lg font-semibold">Related products</h2>
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
        </div>
    );
}
