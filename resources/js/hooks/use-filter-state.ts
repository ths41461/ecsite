import { useState, useEffect } from 'react';

type FilterState = {
  q?: string;
  brand?: string;
  category?: string;
  priceMin?: number;
  priceMax?: number | null;
  rating?: number;
  gender?: string;
  size?: number;
  sort?: string;
};

type UseFilterStateProps = {
  initialFilters: FilterState;
};

function sanitizeFilters(filters: FilterState): FilterState {
  const sanitized: FilterState = {};
  const allowedSortValues = ['', 'newest', 'price_asc', 'price_desc'];

  if (filters.q && typeof filters.q === 'string') sanitized.q = filters.q;
  if (filters.brand && typeof filters.brand === 'string') sanitized.brand = filters.brand;
  if (filters.category && typeof filters.category === 'string') sanitized.category = filters.category;
  if (filters.priceMin !== undefined && filters.priceMin !== null && typeof filters.priceMin === 'number') sanitized.priceMin = filters.priceMin;
  if (filters.priceMax !== undefined && (filters.priceMax === null || typeof filters.priceMax === 'number')) sanitized.priceMax = filters.priceMax;
  if (filters.rating !== undefined && typeof filters.rating === 'number') sanitized.rating = filters.rating;
  if (filters.gender && typeof filters.gender === 'string') sanitized.gender = filters.gender;
  if (filters.size !== undefined && typeof filters.size === 'number') sanitized.size = filters.size;

  if (filters.sort && typeof filters.sort === 'string' && allowedSortValues.includes(filters.sort)) {
    sanitized.sort = filters.sort;
  } else if (filters.sort === null || filters.sort === undefined) {
    sanitized.sort = undefined;
  }
  return sanitized;
}

export function useFilterState({ initialFilters }: UseFilterStateProps) {
  const [filters, setFilters] = useState<FilterState>(() => {
    const hasInitialFilters = Object.values(initialFilters).some(v => v !== undefined);

    if (hasInitialFilters) {
      return sanitizeFilters(initialFilters);
    }

    const savedFilters = localStorage.getItem('productFilters');
    if (savedFilters) {
      try {
        const parsed = JSON.parse(savedFilters);
        return sanitizeFilters(parsed);
      } catch {
        return sanitizeFilters(initialFilters); // Fallback to sanitized initialFilters if parsing fails
      }
    }

    return sanitizeFilters(initialFilters);
  });

  useEffect(() => {
    localStorage.setItem('productFilters', JSON.stringify(filters));
  }, [filters]);

  const updateFilter = (key: string, value: any) => {
    setFilters(prev => {
      const newFilters = { ...prev, [key]: value };
      return sanitizeFilters(newFilters);
    });
  };

  const clearFilter = (key: keyof FilterState) => {
    setFilters(prev => {
      const newFilters = { ...prev };
      delete newFilters[key];
      return sanitizeFilters(newFilters);
    });
  };

  const clearAllFilters = () => {
    setFilters({});
  };

  return {
    filters,
    updateFilter,
    clearFilter,
    clearAllFilters
  };
}