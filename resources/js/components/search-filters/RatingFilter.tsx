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
        <h3 className="text-lg font-semibold text-black">評価</h3>
        {hasActiveRatingFilter && (
          <button
            onClick={() => onClearFilter('rating')}
            className="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
          >
            クリア
          </button>
        )}
      </div>
      <div className="space-y-1">
        {ratings.map((rating) => (
          <div key={rating.rating} className="flex items-center gap-2">
            <label className="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                checked={currentFilters.rating === rating.rating}
                onChange={() => handleRatingClick(rating.rating)}
                className="h-4 w-4 rounded border-[#888888] text-blue-600 focus:ring-blue-500"
              />
              <span className="text-sm text-black">{rating.label} ({rating.count})</span>
            </label>
          </div>
        ))}
      </div>
    </div>
  );
}