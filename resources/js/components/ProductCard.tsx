import { Link } from '@inertiajs/react';
import RatingStars from '@/components/RatingStars';

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

    return (
        <div className="group relative rounded-2xl border bg-white p-3 transition hover:shadow-sm dark:bg-neutral-900">
            <Link href={`/products/${product.slug}`} className="block">
                <div className="aspect-square w-full overflow-hidden rounded-xl bg-gray-50 dark:bg-neutral-800">
                    {product.imageUrl ? (
                        <img
                            src={product.imageUrl}
                            alt={product.imageAlt ?? product.name}
                            className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                            loading="lazy"
                        />
                    ) : (
                        <div className="grid h-full w-full place-items-center text-sm text-gray-400">画像なし</div>
                    )}
                </div>

                <div className="mt-3 space-y-1">
                    {product.brand && <div className="line-clamp-1 text-xs text-gray-500 dark:text-gray-400">{product.brand}</div>}
                    <h3 className="line-clamp-2 text-sm font-medium text-gray-900 dark:text-gray-100">{product.name}</h3>

                    {/* Gender and Size Icons */}
                    {(product.genders || product.sizes) && (
                        <div className="flex flex-wrap gap-1">
                            {product.genders && product.genders.map((gender) => (
                                <span 
                                    key={gender} 
                                    className="inline-flex items-center justify-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                                    title={gender === 'men' ? 'メンズ' : gender === 'women' ? 'レディース' : 'ユニセックス'}
                                >
                                    {gender === 'men' ? '♂' : gender === 'women' ? '♀' : '⚥'}
                                </span>
                            ))}
                            {product.sizes && product.sizes.map((size) => (
                                <span 
                                    key={size} 
                                    className="inline-flex items-center justify-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200"
                                    title={`${size}ml`}
                                >
                                    {size}ml
                                </span>
                            ))}
                        </div>
                    )}

                    <div className="flex items-baseline gap-2">
                        <span className={`text-sm font-semibold ${hasSale ? 'text-rose-600' : 'text-gray-900 dark:text-gray-100'}`}>
                            {yen(hasSale ? product.salePrice! : product.price)}
                        </span>
                        {hasSale && <span className="text-xs text-gray-400 line-through dark:text-gray-500">{yen(product.price)}</span>}
                    </div>

                    {/* Ratings */}
                    {(product.averageRating !== undefined && product.averageRating > 0) && (
                        <div className="flex items-center pt-1">
                            <RatingStars rating={product.averageRating} size="sm" />
                            {product.reviewCount !== undefined && product.reviewCount > 0 && (
                                <span className="ml-1 text-xs text-gray-500">
                                    ({product.reviewCount})
                                </span>
                            )}
                        </div>
                    )}
                </div>
            </Link>

            {/* wishlist placeholder */}
            <button
                type="button"
                aria-label="お気に入りに追加"
                className="absolute top-4 right-4 rounded-full bg-white/90 px-2 py-1 text-xs shadow-sm hover:bg-white"
            >
                ♡
            </button>
        </div>
    );
}