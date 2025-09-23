import { Link } from '@inertiajs/react';

type CategoryFacet = {
  slug: string;
  name: string;
  count: number;
  active?: boolean;
};

type CategoryFilterProps = {
  categories: CategoryFacet[];
  currentFilters: {
    q?: string;
    category?: string;
    brand?: string;
    sort?: string;
    price_min?: number;
    price_max?: number | null;
  };
};

export default function CategoryFilter({ categories, currentFilters }: CategoryFilterProps) {
  const buildUrl = (categorySlug: string) => {
    const params = new URLSearchParams();
    
    // Preserve existing filters
    if (currentFilters.q) params.set('q', currentFilters.q);
    if (currentFilters.brand) params.set('brand', currentFilters.brand);
    if (currentFilters.sort) params.set('sort', currentFilters.sort);
    if (currentFilters.price_min != null) params.set('price_min', String(currentFilters.price_min));
    if (currentFilters.price_max != null) params.set('price_max', String(currentFilters.price_max));
    
    // Toggle category filter
    if (!categories.find(c => c.slug === categorySlug)?.active) {
      params.set('category', categorySlug);
    }
    
    return `?${params.toString()}`;
  };

  return (
    <div className="mb-6">
      <h3 className="mb-3 text-lg font-semibold">カテゴリ</h3>
      <div className="flex flex-wrap gap-2">
        {categories.map((category) => (
          <Link
            key={category.slug}
            href={buildUrl(category.slug)}
            className={`rounded-full px-3 py-1 text-sm transition-colors ${
              category.active
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600'
            }`}
            preserveScroll
          >
            {category.name} ({category.count})
          </Link>
        ))}
      </div>
    </div>
  );
}