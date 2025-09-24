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

export function useFilterState({ initialFilters }: UseFilterStateProps) {
  const [filters, setFilters] = useState<FilterState>(initialFilters);
  
  // Update URL when filters change
  useEffect(() => {
    const params = new URLSearchParams();
    
    // Only set string values, avoid functions
    if (filters.q && typeof filters.q === 'string') params.set('q', filters.q);
    if (filters.brand && typeof filters.brand === 'string') params.set('brand', filters.brand);
    if (filters.category && typeof filters.category === 'string') params.set('category', filters.category);
    if (filters.priceMin !== undefined && typeof filters.priceMin === 'number') params.set('price_min', String(filters.priceMin));
    if (filters.priceMax !== undefined) {
      if (filters.priceMax !== null && typeof filters.priceMax === 'number') {
        params.set('price_max', String(filters.priceMax));
      } else {
        params.delete('price_max');
      }
    }
    if (filters.rating && typeof filters.rating === 'number') params.set('rating', String(filters.rating));
    if (filters.gender && typeof filters.gender === 'string') params.set('gender', filters.gender);
    if (filters.size && typeof filters.size === 'number') params.set('size', String(filters.size));
    // Only set sort if it's a string and not a function
    if (filters.sort && typeof filters.sort === 'string') params.set('sort', filters.sort);
    
    // Update URL without page reload
    const newUrl = `${window.location.pathname}?${params.toString()}`;
    window.history.replaceState({}, '', newUrl);
  }, [filters]);
  
  // Load filters from URL on initial render
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    
    const urlFilters: FilterState = {};
    
    if (params.has('q')) {
      const qValue = params.get('q') || undefined;
      if (qValue && typeof qValue === 'string') urlFilters.q = qValue;
    }
    if (params.has('brand')) {
      const brandValue = params.get('brand') || undefined;
      if (brandValue && typeof brandValue === 'string') urlFilters.brand = brandValue;
    }
    if (params.has('category')) {
      const categoryValue = params.get('category') || undefined;
      if (categoryValue && typeof categoryValue === 'string') urlFilters.category = categoryValue;
    }
    if (params.has('price_min')) {
      const priceMinValue = Number(params.get('price_min'));
      if (!isNaN(priceMinValue)) urlFilters.priceMin = priceMinValue;
    }
    if (params.has('price_max')) {
      const priceMaxValue = params.get('price_max');
      if (priceMaxValue !== null && priceMaxValue !== '') {
        const parsedPriceMax = Number(priceMaxValue);
        if (!isNaN(parsedPriceMax)) {
          urlFilters.priceMax = parsedPriceMax;
        }
      } else {
        urlFilters.priceMax = null;
      }
    }
    if (params.has('rating')) {
      const ratingValue = Number(params.get('rating'));
      if (!isNaN(ratingValue)) urlFilters.rating = ratingValue;
    }
    if (params.has('gender')) {
      const genderValue = params.get('gender') || undefined;
      if (genderValue && typeof genderValue === 'string') urlFilters.gender = genderValue;
    }
    if (params.has('size')) {
      const sizeValue = Number(params.get('size'));
      if (!isNaN(sizeValue)) urlFilters.size = sizeValue;
    }
    if (params.has('sort')) {
      const sortValue = params.get('sort') || undefined;
      // Only set sort if it's a valid string (not a function)
      if (sortValue && typeof sortValue === 'string') {
        urlFilters.sort = sortValue;
      }
    }
    
    setFilters(prev => ({ ...prev, ...urlFilters }));
  }, []);
  
  // Save filters to localStorage
  useEffect(() => {
    localStorage.setItem('productFilters', JSON.stringify(filters));
  }, [filters]);
  
  // Load filters from localStorage on initial render
  useEffect(() => {
    const savedFilters = localStorage.getItem('productFilters');
    if (savedFilters) {
      try {
        const parsedFilters = JSON.parse(savedFilters);
        
        // Sanitize filters to remove any function references
        const sanitizedFilters: FilterState = {};
        for (const [key, value] of Object.entries(parsedFilters)) {
          if (typeof value !== 'function') {
            sanitizedFilters[key as keyof FilterState] = value as any;
          }
        }
        
        setFilters(prev => ({ ...prev, ...sanitizedFilters }));
      } catch (e) {
        console.warn('Failed to parse saved filters', e);
      }
    }
  }, []);
  
  const updateFilter = (key: string, value: any) => {
    // Prevent functions from being stored as filter values
    const safeValue = typeof value === 'function' ? undefined : value;
    setFilters(prev => ({ ...prev, [key]: safeValue }));
  };
  
  const clearFilter = (key: keyof FilterState) => {
    setFilters(prev => {
      const newFilters = { ...prev };
      delete newFilters[key];
      return newFilters;
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