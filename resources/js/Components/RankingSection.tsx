import { Heart } from 'lucide-react';
import React from 'react';
import ImprovedCarousel from './ImprovedCarousel';
import ProductCard from './ProductCard';

const RankingSection: React.FC = () => {
    const rankedProducts = [
        {
            productImageSrc: '/perfume-images/perfume-2.png',
            category: 'ブランド名',
            productName: '商品名 2',
            price: '￥29,00',
            showRatingIcon: true,
            showGenderIcon: true,
            showWishlistIcon: true,
        },
        {
            productImageSrc: '/perfume-images/perfume-3.png',
            category: 'ブランド名',
            productName: '商品名 3',
            price: '￥35,00',
            showRatingIcon: true,
            showGenderIcon: false,
            showWishlistIcon: true,
        },
        {
            productImageSrc: '/perfume-images/perfume-4.png',
            category: 'ブランド名',
            productName: '商品名 4',
            price: '￥25,00',
            showRatingIcon: false,
            showGenderIcon: true,
            showWishlistIcon: false,
        },
        {
            productImageSrc: '/perfume-images/perfume-5.png',
            category: 'ブランド名',
            productName: '商品名 5',
            price: '￥40,00',
            showRatingIcon: true,
            showGenderIcon: true,
            showWishlistIcon: true,
        },
        {
            productImageSrc: '/perfume-images/perfume-6.png',
            category: 'ブランド名',
            productName: '商品名 6',
            price: '￥32,00',
            showRatingIcon: true,
            showGenderIcon: false,
            showWishlistIcon: true,
        },
        {
            productImageSrc: '/perfume-images/perfume-7.png',
            category: 'ブランド名',
            productName: '商品名 7',
            price: '￥28,00',
            showRatingIcon: false,
            showGenderIcon: true,
            showWishlistIcon: false,
        },
        {
            productImageSrc: '/perfume-images/perfume-8.png',
            category: 'ブランド名',
            productName: '商品名 8',
            price: '￥30,00',
            showRatingIcon: true,
            showGenderIcon: true,
            showWishlistIcon: true,
        },
        {
            productImageSrc: '/perfume-images/perfume-9.png',
            category: 'ブランド名',
            productName: '商品名 9',
            price: '￥33,00',
            showRatingIcon: false,
            showGenderIcon: false,
            showWishlistIcon: true,
        },
    ];

    return (
        <section className="my-16 w-full bg-[#FCFCF7] py-8">
            <div className="flex flex-col items-center">
                {/* Section Header */}
                <div className="mb-12 text-center">
                    <h2 className="mb-3 font-['Hiragino_Mincho_ProN'] text-3xl font-semibold text-gray-800">人気ランキング</h2>
                    <p className="text-lg text-gray-600">今月、1,200人以上の学生に選ばれた香り！</p>
                </div>

                {/* Main Ranked Product */}
                <div className="mb-16 flex w-full max-w-5xl flex-col items-center md:flex-row">
                    <div className="relative mb-8 w-full md:mb-0 md:w-1/2">
                        <img
                            src="/perfume-images/perfume-1.png"
                            alt="ルイ・ヴィトン スパークル"
                            className="h-auto w-full object-cover"
                        />
                        <div className="absolute top-3 right-3 rounded-full bg-white/80 p-1.5 shadow-sm">
                            <Heart className="h-4 w-4 text-gray-600" />
                        </div>
                    </div>
                    <div className="flex w-full flex-col items-start md:w-1/2 md:pl-16">
                        <span className="mb-3 inline-block bg-gray-700 px-3 py-1.5 text-sm font-semibold text-white">#1 ランキング</span>
                        <h3 className="mb-3 font-['Hiragino_Mincho_ProN'] text-2xl font-semibold text-gray-900">ルイ・ヴィトン スパークル</h3>
                        <p className="mb-6 text-xl text-gray-900">¥18,000</p>
                        <div className="flex flex-col gap-3">
                            <button className="flex h-11 items-center justify-center bg-[#EAB308] px-5 py-3 text-base font-semibold text-white">
                                カートに入れる
                            </button>
                            <button className="flex h-11 items-center justify-center bg-gray-500 px-5 py-3 text-base font-medium text-white">
                                製品を見る
                            </button>
                        </div>
                    </div>
                </div>

                {/* Product Cards Carousel using ImprovedCarousel */}
                <ImprovedCarousel itemsToShow={4} slideOffset={1}>
                    {rankedProducts.map((product, index) => (
                        <div key={index} className="relative">
                            <div className="absolute top-1.5 left-1.5 z-10 flex h-6 w-6 items-center justify-center rounded-full bg-black/80 text-xs font-bold text-white">
                                #{index + 2}
                            </div>
                            <ProductCard
                                productImageSrc={product.productImageSrc}
                                category={product.category}
                                productName={product.productName}
                                price={product.price}
                                showRatingIcon={product.showRatingIcon}
                                showGenderIcon={product.showGenderIcon}
                                showWishlistIcon={product.showWishlistIcon}
                            />
                        </div>
                    ))}
                </ImprovedCarousel>

                {/* See More Products Button */}
                <div className="mt-16 text-center">
                    <button className="bg-gray-700 px-6 py-3.5 text-base font-medium text-white transition-colors hover:bg-gray-800">
                        商品もっと見る
                    </button>
                </div>
            </div>
        </section>
    );
};

export default RankingSection;
