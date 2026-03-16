import { useState } from 'react';

type Category = {
  id: number;
  name: string;
  slug: string;
  parent_id: number | null;
  depth: number;
  children?: Category[];
};

type HierarchicalCategoryFilterProps = {
  categories: Category[];
  currentFilters: {
    category?: string;
  };
  onFilterChange: (key: string, value: any) => void;
};

export default function HierarchicalCategoryFilter({ categories, currentFilters, onFilterChange }: HierarchicalCategoryFilterProps) {
  // Convert flat list to hierarchical structure
  const buildHierarchy = (categories: Category[]): Category[] => {
    const categoryMap = new Map<number, Category>();
    const roots: Category[] = [];
    
    // Create a map of all categories
    categories.forEach(category => {
      categoryMap.set(category.id, { ...category, children: [] });
    });
    
    // Build hierarchy
    categories.forEach(category => {
      const cat = categoryMap.get(category.id)!;
      if (category.parent_id === null) {
        roots.push(cat);
      } else {
        const parent = categoryMap.get(category.parent_id);
        if (parent) {
          parent.children = parent.children || [];
          parent.children.push(cat);
        }
      }
    });
    
    return roots;
  };
  
  const hierarchicalCategories = buildHierarchy(categories);
  
  const handleCategoryClick = (categorySlug: string) => {
    if (currentFilters.category === categorySlug) {
      // If the category is already selected, remove it
      onFilterChange('category', undefined);
    } else {
      // Otherwise, select the new category
      onFilterChange('category', categorySlug);
    }
  };
  
  const CategoryItem = ({ category, level = 0 }: { category: Category; level?: number }) => {
    const [isExpanded, setIsExpanded] = useState(false);
    const hasChildren = category.children && category.children.length > 0;
    
    return (
      <div>
        <div 
          className={`flex items-center ${level > 0 ? 'ml-4' : ''}`}
          style={{ paddingLeft: `${level * 1}rem` }}
        >
          <label className="flex items-center gap-2 cursor-pointer flex-1">
            <input
              type="checkbox"
              checked={currentFilters.category === category.slug}
              onChange={() => handleCategoryClick(category.slug)}
              className="h-4 w-4 rounded border-[#888888] text-blue-600 focus:ring-blue-500"
            />
            <span className="text-sm text-black">{category.name}</span>
          </label>
          {hasChildren && (
            <button 
              onClick={() => setIsExpanded(!isExpanded)}
              className="ml-2 text-gray-500 hover:text-gray-700"
            >
              {isExpanded ? '−' : '+'}
            </button>
          )}
        </div>
        
        {hasChildren && isExpanded && (
          <div className="mt-1">
            {category.children?.map(child => (
              <CategoryItem key={child.id} category={child} level={level + 1} />
            ))}
          </div>
        )}
      </div>
    );
  };
  
  return (
    <div className="mb-6">
      <h3 className="mb-3 text-lg font-semibold text-black">カテゴリ</h3>
      <div className="border border-[#888888] w-[288px] pr-[5px]">
        <div className="max-h-40 overflow-y-auto py-1">
          {hierarchicalCategories.map(category => (
            <CategoryItem key={category.id} category={category} />
          ))}
        </div>
      </div>
    </div>
  );
}