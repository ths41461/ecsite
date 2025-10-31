import { Head } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { HomeNavigation } from '@/components/homeNavigation';

type ProductRef = { id: number; name: string; slug: string };
type LineNotice = { code: 'qty_clamped_to_available'; requested: number; available: number };
type Line = {
    line_id: string;
    variant_id: number;
    sku: string;
    product: ProductRef;
    price_cents: number;
    compare_at_cents: number | null;
    qty: number;
    managed: boolean;
    available_qty: number | null;
    line_total_cents: number;
    savings_cents: number;
    stock_badge: string;
    notice?: LineNotice;
};
type Cart = {
    lines: Line[];
    subtotal_cents: number;
    savings_cents: number;
    tax_cents?: number;
    total_cents: number;
    currency: string; // 'JPY'
    coupon_code?: string | null;
    coupon_discount_cents?: number;
    coupon_summary?: string;
    coupon_line_ids?: string[];
    coupon_line_names?: string[];
};

type PageProps = { initialCart: Cart };

function yen(cents: number) {
    return (cents / 100).toLocaleString(undefined, { style: 'currency', currency: 'JPY' });
}

// Because we're not using axios here, grab the CSRF token like you did on PDP
function getCookie(name: string) {
    const parts = document.cookie.split('; ').map((c) => c.split('='));
    const found = parts.find(([k]) => k === name);
    return found ? decodeURIComponent(found[1] ?? '') : null;
}
function xsrfHeaders(): HeadersInit {
    const xsrf = getCookie('XSRF-TOKEN');
    return {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
    };
}

export default function CartIndex({ initialCart }: PageProps) {
    const [cart, setCart] = useState<Cart>(initialCart);
    const [busyLine, setBusyLine] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);
    const [couponInput, setCouponInput] = useState('');
    const [couponBusy, setCouponBusy] = useState(false);
    const [couponError, setCouponError] = useState<string | null>(null);
    const [couponNotice, setCouponNotice] = useState<string | null>(null);

    // Fetch fresh cart from server (JSON mode) — used after mutations or manual refresh
    async function refreshCart() {
        setLoading(true);
        try {
            const res = await fetch('/cart', {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            const data: Cart = await res.json();
            setCart(data);
            // Update localStorage to notify other tabs/components
            try {
                if (typeof window !== 'undefined' && window.localStorage) {
                    localStorage.setItem('cart-state', JSON.stringify(data));
                }
                
                // Dispatch custom event to notify same-tab components
                window.dispatchEvent(new CustomEvent('cart-updated', { detail: { cart: data } }));
            } catch (error) {
                console.error('Failed to update cart in localStorage:', error);
            }
        } finally {
            setLoading(false);
        }
    }

    async function updateQty(line: Line, qty: number) {
        setBusyLine(line.line_id);
        try {
            const res = await fetch(`/cart/${encodeURIComponent(line.line_id)}`, {
                method: 'PATCH',
                headers: xsrfHeaders(),
                credentials: 'same-origin',
                body: JSON.stringify({ qty }),
            });
            const data: Cart = await res.json();
            setCart(data);
            // Update localStorage to notify other tabs/components
            try {
                if (typeof window !== 'undefined' && window.localStorage) {
                    localStorage.setItem('cart-state', JSON.stringify(data));
                }
                
                // Dispatch custom event to notify same-tab components
                window.dispatchEvent(new CustomEvent('cart-updated', { detail: { cart: data } }));
            } catch (error) {
                console.error('Failed to update cart in localStorage:', error);
            }
        } finally {
            setBusyLine(null);
        }
    }

    async function removeLine(line: Line) {
        setBusyLine(line.line_id);
        try {
            const res = await fetch(`/cart/${encodeURIComponent(line.line_id)}`, {
                method: 'DELETE',
                headers: xsrfHeaders(),
                credentials: 'same-origin',
            });
            const data: Cart = await res.json();
            setCart(data);
            // Update localStorage to notify other tabs/components
            try {
                if (typeof window !== 'undefined' && window.localStorage) {
                    localStorage.setItem('cart-state', JSON.stringify(data));
                }
                
                // Dispatch custom event to notify same-tab components
                window.dispatchEvent(new CustomEvent('cart-updated', { detail: { cart: data } }));
            } catch (error) {
                console.error('Failed to update cart in localStorage:', error);
            }
        } finally {
            setBusyLine(null);
        }
    }

    useEffect(() => {
        setCouponInput(cart.coupon_code ?? '');
    }, [cart.coupon_code]);

    async function applyCoupon() {
        setCouponError(null);
        setCouponNotice(null);
        setCouponBusy(true);
        try {
            const res = await fetch('/cart/coupon', {
                method: 'POST',
                headers: xsrfHeaders(),
                credentials: 'same-origin',
                body: JSON.stringify({ code: couponInput }),
            });
            const data = await res.json();
            if (!res.ok) {
                let msg = data?.errors?.code?.[0] || data?.message || 'クーポンの適用に失敗しました';
                if (msg === 'Coupon not currently valid.') {
                    msg = `${msg} クーポンの開始日時と終了日時を確認してください。`;
                }
                setCouponError(msg);
                return;
            }
            const nextCart = data as Cart;
            setCart(nextCart);
            // Update localStorage to notify other tabs/components
            try {
                if (typeof window !== 'undefined' && window.localStorage) {
                    localStorage.setItem('cart-state', JSON.stringify(nextCart));
                }
                
                // Dispatch custom event to notify same-tab components
                window.dispatchEvent(new CustomEvent('cart-updated', { detail: { cart: nextCart } }));
            } catch (error) {
                console.error('Failed to update cart in localStorage:', error);
            }
            setCouponNotice('クーポンを適用しました');
            setCouponInput(nextCart.coupon_code ?? '');
        } catch (e: any) {
            const fallback = e?.message || 'クーポンの適用に失敗しました';
            const msg = fallback === 'Coupon not currently valid.'
                ? `${fallback} クーポンの開始日時と終了日時を確認してください。`
                : fallback;
            setCouponError(msg);
        } finally {
            setCouponBusy(false);
        }
    }

    async function removeCouponFromCart() {
        setCouponError(null);
        setCouponNotice(null);
        setCouponBusy(true);
        try {
            const res = await fetch('/cart/coupon', {
                method: 'DELETE',
                headers: xsrfHeaders(),
                credentials: 'same-origin',
            });
            if (!res.ok) {
                setCouponError('クーポンの削除に失敗しました');
                return;
            }
            const data: Cart = await res.json();
            setCart(data);
            // Update localStorage to notify other tabs/components
            try {
                if (typeof window !== 'undefined' && window.localStorage) {
                    localStorage.setItem('cart-state', JSON.stringify(data));
                }
                
                // Dispatch custom event to notify same-tab components
                window.dispatchEvent(new CustomEvent('cart-updated', { detail: { cart: data } }));
            } catch (error) {
                console.error('Failed to update cart in localStorage:', error);
            }
            setCouponNotice('クーポンを削除しました');
        } catch (e: any) {
            setCouponError(e?.message || 'クーポンの削除に失敗しました');
        } finally {
            setCouponBusy(false);
        }
    }

    // Derived values
    const hasItems = cart.lines.length > 0;
    const couponLineIdSet = useMemo(() => new Set(cart.coupon_line_ids ?? []), [cart.coupon_line_ids]);

    return (
        <div className="min-h-screen bg-white">
            <HomeNavigation />
            <div className="mx-auto max-w-5xl px-4 py-6">
                <Head title="ショッピングカート" />

                <div className="mb-6 flex items-end justify-between">
                <div>
                    <h1 className="text-2xl font-semibold">ショッピングカート</h1>
                    <p className="text-sm text-neutral-500">{hasItems ? `${cart.lines.length} 個の商品` : '商品がありません'}</p>
                </div>
                <button
                    onClick={refreshCart}
                    disabled={loading}
                    className="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {loading ? '更新中…' : '更新'}
                </button>
            </div>

            {!hasItems && (
                <div className="rounded-xl border p-6 text-center">
                    <p className="mb-4 text-neutral-600">カートに商品がありません。</p>
                    <a href="/products" className="inline-block rounded-lg bg-rose-600 px-4 py-2 text-white hover:bg-rose-700">
                        買い物を続ける
                    </a>
                </div>
            )}

            {hasItems && (
                <div className="grid gap-6 md:grid-cols-[1fr_320px]">
                    {/* Lines */}
                    <div className="space-y-4">
                        {cart.lines.map((line) => {
                            const clamped = line.notice?.code === 'qty_clamped_to_available';
                            const couponApplied = couponLineIdSet.has(line.line_id);

                            return (
                                <div key={line.line_id} className="rounded-xl border p-4">
                                    {/* header: product + sku */}
                                    <div className="mb-2 flex items-center justify-between">
                                        <div>
                                            <a href={`/products/${line.product.slug}`} className="font-medium hover:underline">
                                                {line.product.name}
                                            </a>
                                            <div className="text-xs text-neutral-500">SKU: {line.sku}</div>
                                        </div>
                                        <button
                                            onClick={() => removeLine(line)}
                                            disabled={busyLine === line.line_id}
                                            className="rounded-lg border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50 disabled:cursor-not-allowed"
                                        >
                                            削除
                                        </button>
                                    </div>

                                    {/* notice if qty clamped */}
                                    {clamped && (
                                        <div className="mb-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">
                                            {line.notice!.requested} 個リクエストしましたが、在庫は {line.notice!.available} 個のみです。数量を調整しました。
                                        </div>
                                    )}

                                    {/* qty + price */}
                                    <div className="flex items-end justify-between gap-4">
                                        <div>
                                            <div className="mb-1 text-xs text-neutral-500">{line.stock_badge}</div>
                                            <div className="flex items-center gap-2">
                                                <button
                                                    onClick={() => updateQty(line, Math.max(0, line.qty - 1))}
                                                    disabled={busyLine === line.line_id}
                                                    className="rounded-lg border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50 disabled:cursor-not-allowed"
                                                    aria-label="数量を減らす"
                                                >
                                                    -
                                                </button>
                                                <span className="w-10 text-center text-sm font-medium">{line.qty}</span>
                                                <button
                                                    onClick={() => updateQty(line, Math.min(20, line.qty + 1))}
                                                    disabled={busyLine === line.line_id}
                                                    className="rounded-lg border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50 disabled:cursor-not-allowed"
                                                    aria-label="数量を増やす"
                                                >
                                                    +
                                                </button>
                                            </div>
                                        </div>

                                        <div className="text-right">
                                            {line.compare_at_cents != null && line.compare_at_cents > line.price_cents && (
                                                <div className="text-xs text-neutral-500 line-through">{yen(line.compare_at_cents)}</div>
                                            )}
                                            <div className="text-lg font-semibold">{yen(line.price_cents)}</div>
                                            <div className="text-sm text-neutral-600">小計: {yen(line.line_total_cents)}</div>
                                            {couponApplied && <div className="text-xs font-medium text-emerald-600">クーポン適用</div>}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {/* Summary */}
        <aside className="h-fit rounded-xl border p-4">
            <h2 className="mb-3 text-lg font-semibold">合計</h2>
            <div className="mb-1 flex items-center justify-between text-sm">
                            <span>小計</span>
                            <span>{yen(cart.subtotal_cents)}</span>
                        </div>
                        {cart.savings_cents > 0 && (
                            <div className="mb-1 flex items-center justify-between text-sm text-emerald-700">
                                <span>割引</span>
                                <span>-{yen(cart.savings_cents)}</span>
                            </div>
                        )}
                        {(cart.tax_cents ?? 0) > 0 && (
                            <div className="mb-1 flex items-center justify-between text-sm">
                                <span>税金</span>
                                <span>{yen(cart.tax_cents ?? 0)}</span>
                            </div>
                        )}
                        {cart.coupon_code && (
                            <div className="mb-1 text-sm">
                                <div className="mb-1 flex items-center justify-between">
                                    <span>
                                        クーポン
                                        <span className="ml-2 rounded-full bg-neutral-100 px-2 py-0.5 text-xs text-neutral-700">
                                            {cart.coupon_code}
                                        </span>
                                    </span>
                                    <div className="flex items-center gap-2">
                                        <span className="text-rose-700">
                                            -{yen(cart.coupon_discount_cents || 0)}
                                        </span>
                                        <button
                                            type="button"
                                            onClick={removeCouponFromCart}
                                            disabled={couponBusy}
                                            className="rounded-md border border-neutral-300 px-2 py-0.5 text-xs text-neutral-600 hover:bg-neutral-100 disabled:cursor-not-allowed"
                                        >
                                            削除
                                        </button>
                                    </div>
                                </div>
                                {cart.coupon_summary && (
                                    <div className="text-xs text-neutral-500">{cart.coupon_summary}</div>
                                )}
                                {(cart.coupon_line_names?.length ?? 0) > 0 && (
                                    <div className="text-xs text-neutral-500">
                                        {cart.coupon_line_names?.[0] === 'すべての商品'
                                            ? 'カート内のすべての商品に適用'
                                            : `適用対象: ${cart.coupon_line_names?.join(', ')}`}
                                    </div>
                                )}
                            </div>
                        )}
                        <div className="mt-2 border-t pt-2">
                            <div className="flex items-center justify-between text-base font-semibold">
                                <span>合計</span>
                                <span>{yen(cart.total_cents)}</span>
                            </div>
                            <p className="mt-1 text-xs text-neutral-500">税込み。配送料はチェックアウト時に計算されます。</p>
                        </div>

                        {/* Coupon entry */}
                        <div className="mt-4 rounded-lg border border-neutral-200 p-3">
                            <div className="mb-2 flex items-center justify-between text-sm font-medium">
                                <span>クーポンコードをお持ちですか？</span>
                                {cart.coupon_code && (
                                    <button
                                        type="button"
                                        onClick={removeCouponFromCart}
                                        disabled={couponBusy}
                                        className="text-xs text-neutral-600 hover:underline disabled:cursor-not-allowed"
                                    >
                                        現在のクーポンを削除
                                    </button>
                                )}
                            </div>
                            <div className="flex items-center gap-2">
                                <input
                                    value={couponInput}
                                    onChange={(e) => setCouponInput(e.target.value)}
                                    placeholder="コードを入力"
                                    className="flex-1 rounded-md border px-3 py-2 text-sm"
                                />
                                <button
                                    type="button"
                                    onClick={applyCoupon}
                                    disabled={couponBusy || !couponInput.trim()}
                                    className="rounded-md bg-neutral-800 px-3 py-2 text-sm text-white hover:bg-neutral-900 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    適用
                                </button>
                            </div>
                            {cart.coupon_code && couponInput !== cart.coupon_code && (
                                <p className="mt-2 text-xs text-neutral-500">
                                    新しいコードを入力して「適用」ボタンを押すと、現在のクーポンを置き換えます。
                                </p>
                            )}
                        </div>

                        {couponError && (
                            <div className="mt-3 rounded-md bg-rose-50 px-3 py-2 text-xs text-rose-700">{couponError}</div>
                        )}
                        {couponNotice && !couponError && (
                            <div className="mt-3 rounded-md bg-emerald-50 px-3 py-2 text-xs text-emerald-700">{couponNotice}</div>
                        )}

                        <div className="mt-4">
                            <a
                                href="/checkout"
                                className="block w-full rounded-lg bg-rose-600 px-4 py-3 text-center font-medium text-white hover:bg-rose-700"
                            >
                                チェックアウトに進む
                            </a>
                        </div>

                        <div className="mt-2 text-center">
                            <a href="/products" className="text-sm text-neutral-600 hover:underline">
                                買い物を続ける
                            </a>
                        </div>
                    </aside>
                </div>
            )}
        </div>
        </div>
    );
}