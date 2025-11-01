import React from 'react';
import { Heart } from 'lucide-react';

interface ProductCardProps {
    productImageSrc: string;
    category: string;
    productName: string;
    price: string;
    showRatingIcon?: boolean;
    showGenderIcon?: boolean;
    showWishlistIcon?: boolean;
}

const ProductCard: React.FC<ProductCardProps> = ({
    productImageSrc,
    category,
    productName,
    price,
    showRatingIcon,
    showGenderIcon,
    showWishlistIcon,
}) => {
    return (
        <div className="flex flex-col w-[310px] bg-[#FCFCF7] border border-black">
            {/* Product Image */}
            <div className="relative w-full h-[263px] bg-[#FAF7EF] flex items-center justify-center overflow-hidden">
                <img src={productImageSrc} alt={productName} className="object-cover w-full h-full" />
                {showWishlistIcon && (
                    <div className="absolute top-2 right-2 bg-white rounded-full p-1 shadow-md">
                        <Heart className="h-5 w-5 text-gray-700" />
                    </div>
                )}
            </div>

            {/* Product Info */}
            <div className="px-2 py-6">
                {/* Category and Rating */}
                <div className="flex justify-between items-end mb-1">
                    <span className="text-[#616161] text-sm font-normal">
                        {category}
                    </span>
                    {showRatingIcon && (
                        <img src="/icons/rating-container.svg" alt="Rating" className="h-4" />
                    )}
                </div>

                {/* Product Name and Gender */}
                <div className="flex justify-between items-center mb-2">
                    <h3 className="text-[#444444] text-xl font-normal leading-[1.5em]">
                        {productName}
                    </h3>
                    {showGenderIcon && (
                        <img src="/icons/gender.svg" alt="Gender" className="h-5" />
                    )}
                </div>

                {/* Product Price */}
                <div className="flex items-center justify-center mb-4">
                    <span className="text-black text-2xl font-normal leading-[1.3333333333333333em]">
                        {price}
                    </span>
                </div>

                {/* Add to Cart Button */}
                <button className="flex items-center justify-center w-full h-10 bg-[#EAB308] text-[#FCFCFC] text-sm font-medium px-4 py-2.5 shadow-sm border border-[#EEDDD4]">
                    <img src="/icons/icon-cart.svg" alt="Cart" className="w-5 h-5 mr-2" />
                    カートに入れる
                </button>
            </div>
        </div>
    );
};

export default ProductCard;
