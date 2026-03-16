import { Link } from '@inertiajs/react';

type CategoryFacet = {
  slug: string;
  name: string;
  count: number;
  active?: boolean;
};

type CategoryFilterProps = {
  categories: CategoryFacet[];
  currentFilters: {
    category?: string;
  };
  onFilterChange: (key: string, value: any) => void;
};

export default function CategoryFilter({ categories, currentFilters, onFilterChange }: CategoryFilterProps) {
  const handleCategoryClick = (categorySlug: string) => {
    if (currentFilters.category === categorySlug) {
      onFilterChange('category', undefined);
    } else {
      onFilterChange('category', categorySlug);
    }
  };

  return (
    <div className="mb-6">
      <h3 className="mb-3 text-lg font-semibold">カテゴリ</h3>
      <div className="border border-[#888888] w-[288px] pr-[5px]">
        <div className="max-h-40 overflow-y-auto py-1">
          {categories.map((category) => (
            <div key={category.slug} className="flex items-center gap-2 px-2.5 py-1">
              <label className="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  checked={category.active || currentFilters.category === category.slug}
                  onChange={() => handleCategoryClick(category.slug)}
                  className="h-4 w-4 rounded border-[#888888] text-blue-600 focus:ring-blue-500"
                />
                <span className="text-sm">{category.name} ({category.count})</span>
              </label>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}