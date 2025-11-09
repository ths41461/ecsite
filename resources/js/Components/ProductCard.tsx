import { getFreshReviewDataForProduct } from '@/lib/review-cache';
import { Link } from '@inertiajs/react';
import { Heart } from 'lucide-react';
import React, { useState } from 'react';

// ============================================================================
// V2 (Complex) Card: Used in Products/Index.tsx
// ============================================================================

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
    variants?: Variant[]; // Make variants optional, will fetch if not provided
};

function yen(n: number) {
    return n.toLocaleString(undefined, { style: 'currency', currency: 'JPY' });
}

const renderStarRating = (rating: number) => {
    const stars = [];
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;

    for (let i = 1; i <= 5; i++) {
        if (i <= fullStars) {
            stars.push(
                <svg key={i} width="20" height="20" viewBox="0 0 20 20" fill="#616161" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 1.667L12.667 7.5L18.333 8.333L14 12.5L15.333 18.333L10 15.833L4.667 18.333L6 12.5L1.667 8.333L7.333 7.5L10 1.667Z" />
                </svg>,
            );
        } else if (i === fullStars + 1 && hasHalfStar) {
            stars.push(
                <div key={i} className="relative">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="#616161" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 1.667L12.667 7.5L18.333 8.333L14 12.5L15.333 18.333L10 15.833L4.667 18.333L6 12.5L1.667 8.333L7.333 7.5L10 1.667Z" />
                    </svg>
                    <div className="absolute inset-0 overflow-hidden" style={{ width: '50%' }}>
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="#616161" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 1.667L12.667 7.5L18.333 8.333L14 12.5L15.333 18.333L10 15.833L4.667 18.333L6 12.5L1.667 8.333L7.333 7.5L10 1.667Z" />
                        </svg>
                    </div>
                </div>,
            );
        } else {
            stars.push(
                <svg key={i} width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="#616161" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 1.667L12.667 7.5L18.333 8.333L14 12.5L15.333 18.333L10 15.833L4.667 18.333L6 12.5L1.667 8.333L7.333 7.5L10 1.667Z" />
                </svg>,
            );
        }
    }

    return (
        <div className="flex items-center" style={{ gap: '4px' }}>
            {stars}
        </div>
    );
};

// ============================================================================
// V1 (Simple) Card: Used in other parts of the application
// ============================================================================

interface SimpleProductCardProps {
    productImageSrc: string;
    category: string;
    productName: string;
    price: string;
    slug: string; // Add slug property for routing
    id: number; // Add id property for cart operations
    variants?: Variant[]; // Make variants optional, will fetch if not provided
    genders?: string[]; // Gender information from product variants
    showRatingIcon?: boolean;
    showWishlistIcon?: boolean;
    brand?: string | null;
    averageRating?: number;
    reviewCount?: number;
    salePrice?: number | null;
    priceValue?: number;
    disableCartDrawer?: boolean;
}

const SimpleProductCard: React.FC<SimpleProductCardProps> = ({
    productImageSrc,
    category,
    productName,
    price,
    slug,
    id,
    variants,
    genders,
    showRatingIcon,
    showWishlistIcon,
    brand,
    averageRating,
    reviewCount,
    salePrice,
    priceValue,
    disableCartDrawer = false,
}) => {
    const [isAddingToCart, setIsAddingToCart] = useState(false);
    const [selectedVariantIndex, setSelectedVariantIndex] = useState(0); // Track selected variant

    const hasSale = salePrice != null && salePrice < (priceValue || 0);
    const freshData = getFreshReviewDataForProduct(id);
    const displayRating = freshData?.averageRating ?? averageRating;
    const displayReviewCount = freshData?.reviewCount ?? reviewCount;

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
            let selectedVariant;

            // Use variants from props if available, otherwise fetch from API
            if (variants && variants.length > 0) {
                // Ensure selectedVariantIndex is within bounds
                const validIndex = Math.max(0, Math.min(selectedVariantIndex, variants.length - 1));
                selectedVariant = variants[validIndex];

                console.log('SimpleProductCard - Using variant from props:', {
                    productId: id,
                    selectedVariantIndex,
                    validIndex,
                    variantCount: variants.length,
                    selectedVariantId: selectedVariant?.id,
                    selectedVariantSku: selectedVariant?.sku,
                });

                if (!selectedVariant || !selectedVariant.id) {
                    throw new Error('選択された商品バリエーションが無効です');
                }
            } else {
                // Fetch product details to get variants (fallback)
                console.log('SimpleProductCard - Fetching product variants from API:', slug);

                const productResponse = await fetch(`/products/${slug}`, {
                    headers: {
                        Accept: 'application/json',
                    },
                });
                if (!productResponse.ok) {
                    throw new Error('商品情報を取得できませんでした');
                }

                // When Accept: application/json is set, Inertia returns props directly, not wrapped
                const productData = await productResponse.json();

                console.log('SimpleProductCard - Product data fetched:', {
                    product: productData?.product,
                    hasVariants: !!productData?.product?.variants,
                    variantCount: productData?.product?.variants?.length || 0,
                });

                // When requesting JSON from Inertia, it returns the props directly
                // So productData should contain: { product: {...}, gallery: [...], related: [...] }
                const productVariants = productData?.product?.variants || [];

                if (productVariants.length === 0) {
                    throw new Error('商品のバリエーションが見つかりません');
                }

                selectedVariant = productVariants[0]; // Fallback to first if no index selected

                console.log('SimpleProductCard - Using fetched variant:', {
                    variantId: selectedVariant?.id,
                    variantSku: selectedVariant?.sku,
                });

                if (!selectedVariant || !selectedVariant.id) {
                    throw new Error('商品バリエーションIDが見つかりません');
                }
            }

            // Analytics/event (non-blocking if it fails)
            postJson('/e/add-to-cart', {
                product_id: id,
                variant_id: selectedVariant.id ?? null,
                sku: selectedVariant.sku,
                qty: 1,
            }).catch(() => {});

            console.log('SimpleProductCard - About to call cart API:', {
                variantId: selectedVariant.id,
                qty: 1,
                sku: selectedVariant.sku,
                productId: id,
            });

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
                console.error('SimpleProductCard - Cart API error:', {
                    status: res.status,
                    statusText: res.statusText,
                    errorText: text,
                    variantId: selectedVariant.id,
                });
                throw new Error(text || 'カートへの追加に失敗しました');
            }

            // Inspect response to detect clamp notice for a better message
            let cart: any | null = null;
            try {
                // Check if the response is JSON before parsing
                const contentType = res.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    cart = (await res.json()) as any;
                } else {
                    // If not JSON, try to get text response for debugging
                    const textResponse = await res.text();
                    console.warn('Cart API returned non-JSON response:', textResponse);
                    // Create a basic cart object structure to avoid breaking downstream code
                    cart = { message: 'Cart updated successfully' };
                }
            } catch (error) {
                console.error('Failed to parse cart response as JSON:', error);
                console.error('Response status:', res.status, 'Response headers:', [...res.headers.entries()]);
                
                // Attempt to get the raw response text to understand what we received
                try {
                    const responseText = await res.text();
                    console.error('Raw response text:', responseText.substring(0, 500) + '...'); // First 500 chars
                } catch (textError) {
                    console.error('Could not read raw response text:', textError);
                }
                
                // Fallback to a basic cart object to prevent breaking the rest of the application
                cart = { message: 'Cart updated with non-JSON response' };
            }



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
        } catch (error) {
            console.error('カートへの追加に失敗しました。もう一度お試しください。', error);
        } finally {
            setIsAddingToCart(false);
        }
    };

    return (
        <div className="flex w-72 flex-col border border-gray-200 bg-white shadow-sm">
            <div className="relative flex h-60 w-full items-center justify-center overflow-hidden bg-[#FAF7EF] p-4">
                <Link href={`/products/${slug}`}>
                    <img src={productImageSrc} alt={productName} className="h-full w-full object-cover" />
                </Link>
                {showWishlistIcon && (
                    <div className="absolute top-2 right-2 rounded-full bg-white p-1 shadow-md">
                        <Heart className="h-5 w-5 text-gray-700" />
                    </div>
                )}
            </div>
            <div className="flex flex-col gap-2 p-3">
                {/* Brand Name (using category field) */}
                {category && <div className="mb-1 truncate font-sans text-xs font-normal text-[#6B7280]">{category}</div>}

                {/* Product Name */}
                <div className="flex min-w-0 items-center justify-between">
                    <Link href={`/products/${slug}`} className="truncate">
                        <h3 className="truncate font-['Hiragino_Mincho_ProN'] text-base leading-tight font-medium text-[#1F2937] hover:underline">
                            {productName}
                        </h3>
                    </Link>
                </div>

                {/* Rating/Review section */}
                <div className="flex items-end justify-between">
                    <div></div> {/* Spacer to keep ratings aligned right */}
                    {displayRating !== undefined && displayRating > 0 && (
                        <div className="flex items-center">
                            {renderStarRating(displayRating)}
                            {displayReviewCount !== undefined && displayReviewCount > 0 && (
                                <span className="ml-1 text-xs text-[#6B7280]">({displayReviewCount})</span>
                            )}
                        </div>
                    )}
                </div>

                {/* Gender Icons */}
                {genders && genders.length > 0 && (
                    <div className="flex justify-end">
                        <div className="flex gap-1">
                            {genders.map((gender) => (
                                <div
                                    key={gender}
                                    className="flex h-5 w-5 items-center justify-center rounded-full border border-[#D1D5DB] text-xs text-[#4B5563]"
                                    title={gender === 'men' ? 'メンズ' : gender === 'women' ? 'レディース' : 'ユニセックス'}
                                >
                                    {gender === 'men' ? '♂' : gender === 'women' ? '♀' : '⚥'}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Price Section */}
                <div className="flex flex-col">
                    {hasSale && (
                        <span className="text-right font-serif text-sm font-normal text-[#6B7280] line-through">
                            ￥{priceValue?.toLocaleString()}
                        </span>
                    )}
                    <span className={`text-right font-serif text-lg leading-none font-bold text-[#1F2937] ${hasSale ? 'mt-1' : ''}`}>
                        ￥{(hasSale ? salePrice! : priceValue || price).toLocaleString()}
                    </span>
                </div>

                {/* Variant Selector */}
                {variants && variants.length > 1 && (
                    <div className="mb-2 flex flex-wrap justify-center gap-1">
                        {variants.map((variant, index) => (
                            <button
                                key={index}
                                type="button"
                                onClick={() => setSelectedVariantIndex(index)}
                                className={`rounded border px-2 py-1 text-xs ${
                                    selectedVariantIndex === index ? 'border-gray-800 bg-gray-800 text-white' : 'border-gray-300 hover:bg-gray-100'
                                }`}
                            >
                                {variant.options?.size_ml
                                    ? `${variant.options.size_ml}ml`
                                    : variant.options?.gender
                                      ? variant.options.gender === 'men'
                                          ? '♂'
                                          : '♀'
                                      : `Variant ${index + 1}`}
                            </button>
                        ))}
                    </div>
                )}

                {/* Add to Cart Button or View Product Button based on disableCartDrawer prop */}
                {disableCartDrawer ? (
                    <Link
                        href={`/products/${slug}`}
                        className="flex h-10 w-full items-center justify-center border border-[#EEDDD4] px-4 py-2 text-sm font-medium shadow-sm focus:ring-2 focus:ring-[#EAB308] focus:ring-offset-2 focus:outline-none bg-[#EAB308] text-white"
                    >
                        <img src="/icons/icon-eye.svg" alt="View" className="mr-2 h-5 w-5" />
                        製品を見る
                    </Link>
                ) : (
                    <button
                        onClick={handleAddToCart}
                        disabled={isAddingToCart}
                        className={`flex h-10 w-full items-center justify-center border border-[#EEDDD4] px-4 py-2 text-sm font-medium shadow-sm focus:ring-2 focus:ring-[#EAB308] focus:ring-offset-2 focus:outline-none ${isAddingToCart ? 'bg-gray-400' : 'bg-[#EAB308] text-white'}`}
                    >
                        <img src="/icons/icon-cart.svg" alt="Cart" className="mr-2 h-5 w-5" />
                        {isAddingToCart ? '追加中...' : 'カートに追加'}
                    </button>
                )}
            </div>


        </div>
    );
};

// ============================================================================
// Adapter Component: The single default export
// ============================================================================

export default function ProductCard(props: any) {
    // Use simple card for all cases since ComplexProductCard was removed
    return <SimpleProductCard {...props} />;
}
