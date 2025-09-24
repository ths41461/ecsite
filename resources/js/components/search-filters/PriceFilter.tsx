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
    price_min?: number;
    price_max?: number | null;
  };
  onFilterChange: (key: string, value: any) => void;
  onClearFilter: (key: string) => void;
};

export default function PriceFilter({ prices, currentFilters, onFilterChange, onClearFilter }: PriceFilterProps) {
  const handlePriceClick = (priceMin: number, priceMax: number | null) => {
    onFilterChange('priceMin', priceMin);
    onFilterChange('priceMax', priceMax);
  };

  // Check if any price filter is active
  const hasActivePriceFilter = prices.some(p => p.active);

  return (
    <div className="mb-6">
      <div className="mb-3 flex items-center justify-between">
        <h3 className="text-lg font-semibold">価格</h3>
        {hasActivePriceFilter && (
          <button
            onClick={() => onClearFilter('price')}
            className="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
          >
            クリア
          </button>
        )}
      </div>
      <div className="space-y-2">
        {prices.map((price, index) => (
          <button
            key={index}
            onClick={() => handlePriceClick(price.min, price.max)}
            className={`block rounded-lg px-3 py-2 text-sm transition-colors ${
              price.active
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600'
            }`}
          >
            {price.label} ({price.count})
          </button>
        ))}
      </div>
    </div>
  );
}