import { useState, useEffect } from 'react';

type FilterState = {
  brand?: string;
  category?: string;
  priceMin?: number;
  priceMax?: number | null;
  rating?: number;
  gender?: string;
  size?: number;
  sortBy?: string;
};

type UseFilterStateProps = {
  initialFilters: FilterState;
};

export function useFilterState({ initialFilters }: UseFilterStateProps) {
  const [filters, setFilters] = useState<FilterState>(initialFilters);
  
  // Update URL when filters change
  useEffect(() => {
    const params = new URLSearchParams();
    
    if (filters.brand) params.set('brand', filters.brand);
    if (filters.category) params.set('category', filters.category);
    if (filters.priceMin !== undefined) params.set('price_min', String(filters.priceMin));
    if (filters.priceMax !== undefined) {
      if (filters.priceMax !== null) {
        params.set('price_max', String(filters.priceMax));
      } else {
        params.delete('price_max');
      }
    }
    if (filters.rating) params.set('rating', String(filters.rating));
    if (filters.gender) params.set('gender', filters.gender);
    if (filters.size) params.set('size', String(filters.size));
    if (filters.sortBy) params.set('sort', filters.sortBy);
    
    // Update URL without page reload
    const newUrl = `${window.location.pathname}?${params.toString()}`;
    window.history.replaceState({}, '', newUrl);
  }, [filters]);
  
  // Load filters from URL on initial render
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    
    const urlFilters: FilterState = {};
    
    if (params.has('brand')) urlFilters.brand = params.get('brand') || undefined;
    if (params.has('category')) urlFilters.category = params.get('category') || undefined;
    if (params.has('price_min')) urlFilters.priceMin = Number(params.get('price_min'));
    if (params.has('price_max')) {
      const priceMax = params.get('price_max');
      urlFilters.priceMax = priceMax ? Number(priceMax) : null;
    }
    if (params.has('rating')) urlFilters.rating = Number(params.get('rating'));
    if (params.has('gender')) urlFilters.gender = params.get('gender') || undefined;
    if (params.has('size')) urlFilters.size = Number(params.get('size'));
    if (params.has('sort')) urlFilters.sortBy = params.get('sort') || undefined;
    
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
        setFilters(prev => ({ ...prev, ...parsedFilters }));
      } catch (e) {
        console.warn('Failed to parse saved filters', e);
      }
    }
  }, []);
  
  const updateFilter = (key: keyof FilterState, value: any) => {
    setFilters(prev => ({ ...prev, [key]: value }));
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