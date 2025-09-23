import { Link } from '@inertiajs/react';

type GenderFilterProps = {
  currentFilters: {
    q?: string;
    category?: string;
    brand?: string;
    sort?: string;
    price_min?: number;
    price_max?: number | null;
    gender?: string;
  };
};

export default function GenderFilter({ currentFilters }: GenderFilterProps) {
  const genders = [
    { value: 'all', label: 'すべて' },
    { value: 'men', label: 'メンズ' },
    { value: 'women', label: 'レディース' },
    { value: 'unisex', label: 'ユニセックス' },
  ];

  const buildUrl = (gender: string) => {
    const params = new URLSearchParams();
    
    // Preserve existing filters
    if (currentFilters.q) params.set('q', currentFilters.q);
    if (currentFilters.category) params.set('category', currentFilters.category);
    if (currentFilters.brand) params.set('brand', currentFilters.brand);
    if (currentFilters.sort) params.set('sort', currentFilters.sort);
    if (currentFilters.price_min != null) params.set('price_min', String(currentFilters.price_min));
    if (currentFilters.price_max != null) params.set('price_max', String(currentFilters.price_max));
    
    // Set gender filter (except for 'all')
    if (gender !== 'all') {
      params.set('gender', gender);
    }
    
    return `?${params.toString()}`;
  };

  return (
    <div className="mb-6">
      <h3 className="mb-3 text-lg font-semibold">性別</h3>
      <div className="flex flex-wrap gap-2">
        {genders.map((gender) => (
          <Link
            key={gender.value}
            href={buildUrl(gender.value)}
            className={`rounded-full px-3 py-1 text-sm transition-colors ${
              (currentFilters.gender || 'all') === gender.value
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600'
            }`}
            preserveScroll
          >
            {gender.label}
          </Link>
        ))}
      </div>
    </div>
  );
}