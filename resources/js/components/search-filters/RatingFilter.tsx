import { Link } from '@inertiajs/react';

type RatingFacet = {
  rating: number;
  label: string;
  count: number;
  active?: boolean;
};

type RatingFilterProps = {
  ratings: RatingFacet[];
  currentFilters: {
    q?: string;
    category?: string;
    brand?: string;
    sort?: string;
    price_min?: number;
    price_max?: number | null;
    rating?: number;
  };
};

export default function RatingFilter({ ratings, currentFilters }: RatingFilterProps) {
  const buildUrl = (rating: number) => {
    const params = new URLSearchParams();
    
    // Preserve existing filters
    if (currentFilters.q) params.set('q', currentFilters.q);
    if (currentFilters.category) params.set('category', currentFilters.category);
    if (currentFilters.brand) params.set('brand', currentFilters.brand);
    if (currentFilters.sort) params.set('sort', currentFilters.sort);
    if (currentFilters.price_min != null) params.set('price_min', String(currentFilters.price_min));
    if (currentFilters.price_max != null) params.set('price_max', String(currentFilters.price_max));
    
    // Toggle rating filter
    if (currentFilters.rating !== rating) {
      params.set('rating', String(rating));
    }
    
    return `?${params.toString()}`;
  };

  const clearRatingFilter = () => {
    const params = new URLSearchParams();
    
    // Preserve existing filters except rating
    if (currentFilters.q) params.set('q', currentFilters.q);
    if (currentFilters.category) params.set('category', currentFilters.category);
    if (currentFilters.brand) params.set('brand', currentFilters.brand);
    if (currentFilters.sort) params.set('sort', currentFilters.sort);
    if (currentFilters.price_min != null) params.set('price_min', String(currentFilters.price_min));
    if (currentFilters.price_max != null) params.set('price_max', String(currentFilters.price_max));
    
    return `?${params.toString()}`;
  };

  // Check if any rating filter is active
  const hasActiveRatingFilter = ratings.some(r => r.active || currentFilters.rating === r.rating);

  return (
    <div className="mb-6">
      <div className="mb-3 flex items-center justify-between">
        <h3 className="text-lg font-semibold">評価</h3>
        {hasActiveRatingFilter && (
          <Link
            href={clearRatingFilter()}
            className="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
            preserveScroll
          >
            クリア
          </Link>
        )}
      </div>
      <div className="space-y-2">
        {ratings.map((rating) => (
          <Link
            key={rating.rating}
            href={buildUrl(rating.rating)}
            className={`flex items-center justify-between rounded-lg px-3 py-2 text-sm transition-colors ${
              currentFilters.rating === rating.rating
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600'
            }`}
            preserveScroll
          >
            <span>{rating.label}</span>
            <span className="ml-2 rounded-full bg-gray-200 px-2 py-1 text-xs dark:bg-gray-600">
              {rating.count}
            </span>
          </Link>
        ))}
      </div>
    </div>
  );
}