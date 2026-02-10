import React from 'react';
import ImprovedCarousel from './ImprovedCarousel';
import ProductCard from './ProductCard';

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

interface ProductData {
    id: number;
    productImageSrc: string;
    category: string;
    productName: string;
    price: string;
    slug: string;
    rank?: number;
    score?: number;
    variants?: Variant[]; // Add variants property
    genders?: string[];
    sizes?: number[];
    showRatingIcon?: boolean;
    showWishlistIcon?: boolean;
    brand?: string | null;
    averageRating?: number;
    reviewCount?: number;
    salePrice?: number | null;
    priceValue?: number;
}

interface RecommendedSectionProps {
    products?: ProductData[]; // Make products prop optional
    className?: string;
}

const RecommendedSection: React.FC<RecommendedSectionProps> = ({ products, className }) => {
    const finalProducts = products && products.length > 0 ? products.slice(0, 8) : [];

    return (
        <section className={`w-full border-t border-b border-[#888888] bg-[#FCFCF7] py-4 ${className}`}>
            <div className="container mx-auto px-4">
                {/* Section Title */}
                <div className="mb-6 text-center">
                    <h2 className="mb-6 font-['Hiragino_Mincho_ProN'] text-2xl leading-tight font-semibold text-gray-800">おすすめ商品</h2>
                </div>

                {/* Product Cards - Carousel for Mobile, Grid for Desktop */}
                {finalProducts.length > 0 ? (
                    <div className="w-full">
                        <div className="block sm:hidden">
                            <ImprovedCarousel itemsToShow={1} slideOffset={1}>
                                {finalProducts.map((product) => (
                                    <ProductCard
                                        key={product.id}
                                        productImageSrc={product.productImageSrc}
                                        category={product.category}
                                        productName={product.productName}
                                        price={product.price}
                                        slug={product.slug}
                                        id={product.id}
                                        genders={product.genders}
                                        sizes={product.sizes}
                                        showRatingIcon={product.showRatingIcon}
                                        showWishlistIcon={product.showWishlistIcon}
                                        averageRating={product.averageRating}
                                        reviewCount={product.reviewCount}
                                    />
                                ))}
                            </ImprovedCarousel>
                        </div>
                        <div className="hidden grid-cols-2 justify-items-center gap-4 sm:grid sm:gap-6 md:grid-cols-3 lg:grid-cols-4">
                            {finalProducts.map((product) => (
                                <ProductCard
                                    key={product.id}
                                    productImageSrc={product.productImageSrc}
                                    category={product.category}
                                    productName={product.productName}
                                    price={product.price}
                                    slug={product.slug}
                                    id={product.id}
                                    variants={product.variants} // Add variants prop
                                    genders={product.genders}
                                    sizes={product.sizes}
                                    showRatingIcon={product.showRatingIcon}
                                    showWishlistIcon={product.showWishlistIcon}
                                    averageRating={product.averageRating}
                                    reviewCount={product.reviewCount}
                                />
                            ))}
                        </div>
                    </div>
                ) : (
                    <div className="py-8 text-center text-gray-600">現在おすすめ商品はございません。</div>
                )}

                {/* See More Products Button */}
                <div className="mt-6 text-center">
                    <a
                        href="/products"
                        className="inline-block min-h-[44px] bg-gray-800 px-6 py-3 text-base font-medium text-white shadow-md transition-colors hover:bg-gray-900 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:outline-none"
                    >
                        商品もっと見る
                    </a>
                </div>
            </div>
        </section>
    );
};

export default RecommendedSection;
