import Footer from '@/Components/Footer';
import { HomeNavigation } from '@/components/homeNavigation';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';

type Product = {
    id: number;
    slug: string;
    name: string;
    short_desc: string;
    gender: string;
    price: number;
    imageUrl: string | null;
    sizes: number[];
};

type Brand = {
    id: number;
    name: string;
    logo: string;
    founded: string;
    founder: string;
    origin: string;
    firstPerfume: string;
    description: string;
    category: string;
    products: Product[];
};

type BrandIntroductionProps = {
    brands: Brand[];
};

function yen(n: number) {
    return n.toLocaleString(undefined, { style: 'currency', currency: 'JPY' });
}

export default function BrandIntroduction({ brands }: BrandIntroductionProps) {
    const [selectedBrand, setSelectedBrand] = useState<Brand | null>(null);
    const [isModalOpen, setIsModalOpen] = useState(false);

    const openModal = (brand: Brand) => {
        setSelectedBrand(brand);
        setIsModalOpen(true);
        document.body.style.overflow = 'hidden';
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setSelectedBrand(null);
        document.body.style.overflow = 'unset';
    };

    return (
        <div className="min-h-screen bg-white">
            <Head title="ブランド紹介" />

            <HomeNavigation />

            <div className="mx-auto max-w-6xl px-4 py-12">
                {/* Header */}
                <div className="mb-12 text-center">
                    <h1 className="mb-4 text-3xl font-bold text-[#0D0D0D]">ブランド紹介</h1>
                    <p className="text-[#444444]">当真サイトで取り扱うブランドをご紹介します</p>
                </div>

                {/* Brands Grid */}
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {brands.map((brand) => (
                        <div
                            key={brand.id}
                            className="group flex flex-col border border-[#888888] bg-white p-6 transition-all duration-200 hover:border-[#0D0D0D]"
                        >
                            {/* Logo */}
                            <div className="mb-6 flex flex-col items-center">
                                <div className="mb-4 flex h-24 w-24 items-center justify-center border border-[#D8D9E0] bg-[#FCFCF7]">
                                    {brand.logo ? (
                                        <img src={brand.logo} alt={`${brand.name} logo`} className="h-16 w-16 object-contain" />
                                    ) : (
                                        <span className="text-2xl font-medium text-[#0D0D0D]">{brand.name.charAt(0)}</span>
                                    )}
                                </div>
                                <h2 className="mb-1 text-xl font-medium text-[#0D0D0D]">{brand.name}</h2>
                                <span className="text-xs text-[#444444]">{brand.category}</span>
                            </div>

                            {/* Info */}
                            <div className="mb-4 flex justify-between border-b border-[#D8D9E0] pb-4 text-xs text-[#444444]">
                                <div className="text-center">
                                    <span className="block font-medium text-[#0D0D0D]">{brand.founded}</span>
                                    <span>設立年</span>
                                </div>
                                <div className="text-center">
                                    <span className="block font-medium text-[#0D0D0D]">{brand.founder}</span>
                                    <span>創業者</span>
                                </div>
                                <div className="text-center">
                                    <span className="block font-medium text-[#0D0D0D]">{brand.origin}</span>
                                    <span>発祥地</span>
                                </div>
                            </div>

                            <p className="mb-4 text-sm leading-relaxed text-[#444444]">{brand.description}</p>

                            <p className="mb-6 text-xs text-[#444444]">
                                <span className="font-medium text-[#0D0D0D]">首款香水: </span>
                                {brand.firstPerfume}
                            </p>

                            {/* Button - pushed to bottom with mt-auto */}
                            <div className="mt-auto">
                                <button
                                    onClick={() => openModal(brand)}
                                    className="w-full border border-[#888888] py-3 text-sm text-[#0D0D0D] transition-colors hover:bg-[#0D0D0D] hover:text-white"
                                >
                                    詳細を見る ({brand.products.length}商品)
                                </button>
                            </div>
                        </div>
                    ))}
                </div>

                {/* Guide Section */}
                <div className="mt-16 border border-[#888888] bg-[#FCFCF7] p-8">
                    <h3 className="mb-6 text-center text-lg font-medium text-[#0D0D0D]">ブランド選びのヒント</h3>
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div className="text-center">
                            <div className="mx-auto mb-3 flex h-10 w-10 items-center justify-center border border-[#888888] font-medium text-[#0D0D0D]">
                                01
                            </div>
                            <h4 className="mb-2 text-sm font-medium text-[#0D0D0D]">ライフスタイル</h4>
                            <p className="text-xs text-[#444444]">普段のスタイルやシーンに合わせて</p>
                        </div>
                        <div className="text-center">
                            <div className="mx-auto mb-3 flex h-10 w-10 items-center justify-center border border-[#888888] font-medium text-[#0D0D0D]">
                                02
                            </div>
                            <h4 className="mb-2 text-sm font-medium text-[#0D0D0D]">香調</h4>
                            <p className="text-xs text-[#444444]">好みの香調から選びましょう</p>
                        </div>
                        <div className="text-center">
                            <div className="mx-auto mb-3 flex h-10 w-10 items-center justify-center border border-[#888888] font-medium text-[#0D0D0D]">
                                03
                            </div>
                            <h4 className="mb-2 text-sm font-medium text-[#0D0D0D]"> Sample</h4>
                            <p className="text-xs text-[#444444]">実際に香りがお気に入りか確認</p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Modal */}
            {isModalOpen && selectedBrand && (
                <div className="fixed inset-0 z-50 flex items-center justify-center">
                    {/* Overlay */}
                    <div className="absolute inset-0 bg-black/50" onClick={closeModal}></div>

                    {/* Modal Content */}
                    <div className="relative z-10 mx-4 max-h-[90vh] w-full max-w-5xl overflow-hidden rounded-sm bg-white">
                        {/* Close Button */}
                        <button
                            onClick={closeModal}
                            className="absolute top-4 right-4 z-20 flex h-10 w-10 items-center justify-center border border-[#888888] text-[#0D0D0D] transition-colors hover:bg-[#0D0D0D] hover:text-white"
                        >
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 4L4 12M4 4L12 12" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                            </svg>
                        </button>

                        {/* Scrollable Content */}
                        <div className="max-h-[90vh] overflow-y-auto">
                            {/* Brand Header in Modal */}
                            <div className="border-b border-[#D8D9E0] bg-[#FCFCF7] p-8">
                                <div className="flex flex-col items-center md:flex-row md:gap-8">
                                    {/* Logo */}
                                    <div className="flex h-32 w-32 flex-shrink-0 items-center justify-center border border-[#D8D9E0] bg-white">
                                        {selectedBrand.logo ? (
                                            <img src={selectedBrand.logo} alt={`${selectedBrand.name} logo`} className="h-24 w-24 object-contain" />
                                        ) : (
                                            <span className="text-4xl font-medium text-[#0D0D0D]">{selectedBrand.name.charAt(0)}</span>
                                        )}
                                    </div>

                                    {/* Brand Info */}
                                    <div className="mt-6 text-center md:mt-0 md:text-left">
                                        <h2 className="text-2xl font-medium text-[#0D0D0D]">{selectedBrand.name}</h2>
                                        <span className="text-xs text-[#444444]">{selectedBrand.category}</span>

                                        <div className="mt-4 flex flex-wrap justify-center gap-6 text-xs text-[#444444] md:justify-start">
                                            <div>
                                                <span className="block font-medium text-[#0D0D0D]">{selectedBrand.founded}</span>
                                                <span>設立年</span>
                                            </div>
                                            <div>
                                                <span className="block font-medium text-[#0D0D0D]">{selectedBrand.founder}</span>
                                                <span>創業者</span>
                                            </div>
                                            <div>
                                                <span className="block font-medium text-[#0D0D0D]">{selectedBrand.origin}</span>
                                                <span>発祥地</span>
                                            </div>
                                            <div>
                                                <span className="block font-medium text-[#0D0D0D]">{selectedBrand.firstPerfume}</span>
                                                <span>首款香水</span>
                                            </div>
                                        </div>

                                        <p className="mt-4 text-sm leading-relaxed text-[#444444]">{selectedBrand.description}</p>
                                    </div>
                                </div>
                            </div>

                            {/* Products Grid */}
                            <div className="p-8">
                                <h3 className="mb-6 text-lg font-medium text-[#0D0D0D]">
                                    {selectedBrand.name}の商品 ({selectedBrand.products.length}件)
                                </h3>

                                {selectedBrand.products.length > 0 ? (
                                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                        {selectedBrand.products.map((product) => (
                                            <Link
                                                key={product.id}
                                                href={`/products/${product.slug}`}
                                                className="group flex flex-col border border-[#D8D9E0] bg-white transition-all duration-200 hover:border-[#0D0D0D] hover:shadow-md"
                                            >
                                                {/* Product Image */}
                                                <div className="flex h-40 items-center justify-center border-b border-[#D8D9E0] bg-[#FCFCF7] p-4">
                                                    {product.imageUrl ? (
                                                        <img src={product.imageUrl} alt={product.name} className="h-full w-full object-contain" />
                                                    ) : (
                                                        <div className="flex h-full w-full items-center justify-center text-gray-400">画像なし</div>
                                                    )}
                                                </div>

                                                {/* Product Info */}
                                                <div className="flex flex-1 flex-col p-4">
                                                    <h4 className="text-sm font-medium text-[#0D0D0D]">{product.name}</h4>
                                                    <p className="mt-1 line-clamp-2 flex-1 text-xs text-[#444444]">{product.short_desc}</p>

                                                    <div className="mt-3 flex items-center justify-between">
                                                        <div className="flex items-center gap-2">
                                                            {/* Gender Icon */}
                                                            <div className="flex h-6 w-6 items-center justify-center rounded-full border border-[#D8D9E0] text-xs">
                                                                {product.gender === 'men' ? '♂' : product.gender === 'women' ? '♀' : '⚥'}
                                                            </div>
                                                            {/* Sizes */}
                                                            {product.sizes.length > 0 && (
                                                                <span className="text-xs text-[#444444]">{product.sizes[0]}ml</span>
                                                            )}
                                                        </div>
                                                        <span className="text-sm font-medium text-[#0D0D0D]">¥{yen(product.price)}</span>
                                                    </div>
                                                </div>
                                            </Link>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="py-12 text-center text-[#444444]">このブランドの商品は準備中です</div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            )}

            <Footer />
        </div>
    );
}
