import { Link } from '@inertiajs/react';

type SizeFilterProps = {
  currentFilters: {
    q?: string;
    category?: string;
    brand?: string;
    sort?: string;
    price_min?: number;
    price_max?: number | null;
    size?: number;
  };
};

export default function SizeFilter({ currentFilters }: SizeFilterProps) {
  // Common perfume sizes in ml
  const sizes = [10, 30, 50, 75, 100, 125, 150, 200];

  const buildUrl = (size: number) => {
    const params = new URLSearchParams();
    
    // Preserve existing filters
    if (currentFilters.q) params.set('q', currentFilters.q);
    if (currentFilters.category) params.set('category', currentFilters.category);
    if (currentFilters.brand) params.set('brand', currentFilters.brand);
    if (currentFilters.sort) params.set('sort', currentFilters.sort);
    if (currentFilters.price_min != null) params.set('price_min', String(currentFilters.price_min));
    if (currentFilters.price_max != null) params.set('price_max', String(currentFilters.price_max));
    
    // Toggle size filter
    if (currentFilters.size !== size) {
      params.set('size', String(size));
    }
    
    return `?${params.toString()}`;
  };

  const clearSizeFilter = () => {
    const params = new URLSearchParams();
    
    // Preserve existing filters except size
    if (currentFilters.q) params.set('q', currentFilters.q);
    if (currentFilters.category) params.set('category', currentFilters.category);
    if (currentFilters.brand) params.set('brand', currentFilters.brand);
    if (currentFilters.sort) params.set('sort', currentFilters.sort);
    if (currentFilters.price_min != null) params.set('price_min', String(currentFilters.price_min));
    if (currentFilters.price_max != null) params.set('price_max', String(currentFilters.price_max));
    
    return `?${params.toString()}`;
  };

  // Check if any size filter is active
  const hasActiveSizeFilter = currentFilters.size !== undefined;

  return (
    <div className="mb-6">
      <div className="mb-3 flex items-center justify-between">
        <h3 className="text-lg font-semibold">容量 (ml)</h3>
        {hasActiveSizeFilter && (
          <Link
            href={clearSizeFilter()}
            className="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
            preserveScroll
          >
            クリア
          </Link>
        )}
      </div>
      <div className="flex flex-wrap gap-2">
        {sizes.map((size) => (
          <Link
            key={size}
            href={buildUrl(size)}
            className={`rounded-full px-3 py-1 text-sm transition-colors ${
              currentFilters.size === size
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600'
            }`}
            preserveScroll
          >
            {size}ml
          </Link>
        ))}
      </div>
    </div>
  );
}