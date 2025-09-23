import ProductCard from '@/components/ProductCard';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import { Head, Link } from '@inertiajs/react';
import BrandFilter from '@/components/search-filters/BrandFilter';
import CategoryFilter from '@/components/search-filters/CategoryFilter';
import PriceFilter from '@/components/search-filters/PriceFilter';
import RatingFilter from '@/components/search-filters/RatingFilter';
import GenderFilter from '@/components/search-filters/GenderFilter';
import SizeFilter from '@/components/search-filters/SizeFilter';
import HierarchicalCategoryFilter from '@/components/search-filters/HierarchicalCategoryFilter';

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

export default function Index({ products, filters, facets }: Props) {
    const isLoading = useInertiaLoading();
    const allowedSort = new Set(['', 'newest', 'price_asc', 'price_desc']);
    const safeSort = (filters.sort ?? '') as string;
    const sortParam = allowedSort.has(safeSort) && safeSort !== '' ? safeSort : undefined;
    
    // Check if any filters are active
    const hasActiveFilters = filters.brand || filters.category || filters.price_min !== undefined || 
                            filters.price_max !== undefined || filters.rating || filters.gender || filters.size;
    
    // Function to clear all filters
    const clearAllFilters = () => {
        const params = new URLSearchParams();
        if (filters.q) params.set('q', filters.q);
        if (sortParam) params.set('sort', sortParam);
        return `?${params.toString()}`;
    };
    
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
                        {filters.brand && (
                            <span className="rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                ブランド: {facets.brands.find(b => b.slug === filters.brand)?.name || filters.brand}
                            </span>
                        )}
                        {filters.category && (
                            <span className="rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                カテゴリ: {facets.categories.find(c => c.slug === filters.category)?.name || filters.category}
                            </span>
                        )}
                        {filters.rating && (
                            <span className="rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                評価: {filters.rating}+
                            </span>
                        )}
                        {filters.gender && (
                            <span className="rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                性別: {filters.gender === 'men' ? 'メンズ' : filters.gender === 'women' ? 'レディース' : 'ユニセックス'}
                            </span>
                        )}
                        {filters.size && (
                            <span className="rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                容量: {filters.size}ml
                            </span>
                        )}
                        <Link
                            href={clearAllFilters()}
                            className="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                            preserveScroll
                        >
                            クリア
                        </Link>
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
                        <BrandFilter brands={facets.brands} currentFilters={filters} />
                        
                        {/* Category Filter */}
                        <HierarchicalCategoryFilter categories={facets.categories} currentFilters={filters} />
                        
                        {/* Price Filter */}
                        <PriceFilter prices={facets.prices} currentFilters={filters} />
                        
                        {/* Rating Filter */}
                        <RatingFilter ratings={facets.ratings} currentFilters={filters} />
                        
                        {/* Gender Filter */}
                        <GenderFilter currentFilters={filters} />
                        
                        {/* Size Filter */}
                        <SizeFilter currentFilters={filters} />
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
                                    ...(filters.q ? { q: String(filters.q) } : {}),
                                    ...(filters.brand ? { brand: String(filters.brand) } : {}),
                                    ...(filters.category ? { category: String(filters.category) } : {}),
                                    ...(filters.price_min != null ? { price_min: String(filters.price_min) } : {}),
                                    ...(filters.price_max != null ? { price_max: String(filters.price_max) } : {}),
                                    ...(filters.rating ? { rating: String(filters.rating) } : {}),
                                    ...(filters.gender ? { gender: String(filters.gender) } : {}),
                                    ...(filters.size ? { size: String(filters.size) } : {}),
                                    ...(s.key ? { sort: s.key } : {}),
                                }).toString()}`}
                                className={`rounded border px-2 py-1 ${(filters.sort || '') === s.key ? 'bg-black text-white' : 'hover:bg-neutral-100'}`}
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
                                          ...(filters.q ? { q: String(filters.q) } : {}),
                                          ...(filters.brand ? { brand: String(filters.brand) } : {}),
                                          ...(filters.category ? { category: String(filters.category) } : {}),
                                          ...(sortParam ? { sort: sortParam } : {}),
                                          ...(filters.price_min != null ? { price_min: String(filters.price_min) } : {}),
                                          ...(filters.price_max != null ? { price_max: String(filters.price_max) } : {}),
                                          ...(filters.rating ? { rating: String(filters.rating) } : {}),
                                          ...(filters.gender ? { gender: String(filters.gender) } : {}),
                                          ...(filters.size ? { size: String(filters.size) } : {}),
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
