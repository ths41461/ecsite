import { ChevronLeft, ChevronRight, Heart } from 'lucide-react';
import React from 'react';
import ProductCard from './ProductCard';
import ImprovedCarousel from './ImprovedCarousel';

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
        <section className="w-full border-t border-b border-[#888888] bg-[#FCFCF7] py-6">
            <div className="container mx-auto flex flex-col items-center px-4">
                {/* Section Header */}
                <div className="mb-8 text-center">
                    <h2 className="mb-2 font-['Hiragino_Mincho_ProN'] text-3xl font-semibold text-[#444444]">人気ランキング</h2>
                    <p className="text-xl text-[#444444]">今月、1,200人以上の学生に選ばれた香り！</p>
                </div>

                {/* Main Ranked Product */}
                <div className="mb-12 flex w-full max-w-5xl flex-col items-center p-8 md:flex-row">
                    <div className="relative mb-6 w-full md:mb-0 md:w-1/2">
                        <img
                            src="/perfume-images/perfume-1.png"
                            alt="ルイ・ヴィトン スパークル"
                            className="h-auto w-full border border-gray-300 object-cover p-5"
                        />
                        <div className="absolute top-2 right-2 rounded-full bg-white p-1 shadow-md">
                            <Heart className="h-5 w-5 text-gray-700" />
                        </div>
                    </div>
                    <div className="flex w-full flex-col items-start md:w-1/2 md:pl-12">
                        <span className="mb-2 inline-block bg-[#616161] px-3 py-1 text-base font-semibold text-white shadow-xs">#1 ランキング</span>
                        <h3 className="mb-2 font-['Hiragino_Mincho_ProN'] text-3xl font-semibold text-black">ルイ・ヴィトン スパークル</h3>
                        <p className="mb-4 text-lg text-black">¥18,000</p>
                        <div className="flex flex-col gap-2">
                            <button className="flex h-10 items-center justify-center border border-[#EEDDD4] bg-[#EAB308] px-4 py-2.5 text-base font-semibold text-white shadow-sm">
                                カートに入れる
                            </button>
                            <button className="flex h-10 items-center justify-center border border-[#D0D5DD] bg-[#888888] px-4 py-2.5 text-sm font-medium text-white shadow-sm">
                                製品を見る
                            </button>
                        </div>
                    </div>
                </div>

                {/* Product Cards Carousel using ImprovedCarousel */}
                <ImprovedCarousel itemsToShow={4} slideOffset={1}>
                    {rankedProducts.map((product, index) => (
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
                </ImprovedCarousel>

                {/* See More Products Button */}
                <div className="mt-12 text-center">
                    <button className="bg-[#444444] px-6 py-3 text-base font-medium text-white shadow-md transition-colors hover:bg-gray-700">
                        商品もっと見る
                    </button>
                </div>
            </div>
        </section>
    );
};

export default RankingSection;