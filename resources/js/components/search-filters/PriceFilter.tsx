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
  const minPrice = currentFilters.price_min ?? 300;
  const maxPrice = currentFilters.price_max ?? 300000;

  const handleMinChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = parseInt(e.target.value);
    onFilterChange('priceMin', value);
  };

  const handleMaxChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = parseInt(e.target.value);
    onFilterChange('priceMax', value);
  };

  // Check if any price filter is active
  const hasActivePriceFilter = currentFilters.price_min !== undefined || currentFilters.price_max !== undefined;

  return (
    <div className="mb-6">
      <div className="mb-3 flex items-center justify-between">
        <h3 className="text-lg font-semibold text-black">価格</h3>
        {hasActivePriceFilter && (
          <button
            onClick={() => onClearFilter('price')}
            className="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
          >
            クリア
          </button>
        )}
      </div>
      <div className="mb-4">
        <div className="flex gap-4 mb-2">
          <div className="flex items-center border border-[#AAB4C3] px-4 py-4 flex-1">
            <span className="text-sm mr-1 text-black">￥</span>
            <input
              type="number"
              value={minPrice}
              onChange={handleMinChange}
              className="bg-transparent border-none focus:outline-none text-sm w-full text-black"
            />
          </div>
          <div className="flex items-center justify-center">
            <span className="text-[14px] text-[#848488]">~</span>
          </div>
          <div className="flex items-center border border-[#AAB4C3] px-4 py-4 flex-1">
            <span className="text-sm mr-1 text-black">￥</span>
            <input
              type="number"
              value={maxPrice}
              onChange={handleMaxChange}
              className="bg-transparent border-none focus:outline-none text-sm w-full text-black"
            />
          </div>
        </div>
      </div>
      <div className="relative mb-2">
        <div className="h-3 bg-[#AAB4C3] rounded-full">
          <div 
            className="absolute h-3 bg-[#EAB308] rounded-full" 
            style={{ 
              left: `${((minPrice - 300) / (300000 - 300)) * 100}%`, 
              width: `${((maxPrice - minPrice) / (300000 - 300)) * 100}%` 
            }}
          />
          <div 
            className="absolute top-0 w-3 h-3 bg-white border border-[#AAB4C3] rounded-full -mt-0.5" 
            style={{ left: `calc(${((minPrice - 300) / (300000 - 300)) * 100}% - 6px)` }}
          />
          <div 
            className="absolute top-0 w-3 h-3 bg-white border border-[#AAB4C3] rounded-full -mt-0.5" 
            style={{ left: `calc(${((maxPrice - 300) / (300000 - 300)) * 100}% - 6px)` }}
          />
        </div>
      </div>
      <div className="flex justify-between text-xs text-gray-500">
        <span className="text-black">300￥</span>
        <span className="text-black">1300￥</span>
      </div>
    </div>
  );
}