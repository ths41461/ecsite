import { Link } from '@inertiajs/react';
import RatingStars from '@/components/RatingStars';
import { getFreshReviewDataForProduct } from '@/lib/review-cache';

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
    genders?: string[]; // Add gender information
    sizes?: number[];   // Add size information
};

function yen(n: number) {
    return n.toLocaleString(undefined, { style: 'currency', currency: 'JPY' });
}

export default function ProductCard({ product }: { product: ProductCardData }) {
    const hasSale = product.salePrice != null && product.salePrice < product.price;

    // Function to render star rating based on Figma design (minimalist approach)
    const renderStarRating = (rating: number) => {
        const stars = [];
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;

        for (let i = 1; i <= 5; i++) {
            if (i <= fullStars) {
                // Full star - according to Figma, color #616161
                stars.push(
                    <svg key={i} width="20" height="20" viewBox="0 0 20 20" fill="#616161" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 1.667L12.667 7.5L18.333 8.333L14 12.5L15.333 18.333L10 15.833L4.667 18.333L6 12.5L1.667 8.333L7.333 7.5L10 1.667Z" />
                    </svg>
                );
            } else if (i === fullStars + 1 && hasHalfStar) {
                // Half star - according to Figma
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
                    </div>
                );
            } else {
                // Empty star - according to Figma
                stars.push(
                    <svg key={i} width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="#616161" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 1.667L12.667 7.5L18.333 8.333L14 12.5L15.333 18.333L10 15.833L4.667 18.333L6 12.5L1.667 8.333L7.333 7.5L10 1.667Z" />
                    </svg>
                );
            }
        }

        return (
            <div className="flex items-center gap-[14px]" style={{ gap: '14px' }}>
                {stars}
            </div>
        );
    };

    return (
        <div 
            className="relative w-[288px] h-[392px] border border-[#D8D9E0] bg-white"
            style={{ borderWidth: '1.748px' }}
        >
            {/* Favorite Button */}
            <button
                type="button"
                aria-label="お気に入りに追加"
                className="absolute top-[16px] right-[16px] w-[40px] h-[40px] rounded-full bg-[#363842] flex items-center justify-center"
            >
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 18.333L7.833 16.5C3.5 13.5 1.667 11.5 1.667 8.333C1.667 5.5 3.5 3.667 5.833 3.667C7.5 3.667 9.167 4.5 10 5.5C10.833 4.5 12.5 3.667 14.167 3.667C16.5 3.667 18.333 5.5 18.333 8.333C18.333 11.5 16.5 13.5 12.167 16.5L10 18.333Z" stroke="white" strokeWidth="1.667" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
            </button>

            {/* Product Image */}
            <Link href={`/products/${product.slug}`} className="block">
                <div 
                    className="absolute left-[64px] top-[36px] w-[160px] h-[196px] overflow-hidden rounded-none" 
                    style={{ width: '160px', height: '196px' }}
                >
                    {product.imageUrl ? (
                        <img
                            src={product.imageUrl}
                            alt={product.imageAlt ?? product.name}
                            className="w-full h-full object-cover"
                            loading="lazy"
                        />
                    ) : (
                        <div className="w-full h-full bg-gray-50 flex items-center justify-center text-xs text-gray-500">
                            画像なし
                        </div>
                    )}
                </div>

                {/* Brand and Product Name */}
                <div 
                    className="absolute left-[10px] top-[252px] w-[267px] h-[72px] flex flex-col"
                    style={{ width: '267px', height: '72px' }}
                >
                    {product.brand && (
                        <div 
                            className="text-[12px] leading-[20px] font-normal text-[#81859C] font-sans mb-1"
                            style={{ fontFamily: 'Noto Sans JP' }}
                        >
                            {product.brand}
                        </div>
                    )}
                    <h3 
                        className="text-[18px] leading-[32px] font-semibold text-[#363842] font-serif mb-1"
                        style={{ fontFamily: 'Hiragino Mincho ProN', lineHeight: '1.7777777777777777em' }}
                    >
                        {product.name}
                    </h3>
                    
                    {/* Gender/Unisex Icons - More visible minimalist style */}
                    <div className="flex gap-1 mt-1">
                        {product.genders && product.genders.map((gender) => (
                            <div 
                                key={gender} 
                                className="w-[20px] h-[20px] flex items-center justify-center rounded-full border border-[#D8D9E0] text-[#363842] text-[11px]"
                                title={gender === 'men' ? 'メンズ' : gender === 'women' ? 'レディース' : 'ユニセックス'}
                            >
                                {gender === 'men' ? '♂' : gender === 'women' ? '♀' : '⚥'}
                            </div>
                        ))}
                    </div>
                </div>

                {/* Pricing */}
                <div className="absolute left-[10px] top-[341px] flex items-center gap-[2px]">
                    <span 
                        className={`text-[24px] font-bold text-[#363842] font-serif`}
                        style={{ fontFamily: 'Playfair Display', lineHeight: '1.8333333333333333em', letterSpacing: '-2%' }}
                    >
                        ￥{yen(hasSale ? product.salePrice! : product.price)}
                    </span>
                    {hasSale && (
                        <span 
                            className="text-[14px] font-normal text-[#363842] font-serif ml-1"
                            style={{ fontFamily: 'Playfair Display', lineHeight: '3.142857142857143em' }}
                        >
                            ({product.sizes?.[0]}ml)
                        </span>
                    )}
                </div>

                {/* Rating */}
                {(() => {
                    // Check if we have fresh review data for this product
                    const freshData = getFreshReviewDataForProduct(product.id);
                    const displayRating = freshData?.averageRating ?? product.averageRating;
                    const displayReviewCount = freshData?.reviewCount ?? product.reviewCount;

                    return (displayRating !== undefined && displayRating > 0) && (
                        <div className="absolute left-[10px] top-[368px] flex items-center">
                            {renderStarRating(displayRating)}
                            {displayReviewCount !== undefined && displayReviewCount > 0 && (
                                <span className="text-xs text-gray-500 ml-2">
                                    ({displayReviewCount})
                                </span>
                            )}
                        </div>
                    );
                })()}

                {/* Cart Button */}
                <button
                    type="button"
                    aria-label="カートに追加"
                    className="absolute right-[11px] top-[332px] w-[85px] h-[52px] flex items-center justify-center bg-[#EAB308]"
                >
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 3H5L5.4 5M7 13H17L21 5H5.4M7 13L5.4 5M7 13L4.707 15.293C4.077 15.923 4.523 17 5.414 17H17M17 17C15.895 17 15 17.895 15 19C15 20.105 15.895 21 17 21C18.105 21 19 20.105 19 19C19 17.895 18.105 17 17 17ZM9 19C9 20.105 8.105 21 7 21C5.895 21 5 20.105 5 19C5 17.895 5.895 17 7 17C8.105 17 9 17.895 9 19Z" stroke="black" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                    </svg>
                </button>
            </Link>
        </div>
    );
}