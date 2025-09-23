import { Link } from '@inertiajs/react';

type PriceFacet = {
  label: string;
  min: number;
  max: number | null;
  count: number;
  active?: boolean;
};

type PriceFilterProps = {
  prices: PriceFacet[];
  currentFilters: {
    q?: string;
    category?: string;
    brand?: string;
    sort?: string;
    price_min?: number;
    price_max?: number | null;
  };
};

export default function PriceFilter({ prices, currentFilters }: PriceFilterProps) {
  const buildUrl = (priceMin: number, priceMax: number | null) => {
    const params = new URLSearchParams();
    
    // Preserve existing filters
    if (currentFilters.q) params.set('q', currentFilters.q);
    if (currentFilters.category) params.set('category', currentFilters.category);
    if (currentFilters.brand) params.set('brand', currentFilters.brand);
    if (currentFilters.sort) params.set('sort', currentFilters.sort);
    
    // Set price filters
    params.set('price_min', String(priceMin));
    if (priceMax !== null) {
      params.set('price_max', String(priceMax));
    }
    
    return `?${params.toString()}`;
  };

  const clearPriceFilter = () => {
    const params = new URLSearchParams();
    
    // Preserve existing filters except price
    if (currentFilters.q) params.set('q', currentFilters.q);
    if (currentFilters.category) params.set('category', currentFilters.category);
    if (currentFilters.brand) params.set('brand', currentFilters.brand);
    if (currentFilters.sort) params.set('sort', currentFilters.sort);
    
    return `?${params.toString()}`;
  };

  // Check if any price filter is active
  const hasActivePriceFilter = prices.some(p => p.active);

  return (
    <div className="mb-6">
      <div className="mb-3 flex items-center justify-between">
        <h3 className="text-lg font-semibold">価格</h3>
        {hasActivePriceFilter && (
          <Link
            href={clearPriceFilter()}
            className="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
            preserveScroll
          >
            クリア
          </Link>
        )}
      </div>
      <div className="space-y-2">
        {prices.map((price, index) => (
          <Link
            key={index}
            href={buildUrl(price.min, price.max)}
            className={`block rounded-lg px-3 py-2 text-sm transition-colors ${
              price.active
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600'
            }`}
            preserveScroll
          >
            {price.label} ({price.count})
          </Link>
        ))}
      </div>
    </div>
  );
}