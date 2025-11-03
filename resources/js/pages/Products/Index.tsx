import { HomeNavigation } from '@/components/homeNavigation';
import ProductCard from '@/components/ProductCard';
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
                    className="w-full rounded-lg border border-gray-300 px-4 py-3 pr-10 pl-12 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400"
                />
                <div className="absolute top-1/2 left-4 -translate-y-1/2 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                {query && (
                    <button onClick={clearSearch} className="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                )}
            </div>
            {showSuggestions && suggestions.length > 0 && (
                <div className="absolute z-10 mt-1 w-full rounded-lg border border-gray-300 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-800">
                    <ul>
                        {suggestions.map((suggestion, index) => (
                            <li
                                key={suggestion.id}
                                className={`cursor-pointer px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 ${index === selectedIndex ? 'bg-blue-50 dark:bg-blue-900/50' : ''}`}
                                onMouseDown={() => handleSuggestionClick(suggestion)}
                            >
                                <div className="flex items-center gap-3">
                                    {suggestion.image && (
                                        <div className="h-12 w-12 flex-shrink-0">
                                            <img
                                                src={suggestion.image}
                                                alt={suggestion.name}
                                                className="h-full w-full rounded-md object-cover"
                                                loading="lazy"
                                            />
                                        </div>
                                    )}
                                    <div className="min-w-0 flex-1">
                                        <div className="truncate font-medium">{suggestion.name}</div>
                                        <div className="truncate text-sm text-gray-500 dark:text-gray-400">
                                            {suggestion.brand?.name} • {suggestion.category?.name}
                                        </div>
                                        {suggestion.price && (
                                            <div className="text-sm font-semibold text-gray-900 dark:text-gray-100">
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
                    <div className="h-5 w-5 animate-spin rounded-full border-2 border-gray-300 border-t-blue-500" />
                </div>
            )}
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
        <div className="min-h-screen bg-white">
            <HomeNavigation />
            <div className="mx-auto max-w-[1408px] px-4 py-6">
                <Head title="商品" />

                <div className="w-full">
                    <div className="mx-auto w-full max-w-[1408px]">
                        <div className="flex h-[300px] w-full items-center bg-[#AAB4C3]">
                            <div className="ml-[34px]">
                                <h1 className="font-serif text-[60px] leading-[1.2] font-bold tracking-[-2%] text-white">商品一覧</h1>
                                <p className="mt-2 font-sans text-[20px] leading-[1.5] text-white">{products.meta?.total ?? 0}個の商品</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex h-[92px] items-center">
                    <div className="flex w-full items-center justify-between px-4">
                        <div className="flex items-center gap-4">
                            <button
                                onClick={() => setFilterSidebarOpen(!isFilterSidebarOpen)}
                                className="flex items-center justify-center gap-[10px] border border-[#AAB4C3] px-[16px] py-[16px]"
                            >
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                                    />
                                </svg>
                                <span className="font-[Sora] text-[12px] text-black">絞り込み</span>
                            </button>

                            {hasActiveFilters && (
                                <button
                                    onClick={handleClearAllFilters}
                                    className="flex w-[123px] items-center justify-center gap-[10px] border border-[#AAB4C3] px-[16px] py-[16px]"
                                >
                                    <span className="font-[Sora] text-[12px] text-black">絞り込み削除</span>
                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            )}
                        </div>

                        <div className="relative w-[200px] border border-[#AAB4C3]">
                            <div className="flex items-center gap-[10px] px-[16px] py-[8px]">
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"
                                    />
                                </svg>
                                <select
                                    className="w-[200px] appearance-none border-none bg-transparent font-[Sora] text-[12px] text-[#444444] focus:outline-none" /* 200px - 16px*2 padding - 10px gap = ~158px */
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
                            </div>
                            <div className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 transform">
                                <svg className="h-4 w-4 text-[#444444]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

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
                                    ブランド: {facets.brands.find((b) => b.slug === stateFilters.brand)?.name || stateFilters.brand}
                                </span>
                            )}
                            {stateFilters.category && (
                                <span className="rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    カテゴリ: {facets.categories.find((c) => c.slug === stateFilters.category)?.name || stateFilters.category}
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
                            {stateFilters.fragranceType && stateFilters.fragranceType.length > 0 && (
                                <span className="rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    香りタイプ: {stateFilters.fragranceType.join(', ')}
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

                <div className="flex flex-col gap-6 lg:flex-row">
                    {isFilterSidebarOpen && (
                        <div className="lg:w-1/4">
                            <div className="sticky top-4 rounded-lg border p-4">
                                <div className="mb-4">
                                    <SearchWithAutocomplete
                                        initialQuery={stateFilters.q || ''}
                                        currentFilters={stateFilters}
                                        updateFilter={updateFilter}
                                    />
                                </div>
                                <div className="mt-4 mb-4 flex items-center justify-between">
                                    <h2 className="text-lg font-semibold text-black">フィルター</h2>
                                    <button onClick={() => setFilterSidebarOpen(false)} className="text-gray-500 hover:text-gray-700">
                                        <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

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

                                <div className="mt-6 flex flex-col gap-4">
                                    <button
                                        className="flex w-full items-center justify-center rounded-lg border border-[#AAB4C3] bg-[#EAB308] py-4 text-sm text-white"
                                        onClick={() => {
                                            // Filter search functionality is already handled by the filter state changes
                                            // This button can be used to trigger any additional search logic if needed
                                        }}
                                    >
                                        フィルター検索
                                    </button>
                                    <button
                                        className="flex w-full items-center justify-center rounded-lg border border-[#AAB4C3] bg-[#EAB308] py-4 text-sm text-white"
                                        onClick={handleResetConditions}
                                    >
                                        条件をリセット
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}

                    <div className={isFilterSidebarOpen ? 'lg:w-3/4' : 'lg:w-full'}>
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {products.data?.length === 0 && (
                                <div className="col-span-full rounded-lg border p-8 text-center text-sm text-neutral-600 dark:text-neutral-300">
                                    商品が見つかりません。フィルターを調整してみてください。
                                </div>
                            )}
                            {products.data?.map((p) => {
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
                                            genders: p.genders,
                                            sizes: p.sizes,
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

                        <nav className="mt-8 flex items-center gap-2">
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
                                        className={`rounded border px-3 py-1 text-sm ${l.active ? 'bg-black text-white' : 'hover:bg-neutral-100'} ${!l.url ? 'cursor-not-allowed text-neutral-400' : ''}`}
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
