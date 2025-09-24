type RatingFacet = {
  rating: number;
  label: string;
  count: number;
  active?: boolean;
};

type RatingFilterProps = {
  ratings: RatingFacet[];
  currentFilters: {
    rating?: number;
  };
  onFilterChange: (key: string, value: any) => void;
  onClearFilter: (key: string) => void;
};

export default function RatingFilter({ ratings, currentFilters, onFilterChange, onClearFilter }: RatingFilterProps) {
  const handleRatingClick = (rating: number) => {
    if (currentFilters.rating === rating) {
      // If the rating is already selected, remove it
      onFilterChange('rating', undefined);
    } else {
      // Otherwise, select the new rating
      onFilterChange('rating', rating);
    }
  };

  // Check if any rating filter is active
  const hasActiveRatingFilter = ratings.some(r => r.active || currentFilters.rating === r.rating);

  return (
    <div className="mb-6">
      <div className="mb-3 flex items-center justify-between">
        <h3 className="text-lg font-semibold">評価</h3>
        {hasActiveRatingFilter && (
          <button
            onClick={() => onClearFilter('rating')}
            className="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
          >
            クリア
          </button>
        )}
      </div>
      <div className="space-y-2">
        {ratings.map((rating) => (
          <button
            key={rating.rating}
            onClick={() => handleRatingClick(rating.rating)}
            className={`flex items-center justify-between rounded-lg px-3 py-2 text-sm transition-colors ${
              currentFilters.rating === rating.rating
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600'
            }`}
          >
            <span>{rating.label}</span>
            <span className="ml-2 rounded-full bg-gray-200 px-2 py-1 text-xs dark:bg-gray-600">
              {rating.count}
            </span>
          </button>
        ))}
      </div>
    </div>
  );
}