import { Heart } from 'lucide-react';
import React from 'react';

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
        <div className="flex w-72 flex-col border border-black bg-[#FCFCF7]">
            {/* Product Image */}
            <div className="relative flex h-60 w-full items-center justify-center overflow-hidden bg-[#FAF7EF] p-4">
                <img src={productImageSrc} alt={productName} className="h-full w-full object-cover" />
                {showWishlistIcon && (
                    <div className="absolute top-2 right-2 rounded-full bg-white p-1 shadow-md">
                        <Heart className="h-5 w-5 text-gray-700" />
                    </div>
                )}
            </div>

            {/* Product Info */}
            <div className="px-3 py-6">
                {/* Category and Rating */}
                <div className="mb-1 flex items-end justify-between">
                    <span className="font-['Hiragino_Mincho_ProN'] text-sm font-normal text-gray-600">{category}</span>
                    {showRatingIcon && <img src="/icons/rating-container.svg" alt="Rating" className="h-4" />}
                </div>

                {/* Product Name and Gender */}
                <div className="mb-2 flex items-center justify-between">
                    <h3 className="font-['Hiragino_Mincho_ProN'] text-lg leading-normal font-normal text-gray-800">{productName}</h3>
                    {showGenderIcon && <img src="/icons/gender.svg" alt="Gender" className="h-5" />}
                </div>

                {/* Product Price */}
                <div className="mb-4 flex items-center justify-center">
                    <span className="font-['Hiragino_Mincho_ProN'] text-xl leading-snug font-semibold text-gray-900">{price}</span>
                </div>

                {/* Add to Cart Button */}
                <button className="flex h-10 w-full items-center justify-center border border-[#EEDDD4] bg-[#EAB308] px-4 py-2.5 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#EAB308]">
                    <img src="/icons/icon-cart.svg" alt="Cart" className="mr-2 h-5 w-5" />
                    カートに入れる
                </button>
            </div>
        </div>
    );
};

export default ProductCard;
