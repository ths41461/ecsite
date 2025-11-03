import { HomeNavigation } from '@/components/homeNavigation';
import MinimalistProductCard from '@/Components/MinimalistProductCard';
import BrandFilter from '@/components/search-filters/BrandFilter';
import FragranceTypeFilter from '@/components/search-filters/FragranceTypeFilter';
import GenderFilter from '@/components/search-filters/GenderFilter';
import HierarchicalCategoryFilter from '@/components/search-filters/HierarchicalCategoryFilter';
import PriceFilter from '@/components/search-filters/PriceFilter';
import RatingFilter from '@/components/search-filters/RatingFilter';
import SizeFilter from '@/components/search-filters/SizeFilter';
import { useFilterState } from '@/hooks/use-filter-state';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

type ProductItem = {
    id: number;
    name: string;
    slug: string;
    brand: { name?: string | null; slug?: string | null };
    image?: string | null;
    price_cents: number | null;
    compare_at_cents: number | null;
    average_rating?: number;
    review_count?: number;
    genders?: string[];
    sizes?: number[];
};
type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    meta?: { current_page: number; last_page: number; total: number };
};
type FacetBrand = { slug: string; name: string; count: number; active?: boolean };
type FacetCategory = { id: number; slug: string; name: string; count: number; active?: boolean; parent_id?: number | null; depth?: number };
type FacetPrice = { label: string; min: number; max: number | null; count: number; active?: boolean };
type FacetRating = { rating: number; label: string; count: number; active?: boolean };

type Props = {
    products: Paginated<ProductItem>;
    filters: {
        q?: string;
        category?: string;
        brand?: string;
        sort?: string;
        price_min?: number;
        price_max?: number | null;
        rating?: number;
        gender?: string;
        size?: number;
        fragranceType?: string[];
    };
    facets: { brands: FacetBrand[]; categories: FacetCategory[]; prices: FacetPrice[]; ratings: FacetRating[] };
};

type SearchSuggestion = {
    id: number;
    name: string;
    slug: string;
    brand: { name?: string | null };
    category: { name?: string | null };
    price?: number | null;
    image?: string | null;
};

function SearchWithAutocomplete({
    initialQuery,
    currentFilters,
    updateFilter,
}: {
    initialQuery: string;
    currentFilters: any;
    updateFilter: (key: string, value: any) => void;
}) {
    const [query, setQuery] = useState(initialQuery);
    const [suggestions, setSuggestions] = useState<SearchSuggestion[]>([]);
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [loading, setLoading] = useState(false);
    const [selectedIndex, setSelectedIndex] = useState(-1);
    const searchTimeoutRef = useRef<NodeJS.Timeout | null>(null);
    const inputRef = useRef<HTMLInputElement>(null);

    const fetchSuggestions = async (searchQuery: string) => {
        if (!searchQuery.trim()) {
            setSuggestions([]);
            setShowSuggestions(false);
            return;
        }
        setLoading(true);
        try {
            const response = await fetch(`/api/search/autocomplete?q=${encodeURIComponent(searchQuery)}`, {
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
            });
            if (response.ok) {
                const data = await response.json();
                setSuggestions(data.suggestions || []);
                setShowSuggestions(true);
            } else {
                setSuggestions([]);
            }
        } catch (error) {
            console.error('Error fetching search suggestions:', error);
            setSuggestions([]);
        } finally {
            setLoading(false);
        }
    };

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        setQuery(value);
        updateFilter('q', value);
        if (searchTimeoutRef.current) {
            clearTimeout(searchTimeoutRef.current);
        }
        searchTimeoutRef.current = setTimeout(() => {
            fetchSuggestions(value);
            setSelectedIndex(-1);
        }, 300);
    };
    const handleSuggestionClick = (suggestion: SearchSuggestion) => {
        setQuery(suggestion.name);
        updateFilter('q', suggestion.name);
        setShowSuggestions(false);
        // Navigate directly to the specific product page using the slug
        router.get(`/products/${suggestion.slug}`);
    };

    const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setSelectedIndex((prev) => Math.min(prev + 1, suggestions.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setSelectedIndex((prev) => Math.max(prev - 1, -1));
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (selectedIndex >= 0 && suggestions[selectedIndex]) {
                // Navigate directly to the specific product page using the slug
                router.get(`/products/${suggestions[selectedIndex].slug}`);
            } else if (query.trim()) {
                const params = new URLSearchParams();
                params.set('q', query.trim());
                if (currentFilters.brand) params.set('brand', currentFilters.brand);
                if (currentFilters.category) params.set('category', currentFilters.category);
                if (currentFilters.sort) params.set('sort', currentFilters.sort);
                if (currentFilters.priceMin != null) params.set('price_min', String(currentFilters.priceMin));
                if (currentFilters.priceMax != null) params.set('price_max', String(currentFilters.priceMax));
                if (currentFilters.rating) params.set('rating', String(currentFilters.rating));
                if (currentFilters.gender) params.set('gender', currentFilters.gender);
                if (currentFilters.size) params.set('size', String(currentFilters.size));
                if (currentFilters.fragranceType) params.set('fragranceType', currentFilters.fragranceType.join(','));
                router.get(`/products?${params.toString()}`);
                setShowSuggestions(false);
            }
        } else if (e.key === 'Escape') {
            setShowSuggestions(false);
            inputRef.current?.blur();
        }
    };

    const handleInputFocus = () => {
        if (query && suggestions.length > 0) {
            setShowSuggestions(true);
        }
    };

    const clearSearch = () => {
        setQuery('');
        updateFilter('q', '');
        setSuggestions([]);
        setShowSuggestions(false);
        const params = new URLSearchParams();
        if (currentFilters.brand) params.set('brand', currentFilters.brand);
        if (currentFilters.category) params.set('category', currentFilters.category);
        if (currentFilters.sort) params.set('sort', currentFilters.sort);
        if (currentFilters.priceMin != null) params.set('price_min', String(currentFilters.priceMin));
        if (currentFilters.priceMax != null) params.set('price_max', String(currentFilters.priceMax));
        if (currentFilters.rating) params.set('rating', String(currentFilters.rating));
        if (currentFilters.gender) params.set('gender', currentFilters.gender);
        if (currentFilters.size) params.set('size', String(currentFilters.size));
        if (currentFilters.fragranceType) params.set('fragranceType', currentFilters.fragranceType.join(','));
        const url = params.toString() ? `/products?${params.toString()}` : '/products';
        router.get(url);
        inputRef.current?.focus();
    };

    useEffect(() => {
        if (currentFilters.q !== query) {
            setQuery(currentFilters.q || '');
        }
    }, [currentFilters.q]);

    useEffect(() => {
        return () => {
            if (searchTimeoutRef.current) {
                clearTimeout(searchTimeoutRef.current);
            }
        };
    }, []);

    return (
        <div className="relative">
            <div className="relative">
                <input
                    ref={inputRef}
                    type="text"
                    value={query}
                    onChange={handleInputChange}
                    onKeyDown={handleKeyDown}
                    onFocus={handleInputFocus}
                    onBlur={() => setTimeout(() => setShowSuggestions(false), 150)}
                    placeholder="商品を検索..."
                    className="w-full border border-gray-300 px-4 py-2.5 pr-10 pl-10 focus:border-gray-500 focus:ring-1 focus:ring-gray-500 focus:outline-none"
                />
                <div className="absolute top-1/2 left-3 -translate-y-1/2 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                {query && (
                    <button onClick={clearSearch} className="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                )}
            </div>
            {showSuggestions && suggestions.length > 0 && (
                <div className="absolute z-10 mt-1 w-full border border-gray-300 bg-white">
                    <ul>
                        {suggestions.map((suggestion, index) => (
                            <li
                                key={suggestion.id}
                                className={`cursor-pointer px-4 py-3 ${index === selectedIndex ? 'bg-gray-100' : ''}`}
                                onMouseDown={() => handleSuggestionClick(suggestion)}
                            >
                                <div className="flex items-center gap-3">
                                    {suggestion.image && (
                                        <div className="h-12 w-12 flex-shrink-0">
                                            <img
                                                src={suggestion.image}
                                                alt={suggestion.name}
                                                className="h-full w-full object-cover"
                                                loading="lazy"
                                            />
                                        </div>
                                    )}
                                    <div className="min-w-0 flex-1">
                                        <div className="font-['Hiragino_Mincho_ProN'] truncate font-medium text-gray-900">{suggestion.name}</div>
                                        <div className="truncate text-sm text-gray-500">
                                            {suggestion.brand?.name} • {suggestion.category?.name}
                                        </div>
                                        {suggestion.price && (
                                            <div className="text-sm font-['Hiragino_Mincho_ProN'] font-medium text-gray-900">
                                                ¥{suggestion.price.toLocaleString()}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
            {loading && (
                <div className="absolute top-1/2 right-10 -translate-y-1/2">
                    <div className="h-5 w-5 animate-spin border-2 border-gray-300 border-t-black" />
                </div>
            )}
            {showSuggestions && suggestions.length === 0 && query && !loading && (
                <div className="absolute z-10 mt-1 w-full border border-gray-300 bg-white px-4 py-3 text-sm text-gray-600">
                    検索結果が見つかりません
                </div>
            )}
        </div>
    );
}

export default function Index({ products, filters, facets }: Props) {
    const isLoading = useInertiaLoading();
    const isInitialMount = useRef(true);
    const [isFilterSidebarOpen, setFilterSidebarOpen] = useState(() => {
        const savedState = localStorage.getItem('productFilterSidebarOpen');
        return savedState ? JSON.parse(savedState) : false;
    });

    useEffect(() => {
        localStorage.setItem('productFilterSidebarOpen', JSON.stringify(isFilterSidebarOpen));
    }, [isFilterSidebarOpen]);

    const {
        filters: stateFilters,
        updateFilter,
        clearFilter,
        clearAllFilters: clearStateFilters,
    } = useFilterState({
        initialFilters: {
            q: filters.q || undefined,
            brand: filters.brand || undefined,
            category: filters.category || undefined,
            priceMin: filters.price_min,
            priceMax: filters.price_max,
            rating: filters.rating,
            gender: filters.gender || undefined,
            size: filters.size,
            fragranceType: filters.fragranceType || undefined,
            sort: filters.sort || undefined,
        },
    });

    useEffect(() => {
        if (isInitialMount.current) {
            isInitialMount.current = false;
            return;
        }
        const params = new URLSearchParams();
        if (stateFilters.brand) params.set('brand', stateFilters.brand);
        if (stateFilters.category) params.set('category', stateFilters.category);
        if (stateFilters.priceMin != null) params.set('price_min', String(stateFilters.priceMin));
        if (stateFilters.priceMax != null) params.set('price_max', String(stateFilters.priceMax));
        if (stateFilters.rating) params.set('rating', String(stateFilters.rating));
        if (stateFilters.gender) params.set('gender', stateFilters.gender);
        if (stateFilters.size) params.set('size', String(stateFilters.size));
        if (stateFilters.fragranceType) params.set('fragranceType', stateFilters.fragranceType.join(','));
        if (stateFilters.sort) params.set('sort', stateFilters.sort);
        if (stateFilters.q) params.set('q', stateFilters.q);

        router.get(
            `/products?${params.toString()}`,
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, [
        stateFilters.brand,
        stateFilters.category,
        stateFilters.priceMin,
        stateFilters.priceMax,
        stateFilters.rating,
        stateFilters.gender,
        stateFilters.size,
        stateFilters.sort,
    ]);

    const handleClearAllFilters = () => {
        clearStateFilters();
        router.get('/products');
    };

    const handleResetConditions = () => {
        // Reset all filters except search query and sort
        updateFilter('brand', undefined);
        updateFilter('category', undefined);
        updateFilter('priceMin', undefined);
        updateFilter('priceMax', undefined);
        updateFilter('rating', undefined);
        updateFilter('gender', undefined);
        updateFilter('size', undefined);
        updateFilter('fragranceType', undefined);
    };

    const handleClearPrice = () => {
        updateFilter('priceMin', undefined);
        updateFilter('priceMax', undefined);
    };

    const hasActiveFilters =
        stateFilters.brand ||
        stateFilters.category ||
        stateFilters.priceMin !== undefined ||
        stateFilters.priceMax !== undefined ||
        stateFilters.rating ||
        stateFilters.gender ||
        stateFilters.size ||
        (stateFilters.fragranceType && stateFilters.fragranceType.length > 0);

    return (
        <div className="min-h-screen bg-gray-50">
            <HomeNavigation />
            <div className="container mx-auto px-4 py-6">
                <Head title="商品" />

                {/* Header Section with consistent spacing and typography */}
                <div className="w-full border-b border-[#888888] py-8 bg-[#FCFCF7]">
                    <div className="container mx-auto px-4">
                        <div className="flex flex-col items-center text-center">
                            <h1 className="font-['Hiragino_Mincho_ProN'] text-3xl font-bold text-gray-900 mb-2">商品一覧</h1>
                            <p className="font-['Hiragino_Mincho_ProN'] text-base text-gray-700">{products.meta?.total ?? 0}個の商品</p>
                        </div>
                    </div>
                </div>

                {/* Filter Controls with consistent styling */}
                <div className="py-6">
                    <div className="container mx-auto px-4">
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <div className="flex flex-wrap items-center gap-4">
                                <button
                                    onClick={() => setFilterSidebarOpen(!isFilterSidebarOpen)}
                                    className="flex items-center justify-center gap-2 border border-gray-300 px-4 py-2.5 bg-white text-sm font-medium text-gray-700"
                                >
                                    <svg className="h-4 w-4 stroke-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                                        />
                                    </svg>
                                    <span>絞り込み</span>
                                </button>

                                {hasActiveFilters && (
                                    <button
                                        onClick={handleClearAllFilters}
                                        className="flex items-center justify-center gap-2 border border-gray-300 px-4 py-2.5 bg-white text-sm font-medium text-gray-700"
                                    >
                                        <span>絞り込み削除</span>
                                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                )}
                            </div>

                            <div className="w-full sm:w-auto sm:flex-1 sm:max-w-xs">
                                <div className="relative border border-gray-300">
                                    <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <svg className="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth={2}
                                                d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"
                                            />
                                        </svg>
                                    </div>
                                    <select
                                        className="block w-full pl-10 pr-10 py-2.5 text-sm text-gray-700 font-medium bg-white border-0 focus:outline-none focus:ring-0 focus:ring-offset-0"
                                        value={stateFilters.sort || 'alphabetical'}
                                        onChange={(e) => {
                                            const value = e.target.value === 'alphabetical' ? '' : e.target.value;
                                            updateFilter('sort', value);
                                        }}
                                    >
                                        <option value="alphabetical">アルファベット順, A–Z</option>
                                        <option value="newest">新着順</option>
                                        <option value="price_asc">価格の安い順</option>
                                        <option value="price_desc">価格の高い順</option>
                                    </select>
                                    <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <svg className="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {hasActiveFilters && (
                    <div className="container mx-auto px-4 py-4">
                        <div className="flex flex-wrap items-center gap-3">
                            <span className="font-['Hiragino_Mincho_ProN'] text-sm text-gray-700">選択中のフィルター:</span>
                            <div className="flex flex-wrap gap-2">
                                {stateFilters.q && (
                                    <span className="inline-flex items-center px-3 py-1 text-sm font-medium bg-gray-800 text-white">
                                        検索: {stateFilters.q}
                                        <button 
                                            onClick={() => updateFilter('q', undefined)}
                                            className="ml-2 flex items-center"
                                        >
                                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </span>
                                )}
                                {stateFilters.brand && (
                                    <span className="inline-flex items-center px-3 py-1 text-sm font-medium bg-gray-800 text-white">
                                        ブランド: {facets.brands.find((b) => b.slug === stateFilters.brand)?.name || stateFilters.brand}
                                        <button 
                                            onClick={() => updateFilter('brand', undefined)}
                                            className="ml-2 flex items-center"
                                        >
                                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </span>
                                )}
                                {stateFilters.category && (
                                    <span className="inline-flex items-center px-3 py-1 text-sm font-medium bg-gray-800 text-white">
                                        カテゴリ: {facets.categories.find((c) => c.slug === stateFilters.category)?.name || stateFilters.category}
                                        <button 
                                            onClick={() => updateFilter('category', undefined)}
                                            className="ml-2 flex items-center"
                                        >
                                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </span>
                                )}
                                {stateFilters.rating && (
                                    <span className="inline-flex items-center px-3 py-1 text-sm font-medium bg-gray-800 text-white">
                                        評価: {stateFilters.rating}+
                                        <button 
                                            onClick={() => updateFilter('rating', undefined)}
                                            className="ml-2 flex items-center"
                                        >
                                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </span>
                                )}
                                {stateFilters.gender && (
                                    <span className="inline-flex items-center px-3 py-1 text-sm font-medium bg-gray-800 text-white">
                                        性別: {stateFilters.gender === 'men' ? 'メンズ' : stateFilters.gender === 'women' ? 'レディース' : 'ユニセックス'}
                                        <button 
                                            onClick={() => updateFilter('gender', undefined)}
                                            className="ml-2 flex items-center"
                                        >
                                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </span>
                                )}
                                {stateFilters.size && (
                                    <span className="inline-flex items-center px-3 py-1 text-sm font-medium bg-gray-800 text-white">
                                        容量: {stateFilters.size}ml
                                        <button 
                                            onClick={() => updateFilter('size', undefined)}
                                            className="ml-2 flex items-center"
                                        >
                                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </span>
                                )}
                                {stateFilters.fragranceType && stateFilters.fragranceType.length > 0 && (
                                    <span className="inline-flex items-center px-3 py-1 text-sm font-medium bg-gray-800 text-white">
                                        香りタイプ: {stateFilters.fragranceType.join(', ')}
                                        <button 
                                            onClick={() => updateFilter('fragranceType', undefined)}
                                            className="ml-2 flex items-center"
                                        >
                                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </span>
                                )}
                                <button
                                    onClick={handleClearAllFilters}
                                    className="inline-flex items-center px-3 py-1 text-sm font-medium bg-gray-200 text-gray-800"
                                >
                                    すべてクリア
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                <div className="flex flex-col gap-6 lg:flex-row">
                    {isFilterSidebarOpen && (
                        <div className="lg:w-1/4">
                            <div className="sticky top-4 bg-white border border-gray-200 p-4">
                                <div className="mb-6">
                                    <SearchWithAutocomplete
                                        initialQuery={stateFilters.q || ''}
                                        currentFilters={stateFilters}
                                        updateFilter={updateFilter}
                                    />
                                </div>
                                <div className="mb-6 flex items-center justify-between">
                                    <h2 className="font-['Hiragino_Mincho_ProN'] text-lg font-semibold text-gray-900">フィルター</h2>
                                    <button 
                                        onClick={() => setFilterSidebarOpen(false)} 
                                        className="text-gray-500 p-1"
                                    >
                                        <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <div className="space-y-6">
                                    <BrandFilter brands={facets.brands} currentFilters={stateFilters} onFilterChange={updateFilter} />

                                    <HierarchicalCategoryFilter
                                        categories={facets.categories}
                                        currentFilters={stateFilters}
                                        onFilterChange={updateFilter}
                                    />

                                    <PriceFilter
                                        prices={facets.prices}
                                        currentFilters={stateFilters}
                                        onFilterChange={updateFilter}
                                        onClearFilter={handleClearPrice}
                                    />

                                    <RatingFilter
                                        ratings={facets.ratings}
                                        currentFilters={stateFilters}
                                        onFilterChange={updateFilter}
                                        onClearFilter={() => clearFilter('rating')}
                                    />

                                    <GenderFilter currentFilters={stateFilters} onFilterChange={updateFilter} />

                                    <SizeFilter currentFilters={stateFilters} onFilterChange={updateFilter} onClearFilter={() => clearFilter('size')} />

                                    <FragranceTypeFilter currentFilters={stateFilters} onFilterChange={updateFilter} />
                                </div>

                                <div className="mt-8 flex flex-col gap-3">
                                    <button
                                        className="w-full py-3 text-sm font-medium text-white bg-gray-800"
                                        onClick={() => {
                                            // Filter search functionality is already handled by the filter state changes
                                            // This button can be used to trigger any additional search logic if needed
                                        }}
                                    >
                                        フィルター適用
                                    </button>
                                    <button
                                        className="w-full py-3 text-sm font-medium text-gray-700 bg-gray-100"
                                        onClick={handleResetConditions}
                                    >
                                        条件をリセット
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}

                    <div className={isFilterSidebarOpen ? 'lg:w-3/4' : 'lg:w-full'}>
                        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                            {products.data?.length === 0 && (
                                <div className="col-span-full py-12 text-center">
                                    <p className="font-['Hiragino_Mincho_ProN'] text-base text-gray-600">商品が見つかりません。フィルターを調整してみてください。</p>
                                </div>
                            )}
                            {products.data?.map((p) => {
                                const priceYen = (p.price_cents ?? 0) / 100;
                                const compareAtYen = p.compare_at_cents != null ? p.compare_at_cents / 100 : null;

                                const hasSale = compareAtYen != null && priceYen < compareAtYen;
                                const cardPrice = hasSale ? compareAtYen! : priceYen;
                                const salePrice = hasSale ? priceYen : null;

                                return (
                                    <MinimalistProductCard
                                        key={p.id}
                                        product={{
                                            id: p.id,
                                            slug: p.slug,
                                            name: p.name,
                                            brand: p.brand?.name ?? undefined,
                                            price: cardPrice,
                                            salePrice: salePrice ?? undefined,
                                            imageUrl: p.image ?? undefined,
                                            imageAlt: p.name,
                                            averageRating: p.average_rating,
                                            reviewCount: p.review_count,
                                            genders: p.genders,
                                            sizes: p.sizes,
                                        }}
                                    />
                                );
                            })}
                            {isLoading && (
                                <>
                                    {Array.from({ length: 8 }).map((_, i) => (
                                        <div key={i} className="w-full max-w-xs h-64 animate-pulse border border-gray-200 bg-gray-100" />
                                    ))}
                                </>
                            )}
                        </div>

                        <nav className="mt-8 flex items-center justify-center gap-2 py-4">
                            {products.links?.map((l, i) => {
                                const href = l.url
                                    ? (() => {
                                          const u = new URL(l.url!, window.location.origin);
                                          const page = u.searchParams.get('page') ?? '';
                                          const params = new URLSearchParams({
                                              ...(stateFilters.q ? { q: String(stateFilters.q) } : {}),
                                              ...(stateFilters.brand ? { brand: String(stateFilters.brand) } : {}),
                                              ...(stateFilters.category ? { category: String(stateFilters.category) } : {}),
                                              ...(stateFilters.sort && stateFilters.sort !== '' ? { sort: stateFilters.sort } : {}),
                                              ...(stateFilters.priceMin != null ? { price_min: String(stateFilters.priceMin) } : {}),
                                              ...(stateFilters.priceMax != null ? { price_max: String(stateFilters.priceMax) } : {}),
                                              ...(stateFilters.rating ? { rating: String(stateFilters.rating) } : {}),
                                              ...(stateFilters.gender ? { gender: String(stateFilters.gender) } : {}),
                                              ...(stateFilters.size ? { size: String(stateFilters.size) } : {}),
                                              ...(stateFilters.fragranceType ? { fragranceType: stateFilters.fragranceType.join(',') } : {}),
                                          } as Record<string, string>);
                                          if (page) params.set('page', page);
                                          return `${u.pathname}?${params.toString()}`;
                                      })()
                                    : '#';

                                return (
                                    <Link
                                        key={i}
                                        href={href}
                                        className={`px-3 py-2 text-sm font-medium ${
                                            l.active 
                                                ? 'bg-gray-800 text-white' 
                                                : 'text-gray-700'
                                        } ${!l.url ? 'cursor-not-allowed text-gray-400' : ''}`}
                                        disabled={!l.url || l.active}
                                    >
                                        <span dangerouslySetInnerHTML={{ __html: l.label }} />
                                    </Link>
                                );
                            })}
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    );
}
