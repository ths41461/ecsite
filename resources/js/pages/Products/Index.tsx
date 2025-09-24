import ProductCard from '@/components/ProductCard';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import { Head, Link, router } from '@inertiajs/react';
import BrandFilter from '@/components/search-filters/BrandFilter';
import CategoryFilter from '@/components/search-filters/CategoryFilter';
import PriceFilter from '@/components/search-filters/PriceFilter';
import RatingFilter from '@/components/search-filters/RatingFilter';
import GenderFilter from '@/components/search-filters/GenderFilter';
import SizeFilter from '@/components/search-filters/SizeFilter';
import HierarchicalCategoryFilter from '@/components/search-filters/HierarchicalCategoryFilter';
import { useState, useEffect, useRef } from 'react';
import { useFilterState } from '@/hooks/use-filter-state';

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
};
type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    meta: { current_page: number; last_page: number; total: number };
};
type FacetBrand = { slug: string; name: string; count: number; active?: boolean };
type FacetCategory = { slug: string; name: string; count: number; active?: boolean; parent_id?: number | null; depth?: number };
type FacetPrice = { label: string; min: number; max: number | null; count: number; active?: boolean };
type FacetRating = { rating: number; label: string; count: number; active?: boolean };

type Props = {
    products: Paginated<ProductItem>;
    filters: { q?: string; category?: string; brand?: string; sort?: string; price_min?: number; price_max?: number | null; rating?: number; gender?: string; size?: number };
    facets: { brands: FacetBrand[]; categories: FacetCategory[]; prices: FacetPrice[]; ratings: FacetRating[] };
};

type SearchSuggestion = {
    id: number;
    name: string;
    slug: string;
    brand: { name?: string | null };
    category: { name?: string | null };
};

// Search component with autocomplete functionality
function SearchWithAutocomplete({ 
    initialQuery, 
    currentFilters, 
    updateFilter 
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

    // Fetch search suggestions
    const fetchSuggestions = async (searchQuery: string) => {
        if (!searchQuery.trim()) {
            setSuggestions([]);
            setShowSuggestions(false);
            return;
        }

        setLoading(true);
        
        try {
            // Fetch from backend API - adjust endpoint as needed
            const response = await fetch(`/api/search/autocomplete?q=${encodeURIComponent(searchQuery)}`, {
                headers: {
                    'Accept': 'application/json',
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

    // Handle input change with debounce
    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        setQuery(value);
        
        // Update the filter state
        updateFilter('q', value);
        
        // Clear previous timeout
        if (searchTimeoutRef.current) {
            clearTimeout(searchTimeoutRef.current);
        }

        // Set new timeout for debounced search
        searchTimeoutRef.current = setTimeout(() => {
            fetchSuggestions(value);
            setSelectedIndex(-1); // Reset selection when typing
        }, 300); // 300ms debounce
    };

    // Handle suggestion selection
    const handleSuggestionClick = (suggestion: SearchSuggestion) => {
        setQuery(suggestion.name);
        updateFilter('q', suggestion.name);
        setShowSuggestions(false);
        
        // Update URL with all current filters and the new search query
        const params = new URLSearchParams();
        params.set('q', suggestion.name);
        if (currentFilters.brand) params.set('brand', currentFilters.brand);
        if (currentFilters.category) params.set('category', currentFilters.category);
        if (currentFilters.sort) params.set('sort', currentFilters.sort);
        if (currentFilters.priceMin != null) params.set('price_min', String(currentFilters.priceMin));
        if (currentFilters.priceMax != null) params.set('price_max', String(currentFilters.priceMax));
        if (currentFilters.rating) params.set('rating', String(currentFilters.rating));
        if (currentFilters.gender) params.set('gender', currentFilters.gender);
        if (currentFilters.size) params.set('size', String(currentFilters.size));
        
        router.get(`/products?${params.toString()}`);
    };

    // Handle keyboard navigation
    const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setSelectedIndex(prev => Math.min(prev + 1, suggestions.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setSelectedIndex(prev => Math.max(prev - 1, -1));
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (selectedIndex >= 0 && suggestions[selectedIndex]) {
                handleSuggestionClick(suggestions[selectedIndex]);
            } else if (query.trim()) {
                // Update URL with all current filters and the new search query
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
                
                router.get(`/products?${params.toString()}`);
                setShowSuggestions(false);
            }
        } else if (e.key === 'Escape') {
            setShowSuggestions(false);
            inputRef.current?.blur();
        }
    };

    // Handle input focus
    const handleInputFocus = () => {
        if (query && suggestions.length > 0) {
            setShowSuggestions(true);
        }
    };

    // Clear search
    const clearSearch = () => {
        setQuery('');
        updateFilter('q', '');
        setSuggestions([]);
        setShowSuggestions(false);
        
        // Update URL preserving other filters
        const params = new URLSearchParams();
        if (currentFilters.brand) params.set('brand', currentFilters.brand);
        if (currentFilters.category) params.set('category', currentFilters.category);
        if (currentFilters.sort) params.set('sort', currentFilters.sort);
        if (currentFilters.priceMin != null) params.set('price_min', String(currentFilters.priceMin));
        if (currentFilters.priceMax != null) params.set('price_max', String(currentFilters.priceMax));
        if (currentFilters.rating) params.set('rating', String(currentFilters.rating));
        if (currentFilters.gender) params.set('gender', currentFilters.gender);
        if (currentFilters.size) params.set('size', String(currentFilters.size));
        
        const url = params.toString() ? `/products?${params.toString()}` : '/products';
        router.get(url);
        inputRef.current?.focus();
    };

    // Update local state when currentFilters.q changes from outside
    useEffect(() => {
        if (currentFilters.q !== query) {
            setQuery(currentFilters.q || '');
        }
    }, [currentFilters.q]);

    // Cleanup on unmount
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
                    placeholder="商品を検索..."
                    className="w-full rounded-lg border border-gray-300 px-4 py-3 pl-12 pr-10 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400"
                />
                <div className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                {query && (
                    <button
                        onClick={clearSearch}
                        className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                )}
            </div>

            {/* Autocomplete suggestions dropdown */}
            {showSuggestions && suggestions.length > 0 && (
                <div className="absolute z-10 mt-1 w-full rounded-lg border border-gray-300 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-800">
                    <ul>
                        {suggestions.map((suggestion, index) => (
                            <li
                                key={suggestion.id}
                                className={`cursor-pointer px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 ${
                                    index === selectedIndex ? 'bg-blue-50 dark:bg-blue-900/50' : ''
                                }`}
                                onClick={() => handleSuggestionClick(suggestion)}
                            >
                                <div className="font-medium">{suggestion.name}</div>
                                <div className="text-sm text-gray-500 dark:text-gray-400">
                                    {suggestion.brand?.name} • {suggestion.category?.name}
                                </div>
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            {/* Loading indicator */}
            {loading && (
                <div className="absolute right-10 top-1/2 -translate-y-1/2">
                    <div className="h-5 w-5 animate-spin rounded-full border-2 border-gray-300 border-t-blue-500" />
                </div>
            )}

            {/* No results message */}
            {showSuggestions && suggestions.length === 0 && query && !loading && (
                <div className="absolute z-10 mt-1 w-full rounded-lg border border-gray-300 bg-white px-4 py-3 dark:border-gray-600 dark:bg-gray-800">
                    検索結果が見つかりません
                </div>
            )}
        </div>
    );
}

export default function Index({ products, filters, facets }: Props) {
    const isLoading = useInertiaLoading();
    
    // Initialize filter state with server-provided filters
    const { filters: stateFilters, updateFilter, clearAllFilters: clearStateFilters } = useFilterState({ 
        initialFilters: {
            q: filters.q || undefined,
            brand: filters.brand || undefined,
            category: filters.category || undefined,
            priceMin: filters.price_min,
            priceMax: filters.price_max,
            rating: filters.rating,
            gender: filters.gender || undefined,
            size: filters.size,
            sort: filters.sort || undefined,
        }
    });
    
    const allowedSort = new Set(['', 'newest', 'price_asc', 'price_desc']);
    const safeSort = (stateFilters.sort ?? '') as string;
    const sortParam = allowedSort.has(safeSort) && safeSort !== '' ? safeSort : undefined;
    
    const handleClearAllFilters = () => {
        clearStateFilters();
        router.get('/products');
    };
    
    // Check if any filters are active
    const hasActiveFilters = stateFilters.brand || stateFilters.category || stateFilters.priceMin !== undefined || 
                            stateFilters.priceMax !== undefined || stateFilters.rating || stateFilters.gender || stateFilters.size;
    
    
    
    return (
        <div className="mx-auto max-w-[1408px] px-4 py-6">
            <Head title="商品" />

            {/* Hero Banner - 1408px × 300px */}
            <div className="mb-8 w-full">
                <div className="mx-auto w-full max-w-[1408px]">
                    <div className="flex h-[300px] w-full items-center justify-center rounded-lg bg-gradient-to-r from-amber-100 to-orange-100 dark:from-amber-900/20 dark:to-orange-900/20">
                        <div className="text-center">
                            <h2 className="mb-4 text-3xl font-bold text-amber-800 dark:text-amber-200">特別キャンペーン</h2>
                            <p className="text-xl text-amber-600 dark:text-amber-300">期間限定オファーをチェック</p>
                        </div>
                    </div>
                </div>
            </div>

            <h1 className="mb-4 text-2xl font-bold">商品</h1>

            {/* Search Bar */}
            <div className="mb-6 relative">
                <SearchWithAutocomplete 
                    initialQuery={stateFilters.q || ''} 
                    currentFilters={stateFilters}
                    updateFilter={updateFilter}
                />
            </div>

            {/* Filter Container */}
            <div className="mb-6 flex items-center justify-between">
                {/* Filter Toggle Button */}
                <button className="flex items-center gap-2 rounded border px-3 py-2 text-sm hover:bg-neutral-100">
                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                        />
                    </svg>
                    <span>絞り込み</span>
                </button>

                {/* Sort Dropdown */}
                <div className="flex items-center gap-2 text-sm">
                    <span className="text-neutral-500">並び替え:</span>
                    <select className="rounded border px-2 py-1 text-sm">
                        <option>新着</option>
                        <option>ベストセラー</option>
                        <option>アルファベット順</option>
                        <option>A–Z</option>
                        <option>価格の安い順</option>
                        <option>価格の高い順</option>
                        <option>古い商品順</option>
                    </select>
                </div>
            </div>

            {/* Active Filters Bar */}
            {hasActiveFilters && (
                <div className="mb-4 flex items-center gap-2">
                    <span className="text-sm text-neutral-600 dark:text-neutral-400">選択中のフィルター:</span>
                    <div className="flex flex-wrap gap-2">
                        {stateFilters.q && (
                            <span className="rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                検索: {stateFilters.q}
                            </span>
                        )}
                        {stateFilters.brand && (
                            <span className="rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                ブランド: {facets.brands.find(b => b.slug === stateFilters.brand)?.name || stateFilters.brand}
                            </span>
                        )}
                        {stateFilters.category && (
                            <span className="rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                カテゴリ: {facets.categories.find(c => c.slug === stateFilters.category)?.name || stateFilters.category}
                            </span>
                        )}
                        {stateFilters.rating && (
                            <span className="rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                評価: {stateFilters.rating}+
                            </span>
                        )}
                        {stateFilters.gender && (
                            <span className="rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                性別: {stateFilters.gender === 'men' ? 'メンズ' : stateFilters.gender === 'women' ? 'レディース' : 'ユニセックス'}
                            </span>
                        )}
                        {stateFilters.size && (
                            <span className="rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                容量: {stateFilters.size}ml
                            </span>
                        )}
                        <button
                            onClick={handleClearAllFilters}
                            className="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                        >
                            クリア
                        </button>
                    </div>
                </div>
            )}

            {/* Filter Sidebar and Results */}
            <div className="flex flex-col lg:flex-row gap-6">
                {/* Filter Sidebar */}
                <div className="lg:w-1/4">
                    <div className="sticky top-4 rounded-lg border p-4">
                        <h2 className="mb-4 text-lg font-semibold">フィルター</h2>
                        
                        {/* Brand Filter */}
                        <BrandFilter brands={facets.brands} currentFilters={{
                            q: stateFilters.q,
                            brand: stateFilters.brand,
                            category: stateFilters.category,
                            sort: stateFilters.sort,
                            price_min: stateFilters.priceMin,
                            price_max: stateFilters.priceMax,
                        }} />
                        
                        {/* Category Filter */}
                        <HierarchicalCategoryFilter categories={facets.categories} currentFilters={{
                            q: stateFilters.q,
                            brand: stateFilters.brand,
                            category: stateFilters.category,
                            sort: stateFilters.sort,
                            price_min: stateFilters.priceMin,
                            price_max: stateFilters.priceMax,
                        }} />
                        
                        {/* Price Filter */}
                        <PriceFilter prices={facets.prices} currentFilters={{
                            q: stateFilters.q,
                            brand: stateFilters.brand,
                            category: stateFilters.category,
                            sort: stateFilters.sort,
                            price_min: stateFilters.priceMin,
                            price_max: stateFilters.priceMax,
                        }} />
                        
                        {/* Rating Filter */}
                        <RatingFilter ratings={facets.ratings} currentFilters={{
                            q: stateFilters.q,
                            brand: stateFilters.brand,
                            category: stateFilters.category,
                            sort: stateFilters.sort,
                            price_min: stateFilters.priceMin,
                            price_max: stateFilters.priceMax,
                            rating: stateFilters.rating,
                        }} />
                        
                        {/* Gender Filter */}
                        <GenderFilter currentFilters={{
                            q: stateFilters.q,
                            brand: stateFilters.brand,
                            category: stateFilters.category,
                            sort: stateFilters.sort,
                            price_min: stateFilters.priceMin,
                            price_max: stateFilters.priceMax,
                            gender: stateFilters.gender,
                        }} />
                        
                        {/* Size Filter */}
                        <SizeFilter currentFilters={{
                            q: stateFilters.q,
                            brand: stateFilters.brand,
                            category: stateFilters.category,
                            sort: stateFilters.sort,
                            price_min: stateFilters.priceMin,
                            price_max: stateFilters.priceMax,
                            size: stateFilters.size,
                        }} />
                    </div>
                </div>

                {/* Results Area */}
                <div className="lg:w-3/4">
                    {/* Sort */}
                    <div className="mb-4 flex items-center gap-2 text-sm">
                        <span className="text-neutral-500">並び替え:</span>
                        {[
                            { key: '', label: '関連性/新着' },
                            { key: 'newest', label: '新着' },
                            { key: 'price_asc', label: '価格 ↑' },
                            { key: 'price_desc', label: '価格 ↓' },
                        ].map((s) => (
                            <Link
                                key={s.key || 'relevance'}
                                href={`?${new URLSearchParams({
                                    ...(stateFilters.q ? { q: String(stateFilters.q) } : {}),
                                    ...(stateFilters.brand ? { brand: String(stateFilters.brand) } : {}),
                                    ...(stateFilters.category ? { category: String(stateFilters.category) } : {}),
                                    ...(stateFilters.priceMin != null ? { price_min: String(stateFilters.priceMin) } : {}),
                                    ...(stateFilters.priceMax != null ? { price_max: String(stateFilters.priceMax) } : {}),
                                    ...(stateFilters.rating ? { rating: String(stateFilters.rating) } : {}),
                                    ...(stateFilters.gender ? { gender: String(stateFilters.gender) } : {}),
                                    ...(stateFilters.size ? { size: String(stateFilters.size) } : {}),
                                    ...(s.key && s.key !== '' ? { sort: s.key } : {}),
                                }).toString()}`}
                                className={`rounded border px-2 py-1 ${(stateFilters.sort || '') === s.key ? 'bg-black text-white' : 'hover:bg-neutral-100'}`}
                                preserveScroll
                            >
                                {s.label}
                            </Link>
                        ))}
                    </div>

                    {/* Grid */}
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {products.data.length === 0 && (
                            <div className="col-span-full rounded-lg border p-8 text-center text-sm text-neutral-600 dark:text-neutral-300">
                                商品が見つかりません。フィルターを調整してみてください。
                            </div>
                        )}
                        {products.data.map((p) => {
                            const priceYen = (p.price_cents ?? 0) / 100;
                            const compareAtYen = p.compare_at_cents != null ? p.compare_at_cents / 100 : null;

                            const hasSale = compareAtYen != null && priceYen < compareAtYen;
                            const cardPrice = hasSale ? compareAtYen! : priceYen;
                            const salePrice = hasSale ? priceYen : null;

                            return (
                                <ProductCard
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
                                        genders: p.genders, // Add gender information
                                        sizes: p.sizes,     // Add size information
                                    }}
                                />
                            );
                        })}
                        {isLoading && (
                            <>
                                {Array.from({ length: 8 }).map((_, i) => (
                                    <div key={i} className="h-64 animate-pulse rounded-2xl border bg-neutral-100 dark:bg-neutral-800" />
                                ))}
                            </>
                        )}
                    </div>

                    {/* Pagination */}
                    <nav className="mt-8 flex items-center gap-2">
                        {products.links.map((l, i) => {
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
                                      } as Record<string, string>);
                                      if (page) params.set('page', page);
                                      return `${u.pathname}?${params.toString()}`;
                                  })()
                                : '#';

                            return (
                                <Link
                                    key={i}
                                    href={href}
                                    className={`rounded border px-3 py-1 text-sm ${l.active ? 'bg-black text-white' : 'hover:bg-neutral-100'}`}
                                    preserveScroll
                                >
                                    <span dangerouslySetInnerHTML={{ __html: l.label }} />
                                </Link>
                            );
                        })}
                    </nav>
                </div>
            </div>
        </div>
    );
}
