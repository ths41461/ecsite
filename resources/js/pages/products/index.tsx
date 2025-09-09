import { Head, Link } from '@inertiajs/react';
// If your ProductCard exists at this path (per your note), import it:
import ProductCard from '@/components/ProductCard';

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

type Props = {
  products: Paginated<ProductItem>;
  filters: { q?: string; category?: string; brand?: string; sort?: string };
  facets: Record<string, unknown>;
};

export default function Index({ products }: Props) {
  return (
    <div className="mx-auto max-w-7xl px-4 py-6">
      <Head title="Products" />
      <h1 className="mb-4 text-2xl font-bold">Products</h1>

      {/* Grid */}
      <div className="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
        {products.data.map((p) => {
          const price = Math.round(((p.compare_at_cents ?? p.price_cents) ?? 0) / 100);
          const salePrice = p.compare_at_cents ? Math.round(((p.price_cents ?? 0)) / 100) : undefined;
          return (
            <ProductCard
              key={p.id}
              product={{
                id: p.id,
                slug: p.slug,
                name: p.name,
                brand: p.brand?.name ?? null,
                price,
                salePrice,
                imageUrl: p.image ?? undefined,
                imageAlt: p.name,
              }}
            />
          );
        })}
      </div>

      {/* Paginator (very simple first) */}
      <nav className="mt-8 flex items-center gap-2">
        {products.links.map((l, i) => (
          <Link
            key={i}
            href={l.url ?? '#'}
            className={`rounded border px-3 py-1 text-sm ${
              l.active ? 'bg-black text-white' : 'hover:bg-neutral-100'
            }`}
            preserveScroll
          >
            {/* strip HTML entities if any */}
            <span dangerouslySetInnerHTML={{ __html: l.label }} />
          </Link>
        ))}
      </nav>
    </div>
  );
}
