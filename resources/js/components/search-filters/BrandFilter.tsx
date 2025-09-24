type BrandFacet = {
  slug: string;
  name: string;
  count: number;
  active?: boolean;
};

type BrandFilterProps = {
  brands: BrandFacet[];
  currentFilters: {
    brand?: string;
  };
  onFilterChange: (key: string, value: any) => void;
};

export default function BrandFilter({ brands, currentFilters, onFilterChange }: BrandFilterProps) {
  const handleBrandClick = (brandSlug: string) => {
    if (currentFilters.brand === brandSlug) {
      // If the brand is already selected, remove it
      onFilterChange('brand', undefined);
    } else {
      // Otherwise, select the new brand
      onFilterChange('brand', brandSlug);
    }
  };

  return (
    <div className="mb-6">
      <h3 className="mb-3 text-lg font-semibold">ブランド</h3>
      <div className="flex flex-wrap gap-2">
        {brands.map((brand) => (
          <button
            key={brand.slug}
            onClick={() => handleBrandClick(brand.slug)}
            className={`rounded-full px-3 py-1 text-sm transition-colors ${
              brand.active
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600'
            }`}
          >
            {brand.name} ({brand.count})
          </button>
        ))}
      </div>
    </div>
  );
}