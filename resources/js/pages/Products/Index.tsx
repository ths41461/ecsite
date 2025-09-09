import ProductCard from '@/components/ProductCard';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import { Head, Link } from '@inertiajs/react';

type ProductItem = {
    id: number;
    name: string;
    slug: string;
    brand: { name?: string | null; slug?: string | null };
    image?: string | null;
    price_cents: number | null;
    compare_at_cents: number | null;
};
type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    meta: { current_page: number; last_page: number; total: number };
};
type FacetBrand = { slug: string; name: string; count: number; active?: boolean };
type FacetCategory = { slug: string; name: string; count: number; active?: boolean };
type FacetPrice = { label: string; min: number; max: number | null; count: number; active?: boolean };

type Props = {
    products: Paginated<ProductItem>;
    filters: { q?: string; category?: string; brand?: string; sort?: string; price_min?: number; price_max?: number | null };
    facets: { brands: FacetBrand[]; categories: FacetCategory[]; prices: FacetPrice[] };
};

export default function Index({ products, filters, facets }: Props) {
    const isLoading = useInertiaLoading();
    const allowedSort = new Set(['', 'newest', 'price_asc', 'price_desc']);
    const safeSort = (filters.sort ?? '') as string;
    const sortParam = allowedSort.has(safeSort) && safeSort !== '' ? safeSort : undefined;
    return (
        <div className="mx-auto max-w-7xl px-4 py-6">
            <Head title="Products" />
            <h1 className="mb-4 text-2xl font-bold">Products</h1>

            {/* Filter bar */}
            <div className="mb-6 grid gap-3 rounded-lg border p-3 md:grid-cols-3">
                <div>
                    <div className="mb-2 text-xs font-semibold text-neutral-500 uppercase">Brands</div>
                    <div className="flex flex-wrap gap-2">
                        {facets.brands.map((b) => (
                            <Link
                                key={b.slug}
                                href={`?${new URLSearchParams({
                                    ...(filters.q ? { q: String(filters.q) } : {}),
                                    ...(filters.category ? { category: String(filters.category) } : {}),
                                    ...(sortParam ? { sort: sortParam } : {}),
                                    ...(filters.price_min != null ? { price_min: String(filters.price_min) } : {}),
                                    ...(filters.price_max != null ? { price_max: String(filters.price_max) } : {}),
                                    brand: b.active ? '' : b.slug,
                                }).toString()}`}
                                className={`rounded border px-2 py-1 text-xs ${b.active ? 'bg-black text-white' : 'hover:bg-neutral-100'}`}
                                preserveScroll
                            >
                                {b.name} ({b.count})
                            </Link>
                        ))}
                    </div>
                </div>
                <div>
                    <div className="mb-2 text-xs font-semibold text-neutral-500 uppercase">Categories</div>
                    <div className="flex flex-wrap gap-2">
                        {facets.categories.map((c) => (
                            <Link
                                key={c.slug}
                                href={`?${new URLSearchParams({
                                    ...(filters.q ? { q: String(filters.q) } : {}),
                                    ...(filters.brand ? { brand: String(filters.brand) } : {}),
                                    ...(sortParam ? { sort: sortParam } : {}),
                                    ...(filters.price_min != null ? { price_min: String(filters.price_min) } : {}),
                                    ...(filters.price_max != null ? { price_max: String(filters.price_max) } : {}),
                                    category: c.active ? '' : c.slug,
                                }).toString()}`}
                                className={`rounded border px-2 py-1 text-xs ${c.active ? 'bg-black text-white' : 'hover:bg-neutral-100'}`}
                                preserveScroll
                            >
                                {c.name} ({c.count})
                            </Link>
                        ))}
                    </div>
                </div>
                <div>
                    <div className="mb-2 text-xs font-semibold text-neutral-500 uppercase">Price</div>
                    <div className="flex flex-wrap gap-2">
                        {facets.prices.map((p, i) => (
                            <Link
                                key={i}
                                href={`?${new URLSearchParams({
                                    ...(filters.q ? { q: String(filters.q) } : {}),
                                    ...(filters.brand ? { brand: String(filters.brand) } : {}),
                                    ...(filters.category ? { category: String(filters.category) } : {}),
                                    ...(sortParam ? { sort: sortParam } : {}),
                                    price_min: String(p.min),
                                    ...(p.max == null ? {} : { price_max: String(p.max) }),
                                }).toString()}`}
                                className={`rounded border px-2 py-1 text-xs ${p.active ? 'bg-black text-white' : 'hover:bg-neutral-100'}`}
                                preserveScroll
                            >
                                {p.label} ({p.count})
                            </Link>
                        ))}
                    </div>
                </div>
            </div>

            {/* Sort */}
            <div className="mb-4 flex items-center gap-2 text-sm">
                <span className="text-neutral-500">Sort:</span>
                {[
                    { key: '', label: 'Relevance/Newest' },
                    { key: 'newest', label: 'Newest' },
                    { key: 'price_asc', label: 'Price ↑' },
                    { key: 'price_desc', label: 'Price ↓' },
                ].map((s) => (
                    <Link
                        key={s.key || 'relevance'}
                        href={`?${new URLSearchParams({
                            ...(filters.q ? { q: String(filters.q) } : {}),
                            ...(filters.brand ? { brand: String(filters.brand) } : {}),
                            ...(filters.category ? { category: String(filters.category) } : {}),
                            ...(filters.price_min != null ? { price_min: String(filters.price_min) } : {}),
                            ...(filters.price_max != null ? { price_max: String(filters.price_max) } : {}),
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
            <div className="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                {products.data.length === 0 && (
                    <div className="col-span-full rounded-lg border p-8 text-center text-sm text-neutral-600 dark:text-neutral-300">
                        No products found. Try adjusting filters.
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
    );
}
