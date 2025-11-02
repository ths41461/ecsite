import React from 'react';
import ProductCard from './ProductCard';

interface ProductData {
    productImageSrc: string;
    category: string;
    productName: string;
    price: string;
    showRatingIcon?: boolean;
    showGenderIcon?: boolean;
    showWishlistIcon?: boolean;
}

interface RecommendedSectionProps {
    products?: ProductData[]; // Make products prop optional
    className?: string;
}

const defaultDummyProducts: ProductData[] = [
    {
        productImageSrc: '/perfume-images/perfume-1.png',
        category: 'ブランド名',
        productName: '商品名 1',
        price: '￥29,00',
        showRatingIcon: true,
        showGenderIcon: true,
        showWishlistIcon: true,
    },
    {
        productImageSrc: '/perfume-images/perfume-2.png',
        category: 'ブランド名',
        productName: '商品名 2',
        price: '￥35,00',
        showRatingIcon: true,
        showGenderIcon: false,
        showWishlistIcon: true,
    },
    {
        productImageSrc: '/perfume-images/perfume-3.png',
        category: 'ブランド名',
        productName: '商品名 3',
        price: '￥25,00',
        showRatingIcon: false,
        showGenderIcon: true,
        showWishlistIcon: false,
    },
    {
        productImageSrc: '/perfume-images/perfume-4.png',
        category: 'ブランド名',
        productName: '商品名 4',
        price: '￥40,00',
        showRatingIcon: true,
        showGenderIcon: true,
        showWishlistIcon: true,
    },
    {
        productImageSrc: '/perfume-images/perfume-5.png',
        category: 'ブランド名',
        productName: '商品名 5',
        price: '￥30,00',
        showRatingIcon: true,
        showGenderIcon: false,
        showWishlistIcon: true,
    },
    {
        productImageSrc: '/perfume-images/perfume-6.png',
        category: 'ブランド名',
        productName: '商品名 6',
        price: '￥32,00',
        showRatingIcon: false,
        showGenderIcon: true,
        showWishlistIcon: true,
    },
    {
        productImageSrc: '/perfume-images/perfume-7.png',
        category: 'ブランド名',
        productName: '商品名 7',
        price: '￥28,00',
        showRatingIcon: true,
        showGenderIcon: true,
        showWishlistIcon: false,
    },
    {
        productImageSrc: '/perfume-images/hero-background.png',
        category: 'ブランド名',
        productName: '商品名 8',
        price: '￥38,00',
        showRatingIcon: true,
        showGenderIcon: false,
        showWishlistIcon: true,
    },
];

const RecommendedSection: React.FC<RecommendedSectionProps> = ({ products, className }) => {
    const productsToDisplay = products && products.length > 0 ? products : defaultDummyProducts;
    const finalProducts = productsToDisplay.slice(0, 8); // Ensure max 8 products

    // If fewer than 8 products are provided, fill with dummy data
    while (finalProducts.length < 8) {
        finalProducts.push(defaultDummyProducts[finalProducts.length]);
    }

    return (
        <section className={`w-full border-t border-b border-[#888888] bg-[#FCFCF7] py-4 ${className}`}>
            <div className="container mx-auto px-4">
                {/* Section Title */}
                <div className="mb-8 text-center">
                    <h2 className="mt-5 mb-12 text-3xl leading-tight font-semibold text-[#444444]">おすすめ商品</h2>
                </div>

                {/* Product Cards Grid */}
                <div className="grid grid-cols-4 gap-8">
                    {finalProducts.map((product, index) => (
                        <ProductCard
                            key={index}
                            productImageSrc={product.productImageSrc}
                            category={product.category}
                            productName={product.productName}
                            price={product.price}
                            showRatingIcon={product.showRatingIcon}
                            showGenderIcon={product.showGenderIcon}
                            showWishlistIcon={product.showWishlistIcon}
                        />
                    ))}
                </div>

                {/* See More Products Button */}
                <div className="mt-12 text-center">
                    <button className="bg-[#444444] px-4 py-2.5 text-lg font-medium text-white shadow-md transition-colors hover:bg-gray-700">
                        商品もっと見る
                    </button>
                </div>
            </div>
        </section>
    );
};

export default RecommendedSection;
