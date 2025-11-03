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
  const handleBrandToggle = (brandSlug: string) => {
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
      <h3 className="mb-3 text-lg font-semibold text-black">ブランド</h3>
      <div className="border border-[#888888] w-[288px] pr-[5px]">
        <div className="max-h-40 overflow-y-auto py-1">
          {brands.map((brand) => (
            <div key={brand.slug} className="flex items-center gap-2 px-2.5 py-1">
              <label className="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  checked={brand.active || currentFilters.brand === brand.slug}
                  onChange={() => handleBrandToggle(brand.slug)}
                  className="h-4 w-4 rounded border-[#888888] text-blue-600 focus:ring-blue-500"
                />
                <span className="text-sm text-black">{brand.name} ({brand.count})</span>
              </label>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}