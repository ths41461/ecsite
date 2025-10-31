import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { HomeNavigation } from '@/components/homeNavigation';

type Item = { id: number; name: string; slug: string; image?: string | null };
type Props = { items: Item[]; count: number };

function xsrfHeaders(): HeadersInit {
    const parts = document.cookie.split('; ').map((c) => c.split('='));
    const found = parts.find(([k]) => k === 'XSRF-TOKEN');
    const xsrf = found ? decodeURIComponent(found[1] ?? '') : null;
    return {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
    };
}

export default function Index({ items: initialItems, count }: Props) {
    const [items, setItems] = useState<Item[]>(initialItems);

    async function removeItem(id: number) {
        const res = await fetch(`/wishlist/${id}`, {
            method: 'DELETE',
            headers: xsrfHeaders(),
            credentials: 'same-origin',
        });
        if (res.ok) {
            setItems((prev) => prev.filter((i) => i.id !== id));
        }
    }

    return (
        <div className="min-h-screen bg-white">
            <HomeNavigation />
            <div className="mx-auto max-w-5xl px-4 py-6">
                <Head title="お気に入り" />
                <h1 className="mb-4 text-2xl font-semibold">お気に入り</h1>

            {items.length === 0 ? (
                <div className="rounded-lg border p-6 text-sm text-neutral-600">
                    お気に入りリストが空です。{' '}
                    <Link href="/products" className="text-rose-600 underline">
                        商品をブラウズ
                    </Link>
                    してください。
                </div>
            ) : (
                <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                    {items.map((p) => (
                        <div key={p.id} className="rounded-xl border p-2">
                            <Link href={`/products/${p.slug}`}>
                                {p.image ? (
                                    <img src={p.image} alt={p.name} className="aspect-square w-full rounded-lg object-cover" />
                                ) : (
                                    <div className="aspect-square w-full rounded-lg bg-neutral-100" />
                                )}
                                <div className="mt-2 text-sm font-medium">{p.name}</div>
                            </Link>
                            <button
                                onClick={() => removeItem(p.id)}
                                className="mt-2 w-full rounded-lg border px-3 py-2 text-sm hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-800"
                            >
                                削除
                            </button>
                        </div>
                    ))}
                </div>
            )}
        </div>
        </div>
    );
}