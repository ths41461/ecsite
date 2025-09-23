import { Link } from '@inertiajs/react';

type BrandFacet = {
  slug: string;
  name: string;
  count: number;
  active?: boolean;
};

type BrandFilterProps = {
  brands: BrandFacet[];
  currentFilters: {
    q?: string;
    category?: string;
    brand?: string;
    sort?: string;
    price_min?: number;
    price_max?: number | null;
  };
};

export default function BrandFilter({ brands, currentFilters }: BrandFilterProps) {
  const buildUrl = (brandSlug: string) => {
    const params = new URLSearchParams();
    
    // Preserve existing filters
    if (currentFilters.q) params.set('q', currentFilters.q);
    if (currentFilters.category) params.set('category', currentFilters.category);
    if (currentFilters.sort) params.set('sort', currentFilters.sort);
    if (currentFilters.price_min != null) params.set('price_min', String(currentFilters.price_min));
    if (currentFilters.price_max != null) params.set('price_max', String(currentFilters.price_max));
    
    // Toggle brand filter
    if (!brands.find(b => b.slug === brandSlug)?.active) {
      params.set('brand', brandSlug);
    }
    
    return `?${params.toString()}`;
  };

  return (
    <div className="mb-6">
      <h3 className="mb-3 text-lg font-semibold">ブランド</h3>
      <div className="flex flex-wrap gap-2">
        {brands.map((brand) => (
          <Link
            key={brand.slug}
            href={buildUrl(brand.slug)}
            className={`rounded-full px-3 py-1 text-sm transition-colors ${
              brand.active
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600'
            }`}
            preserveScroll
          >
            {brand.name} ({brand.count})
          </Link>
        ))}
      </div>
    </div>
  );
}