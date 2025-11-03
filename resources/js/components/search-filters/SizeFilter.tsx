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
        <h3 className="text-lg font-semibold text-black">容量 (ml)</h3>
        {hasActiveSizeFilter && (
          <button
            onClick={() => onClearFilter('size')}
            className="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
          >
            クリア
          </button>
        )}
      </div>
      <div className="space-y-1">
        {sizes.map((size) => (
          <div key={size} className="flex items-center gap-2">
            <label className="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                checked={currentFilters.size === size}
                onChange={() => handleSizeClick(size)}
                className="h-4 w-4 rounded border-[#888888] text-blue-600 focus:ring-blue-500"
              />
              <span className="text-sm text-black">{size}ml</span>
            </label>
          </div>
        ))}
      </div>
    </div>
  );
}