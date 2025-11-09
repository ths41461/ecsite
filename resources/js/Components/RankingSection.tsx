import { Heart } from 'lucide-react';
import React from 'react';
import ImprovedCarousel from './ImprovedCarousel';
import ProductCard from './ProductCard';

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

interface RankingSectionProps {
    products?: ProductData[];
}

const RankingSection: React.FC<RankingSectionProps> = ({ products = [] }) => {
    // Sort products by rank if available, otherwise use order provided
    const sortedProducts = [...products].sort((a, b) => (a.rank || 0) - (b.rank || 0));
    
    const rankedProducts = sortedProducts.slice(1, 8); // Skip the first one since it's the main product (#1)

    // Get the main product for #1 rank (first product in the list)
    const mainProduct = sortedProducts[0];

    return (
        <section className="my-8 w-full border-t border-b border-[#888888] bg-[#FCFCF7] py-6 sm:my-12 sm:py-8">
            <div className="container mx-auto flex flex-col items-center px-4">
                {/* Section Header */}
                <div className="mb-6 text-center sm:mb-10">
                    <h2 className="mb-3 font-['Hiragino_Mincho_ProN'] text-2xl font-semibold text-gray-800 sm:text-3xl">人気ランキング</h2>
                    <p className="text-base text-gray-600 sm:text-lg">今月、1,200人以上の学生に選ばれた香り！</p>
                </div>

                {/* Main Ranked Product */}
                {mainProduct && (
                    <div className="mb-10 flex w-full max-w-5xl flex-col items-center sm:mb-16 md:flex-row">
                        <div className="relative mb-4 w-full border border-gray-300 md:mb-0 md:w-1/2">
                            <img src={mainProduct.productImageSrc} alt={mainProduct.productName} className="h-auto w-full object-cover" />
                            <div className="absolute top-2 right-2 rounded-full bg-white/80 p-1 shadow-sm">
                                <Heart className="h-3 w-3 text-gray-600" />
                            </div>
                        </div>
                        <div className="flex w-full flex-col items-start md:w-1/2 md:pl-16">
                            <span className="mb-2 inline-block bg-gray-700 px-2 py-1 text-xs font-semibold text-white sm:mb-3 sm:px-3 sm:py-1.5 sm:text-sm">
                                #{mainProduct.rank || '1'} ランキング
                            </span>
                            <h3 className="mb-2 font-['Hiragino_Mincho_ProN'] text-xl font-semibold text-gray-900 sm:mb-3 sm:text-2xl">
                                {mainProduct.productName}
                            </h3>
                            <p className="mb-4 text-lg text-gray-900 sm:mb-6 sm:text-xl">{mainProduct.price}</p>
                            <div className="flex flex-row gap-3 md:flex-col md:gap-3">
                                <button 
                                    onClick={() => {
                                        // Add to cart functionality similar to other cards
                                        alert(`${mainProduct.productName}をカートに追加しました`);
                                    }}
                                    className="flex-1 flex h-11 items-center justify-center bg-[#EAB308] px-4 py-3 text-sm md:text-base font-semibold text-white min-w-[130px]"
                                >
                                    カートに入れる
                                </button>
                                <a href={`/products/${mainProduct.slug}`} className="flex-1 flex h-11 items-center justify-center bg-gray-500 px-4 py-3 text-sm md:text-base font-medium text-white min-w-[130px]">
                                    製品を見る
                                </a>
                            </div>
                        </div>
                    </div>
                )}

                {/* Product Cards - Responsive Carousel: 1 item on mobile, 4 on desktop */}
                {rankedProducts.length > 0 ? (
                    <div className="relative w-full">
                        {/* Mobile Carousel - 1 item */}
                        <div className="-mx-4 block px-4 sm:hidden">
                            <ImprovedCarousel itemsToShow={1} slideOffset={1}>
                                {rankedProducts.map((product, index) => (
                                    <div key={product.id} className="relative">
                                        <div className="absolute top-1.5 left-1.5 z-10 flex h-6 w-6 items-center justify-center rounded-full bg-black/80 text-xs font-bold text-white">
                                            #{product.rank || index + 2}
                                        </div>
                                        <ProductCard
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
                                            disableCartDrawer={true}
                                        />
                                    </div>
                                ))}
                            </ImprovedCarousel>
                        </div>

                        {/* Desktop Carousel - 4 items */}
                        <div className="hidden sm:block">
                            <ImprovedCarousel itemsToShow={4} slideOffset={1}>
                                {rankedProducts.map((product, index) => (
                                    <div key={product.id} className="relative">
                                        <div className="absolute top-1.5 left-1.5 z-10 flex h-6 w-6 items-center justify-center rounded-full bg-black/80 text-xs font-bold text-white">
                                            #{product.rank || index + 2}
                                        </div>
                                        <ProductCard
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
                                            disableCartDrawer={true}
                                        />
                                    </div>
                                ))}
                            </ImprovedCarousel>
                        </div>
                    </div>
                ) : (
                    <div className="text-center py-8 text-gray-500">
                        現在ランキング商品はございません。
                    </div>
                )}

                {/* See More Products Button */}
                <div className="mt-10 text-center sm:mt-16">
                    <a href="/products" className="inline-block bg-gray-700 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-gray-800 sm:px-6 sm:py-3.5 sm:text-base">
                        商品もっと見る
                    </a>
                </div>
            </div>
        </section>
    );
};

export default RankingSection;
