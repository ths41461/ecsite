import React from 'react';
import ProductCard from './ProductCard';
import ImprovedCarousel from './ImprovedCarousel';

interface ProductData {
    id: number;
    productImageSrc: string;
    category: string;
    productName: string;
    price: string;
    slug: string;
    rank?: number;
    score?: number;
    genders?: string[];
    sizes?: number[];
    showRatingIcon?: boolean;
    showWishlistIcon?: boolean;
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
                    <h2 className="font-['Hiragino_Mincho_ProN'] mb-6 text-2xl leading-tight font-semibold text-gray-800">おすすめ商品</h2>
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
                                    />
                                ))}
                            </ImprovedCarousel>
                        </div>
                        <div className="hidden sm:grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6 justify-items-center">
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
                                />
                            ))}
                        </div>
                    </div>
                ) : (
                    <div className="text-center py-8 text-gray-500">
                        現在おすすめ商品はございません。
                    </div>
                )}

                {/* See More Products Button */}
                <div className="mt-6 text-center">
                    <a href="/products" className="inline-block bg-gray-800 px-6 py-3 text-base font-medium text-white shadow-md transition-colors hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 min-h-[44px]">
                        商品もっと見る
                    </a>
                </div>
            </div>
        </section>
    );
};

export default RecommendedSection;
