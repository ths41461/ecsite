type SizeFilterProps = {
  currentFilters: {
    size?: number;
  };
  onFilterChange: (key: string, value: any) => void;
  onClearFilter: (key: string) => void;
};

export default function SizeFilter({ currentFilters, onFilterChange, onClearFilter }: SizeFilterProps) {
  // Common perfume sizes in ml
  const sizes = [10, 30, 50, 75, 100, 125, 150, 200];

  const handleSizeClick = (size: number) => {
    if (currentFilters.size === size) {
      // If the size is already selected, remove it
      onFilterChange('size', undefined);
    } else {
      // Otherwise, select the new size
      onFilterChange('size', size);
    }
  };

  // Check if any size filter is active
  const hasActiveSizeFilter = currentFilters.size !== undefined;

  return (
    <div className="mb-6">
      <div className="mb-3 flex items-center justify-between">
        <h3 className="text-lg font-semibold">容量 (ml)</h3>
        {hasActiveSizeFilter && (
          <button
            onClick={() => onClearFilter('size')}
            className="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
          >
            クリア
          </button>
        )}
      </div>
      <div className="flex flex-wrap gap-2">
        {sizes.map((size) => (
          <button
            key={size}
            onClick={() => handleSizeClick(size)}
            className={`rounded-full px-3 py-1 text-sm transition-colors ${
              currentFilters.size === size
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600'
            }`}
          >
            {size}ml
          </button>
        ))}
      </div>
    </div>
  );
}