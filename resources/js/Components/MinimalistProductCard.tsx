import { getFreshReviewDataForProduct } from '@/lib/review-cache';
import { Link } from '@inertiajs/react';
import { useState } from 'react';

export type ProductCardData = {
    id: number;
    slug: string;
    name: string;
    brand?: string | null;
    price: number; // in JPY
    salePrice?: number | null; // in JPY
    imageUrl?: string | null;
    imageAlt?: string | null;
    averageRating?: number;
    reviewCount?: number;
    genders?: string[];
    sizes?: number[];
};

function yen(n: number) {
    return n.toLocaleString(undefined, { style: 'currency', currency: 'JPY' });
}

const renderStarRating = (rating: number) => {
    const stars = [];
    const fullStars = Math.round(rating);

    for (let i = 1; i <= 5; i++) {
        stars.push(
            <svg key={i} width="16" height="16" viewBox="0 0 20 20" fill={i <= fullStars ? '#616161' : '#E0E0E0'} xmlns="http://www.w3.org/2000/svg">
                <path d="M10 1.667L12.667 7.5L18.333 8.333L14 12.5L15.333 18.333L10 15.833L4.667 18.333L6 12.5L1.667 8.333L7.333 7.5L10 1.667Z" />
            </svg>,
        );
    }
    return <div className="flex items-center gap-1">{stars}</div>;
};

export default function MinimalistProductCard({ product }: { product: ProductCardData }) {
    if (!product) {
        return null;
    }

    const [isAddingToCart, setIsAddingToCart] = useState(false);
    const hasSale = product.salePrice != null && product.salePrice < product.price;
    const freshData = getFreshReviewDataForProduct(product.id);
    const displayRating = freshData?.averageRating ?? product.averageRating;
    const displayReviewCount = freshData?.reviewCount ?? product.reviewCount;

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

    // Function to handle adding to cart
    const handleAddToCart = async () => {
        setIsAddingToCart(true);
        try {
            // Fetch product details to get variants
            const productResponse = await fetch(`/products/${product.slug}`);
            if (!productResponse.ok) {
                throw new Error('商品情報を取得できませんでした');
            }

            const productData = await productResponse.json();
            const variants = productData?.product?.variants || [];

            if (variants.length === 0) {
                throw new Error('商品のバリエーションが見つかりません');
            }

            // Use the first available variant
            const selectedVariant = variants[0];

            // Analytics/event (non-blocking if it fails)
            postJson('/e/add-to-cart', {
                product_id: product.id,
                variant_id: selectedVariant.id ?? null,
                sku: selectedVariant.sku,
                qty: 1,
            }).catch(() => {});

            // Actual cart mutation
            if (selectedVariant.id == null) {
                throw new Error('商品バリエーションIDが見つかりません');
            }
            const res = await postJson('/cart', {
                variant_id: selectedVariant.id,
                qty: 1,
            });
            if (!res.ok) {
                const text = await res.text();
                throw new Error(text || 'カートへの追加に失敗しました');
            }

            // Show success message
            alert('カートに追加しました。');
        } catch (error) {
            console.error('カートへの追加に失敗しました:', error);
            alert('カートへの追加に失敗しました。');
        } finally {
            setIsAddingToCart(false);
        }
    };

    return (
        <div className="group relative flex h-[24.5rem] w-full max-w-[18rem] flex-col overflow-hidden border border-neutral-200 bg-white font-sans lg:mx-2 lg:mb-4">
            {/* Favorite Button */}
            <button
                type="button"
                aria-label="お気に入りに追加"
                className="absolute top-3 right-3 z-20 flex h-9 w-9 items-center justify-center bg-white/70 backdrop-blur-sm"
            >
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M10 18.333L7.833 16.5C3.5 13.5 1.667 11.5 1.667 8.333C1.667 5.5 3.5 3.667 5.833 3.667C7.5 3.667 9.167 4.5 10 5.5C10.833 4.5 12.5 3.667 14.167 3.667C16.5 3.667 18.333 5.5 18.333 8.333C18.333 11.5 16.5 13.5 12.167 16.5L10 18.333Z"
                        stroke="#363842"
                        strokeWidth="1.667"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                </svg>
            </button>

            {/* Main Clickable Area */}
            <Link href={`/products/${product.slug}`} className="flex h-full flex-col">
                {/* Image Area */}
                <div className="flex h-56 flex-shrink-0 items-center justify-center p-4">
                    <div className="h-full w-full">
                        {' '}
                        {product.imageUrl ? (
                            <img
                                src={product.imageUrl}
                                alt={product.imageAlt ?? product.name}
                                className="h-full w-full object-contain"
                                loading="lazy"
                            />
                        ) : (
                            <div className="flex h-full w-full items-center justify-center bg-neutral-50 text-xs text-neutral-500">画像なし</div>
                        )}
                    </div>
                </div>

                {/* Content Area with Hover Reveal */}
                <div className="relative flex-grow p-4 text-center">
                    {/* Default View */}
                    <div className="absolute inset-0 flex flex-col justify-center opacity-100 group-hover:opacity-0">
                        <div>
                            {product.brand ? (
                                <div className="h-[18px]">
                                    <div className="text-xs font-medium tracking-wider text-neutral-600 uppercase">{product.brand}</div>
                                </div>
                            ) : (
                                <div className="h-[18px]" />
                            )}
                            <h3 className="font-hiragino-mincho mt-1 text-base font-medium text-neutral-800">{product.name}</h3>
                            {product.genders && product.genders.length > 0 ? (
                                <div className="mt-2 flex h-[28px] justify-center gap-1">
                                    {product.genders.map((gender) => (
                                        <div
                                            key={gender}
                                            className="flex h-5 w-5 items-center justify-center border border-neutral-300 text-xs text-neutral-700"
                                            title={gender === 'men' ? 'メンズ' : gender === 'women' ? 'レディース' : 'ユニセックス'}
                                        >
                                            {gender === 'men' ? '♂' : gender === 'women' ? '♀' : '⚥'}
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="mt-2 h-[28px]" />
                            )}
                            <div className="mt-4 flex items-baseline justify-center gap-2 pt-2">
                                <span className={`font-hiragino-mincho text-base font-semibold text-neutral-900 ${hasSale ? 'text-red-600' : ''}`}>
                                    {yen(hasSale ? product.salePrice! : product.price)}
                                </span>
                                {hasSale && <span className="text-sm text-neutral-500 line-through">{yen(product.price)}</span>}
                            </div>
                        </div>
                    </div>

                    {/* Hover Reveal View */}
                    <div className="absolute inset-0 z-10 flex flex-col items-center justify-center gap-2 bg-white opacity-0 group-hover:opacity-100">
                        {displayRating !== undefined && displayRating > 0 && displayReviewCount !== undefined && displayReviewCount > 0 ? (
                            <div className="flex h-[24px] items-center gap-1 text-sm text-neutral-600">
                                {renderStarRating(displayRating)}
                                <span>({displayReviewCount} レビュー)</span>
                            </div>
                        ) : (
                            <div className="h-[24px]" />
                        )}
                        {product.sizes && product.sizes.length > 0 ? (
                            <div className="h-[24px] text-sm font-medium text-gray-700">
                                サイズ:{' '}
                                {product.sizes
                                    .map((s, i) => (
                                        <span key={i} className="font-semibold text-gray-800">
                                            {s}ml
                                        </span>
                                    ))
                                    .reduce((prev, curr) => [prev, ', ', curr] as any)}
                            </div>
                        ) : (
                            <div className="h-[24px]" />
                        )}
                        <button
                            type="button"
                            aria-label="カートに追加"
                            onClick={(e) => {
                                e.preventDefault(); // Prevent link navigation
                                e.stopPropagation(); // Stop event bubbling
                                handleAddToCart();
                            }}
                            disabled={isAddingToCart}
                            className={`mt-2 flex h-10 w-full max-w-[9.375rem] items-center justify-center gap-2 px-4 text-sm font-medium ${
                                isAddingToCart ? 'bg-gray-400' : 'bg-[#EAB308]'
                            }`}
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M3 3H5L5.4 5M7 13H17L21 5H5.4M7 13L5.4 5M7 13L4.707 15.293C4.077 15.923 4.523 17 5.414 17H17M17 17C15.895 17 15 17.895 15 19C15 20.105 15.895 21 17 21C18.105 21 19 20.105 19 19C19 17.895 18.105 17 17 17ZM9 19C9 20.105 8.105 21 7 21C5.895 21 5 20.105 5 19C5 17.895 5.895 17 7 17C8.105 17 9 17.895 9 19Z"
                                    stroke="black"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                />
                            </svg>
                            <span>{isAddingToCart ? '追加中...' : 'カートに追加'}</span>
                        </button>
                    </div>
                </div>
            </Link>
        </div>
    );
}
