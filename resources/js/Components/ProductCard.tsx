import { Link } from '@inertiajs/react';
import { getFreshReviewDataForProduct } from '@/lib/review-cache';
import { Heart } from 'lucide-react';
import React from 'react';

// ============================================================================
// V2 (Complex) Card: Used in Products/Index.tsx
// ============================================================================

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

function ComplexProductCard({ product }: { product: ProductCardData }) {
    const hasSale = product.salePrice != null && product.salePrice < product.price;
    const freshData = getFreshReviewDataForProduct(product.id);
    const displayRating = freshData?.averageRating ?? product.averageRating;
    const displayReviewCount = freshData?.reviewCount ?? product.reviewCount;

    return (
        <div className="relative flex h-[392px] w-[288px] flex-col border border-[#D8D9E0] bg-white font-sans">
            <button
                type="button"
                aria-label="お気に入りに追加"
                className="absolute top-4 right-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-[#363842]"
            >
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M10 18.333L7.833 16.5C3.5 13.5 1.667 11.5 1.667 8.333C1.667 5.5 3.5 3.667 5.833 3.667C7.5 3.667 9.167 4.5 10 5.5C10.833 4.5 12.5 3.667 14.167 3.667C16.5 3.667 18.333 5.5 18.333 8.333C18.333 11.5 16.5 13.5 12.167 16.5L10 18.333Z"
                        stroke="white"
                        strokeWidth="1.667"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                </svg>
            </button>

            <Link href={`/products/${product.slug}`} aria-label={`View ${product.name}`}>
                <div className="flex h-[232px] flex-shrink-0 items-start justify-center pt-9">
                    <div className="h-[196px] w-[160px]">
                        {product.imageUrl ? (
                            <img
                                src={product.imageUrl}
                                alt={product.imageAlt ?? product.name}
                                className="h-full w-full object-cover"
                                loading="lazy"
                            />
                        ) : (
                            <div className="flex h-full w-full items-center justify-center bg-gray-50 text-xs text-gray-500">
                                画像なし
                            </div>
                        )}
                    </div>
                </div>
            </Link>

            <div className="flex flex-grow flex-col px-2.5 pt-2 pb-4">
                {product.brand && <div className="font-sans text-xs font-normal text-[#81859C] mb-1">{product.brand}</div>}
                <Link href={`/products/${product.slug}`}>
                    <h3 className="font-serif text-xl font-bold leading-snug text-[#363842] hover:underline">{product.name}</h3>
                </Link>

                <div className="mt-1 flex gap-1">
                    {product.genders &&
                        product.genders.map((gender) => (
                            <div
                                key={gender}
                                className="flex h-5 w-5 items-center justify-center rounded-full border border-[#D8D9E0] text-xs text-[#363842]"
                                title={gender === 'men' ? 'メンズ' : gender === 'women' ? 'レディース' : 'ユニセックス'}
                            >
                                {gender === 'men' ? '♂' : gender === 'women' ? '♀' : '⚥'}
                            </div>
                        ))}
                </div>

                <div className="mt-auto">
                    {displayRating !== undefined && displayRating > 0 && (
                        <div className="mb-2 flex items-center">
                            {renderStarRating(displayRating)}
                            {displayReviewCount !== undefined && displayReviewCount > 0 && (
                                <span className="ml-2 text-xs text-gray-500">({displayReviewCount})</span>
                            )}
                        </div>
                    )}
                    <div className="flex items-center justify-between">
                        <div className="flex items-baseline gap-1">
                            <span className="font-serif text-lg font-semibold leading-none text-[#363842]">
                                ￥{yen(hasSale ? product.salePrice! : product.price)}
                            </span>
                            {hasSale && (
                                <span className="font-serif ml-1 text-sm font-normal text-[#363842]">({product.sizes?.[0]}ml)</span>
                            )}
                        </div>
                        <button
                            type="button"
                            aria-label="カートに追加"
                            className="relative z-10 flex h-[52px] items-center justify-center gap-1.5 bg-[#EAB308] px-3"
                        >
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M3 3H5L5.4 5M7 13H17L21 5H5.4M7 13L5.4 5M7 13L4.707 15.293C4.077 15.923 4.523 17 5.414 17H17M17 17C15.895 17 15 17.895 15 19C15 20.105 15.895 21 17 21C18.105 21 19 20.105 19 19C19 17.895 18.105 17 17 17ZM9 19C9 20.105 8.105 21 7 21C5.895 21 5 20.105 5 19C5 17.895 5.895 17 7 17C8.105 17 9 17.895 9 19Z"
                                    stroke="black"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                />
                            </svg>
                            <span className="text-sm font-bold text-black">カートに追加</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

// ============================================================================
// V1 (Simple) Card: Used in other parts of the application
// ============================================================================

interface SimpleProductCardProps {
    productImageSrc: string;
    category: string;
    productName: string;
    price: string;
    showRatingIcon?: boolean;
    showGenderIcon?: boolean;
    showWishlistIcon?: boolean;
}

const SimpleProductCard: React.FC<SimpleProductCardProps> = ({
    productImageSrc,
    category,
    productName,
    price,
    showRatingIcon,
    showGenderIcon,
    showWishlistIcon,
}) => {
    return (
        <div className="flex w-72 flex-col border border-black bg-[#FCFCF7]">
            <div className="relative flex h-60 w-full items-center justify-center overflow-hidden bg-[#FAF7EF] p-4">
                <img src={productImageSrc} alt={productName} className="h-full w-full object-cover" />
                {showWishlistIcon && (
                    <div className="absolute top-2 right-2 rounded-full bg-white p-1 shadow-md">
                        <Heart className="h-5 w-5 text-gray-700" />
                    </div>
                )}
            </div>
            <div className="px-3 py-6">
                <div className="mb-1 flex items-end justify-between">
                    <span className="font-['Hiragino_Mincho_ProN'] text-sm font-normal text-gray-600">{category}</span>
                    {showRatingIcon && <img src="/icons/rating-container.svg" alt="Rating" className="h-4" />}
                </div>
                <div className="mb-2 flex items-center justify-between">
                    <h3 className="font-['Hiragino_Mincho_ProN'] text-lg font-normal leading-normal text-gray-800">{productName}</h3>
                    {showGenderIcon && <img src="/icons/gender.svg" alt="Gender" className="h-5" />}
                </div>
                <div className="mb-4 flex items-center justify-center">
                    <span className="font-['Hiragino_Mincho_ProN'] text-xl font-semibold leading-snug text-gray-900">{price}</span>
                </div>
                <button className="flex h-10 w-full items-center justify-center border border-[#EEDDD4] bg-[#EAB308] px-4 py-2.5 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#EAB308]">
                    <img src="/icons/icon-cart.svg" alt="Cart" className="mr-2 h-5 w-5" />
                    カートに入れる
                </button>
            </div>
        </div>
    );
};

// ============================================================================
// Adapter Component: The single default export
// ============================================================================

export default function ProductCard(props: any) {
    // If a 'product' object prop exists, render the complex card.
    if (props.product) {
        return <ComplexProductCard product={props.product} />;
    }

    // Otherwise, assume flat props and render the simple card.
    // This prevents crashes if the component is used with the old props shape.
    return <SimpleProductCard {...props} />;
}
