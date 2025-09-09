import * as React from "react";
import { Link } from "@inertiajs/react";

export type ProductCardData = {
  id: number;
  slug: string;
  name: string;
  brand?: string | null;
  price: number;           // in JPY
  salePrice?: number | null; // in JPY
  imageUrl?: string | null;
  imageAlt?: string | null;
};

function yen(n: number) {
  return n.toLocaleString(undefined, { style: "currency", currency: "JPY" });
}

export default function ProductCard({ product }: { product: ProductCardData }) {
  const hasSale = product.salePrice != null && product.salePrice < product.price;

  return (
    <div className="relative group rounded-2xl border p-3 transition hover:shadow-sm bg-white">
      <Link href={`/products/${product.slug}`} className="block">
        <div className="aspect-square w-full overflow-hidden rounded-xl bg-gray-50">
          {product.imageUrl ? (
            <img
              src={product.imageUrl}
              alt={product.imageAlt ?? product.name}
              className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
              loading="lazy"
            />
          ) : (
            <div className="grid h-full w-full place-items-center text-sm text-gray-400">
              No image
            </div>
          )}
        </div>

        <div className="mt-3 space-y-1">
          {product.brand && (
            <div className="text-xs text-gray-500 line-clamp-1">{product.brand}</div>
          )}
          <h3 className="text-sm font-medium line-clamp-2">{product.name}</h3>

          <div className="flex items-baseline gap-2">
            <span className={`text-sm font-semibold ${hasSale ? "text-rose-600" : ""}`}>
              {yen(hasSale ? product.salePrice! : product.price)}
            </span>
            {hasSale && (
              <span className="text-xs text-gray-400 line-through">{yen(product.price)}</span>
            )}
          </div>
        </div>
      </Link>

      {/* wishlist placeholder */}
      <button
        type="button"
        aria-label="Add to wishlist"
        className="absolute right-4 top-4 rounded-full bg-white/90 px-2 py-1 text-xs shadow-sm hover:bg-white"
      >
        ♡
      </button>
    </div>
  );
}
