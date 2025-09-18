import { Head } from '@inertiajs/react';
import { useState } from 'react';

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
            const res = await fetch('/cart', { headers: { Accept: 'application/json' } });
            const data: Cart = await res.json();
            setCart(data);
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
                body: JSON.stringify({ qty }),
            });
            const data: Cart = await res.json();
            setCart(data);
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
            });
            const data: Cart = await res.json();
            setCart(data);
        } finally {
            setBusyLine(null);
        }
    }

    async function applyCoupon() {
        setCouponError(null);
        setCouponNotice(null);
        setCouponBusy(true);
        try {
            const res = await fetch('/cart/coupon', {
                method: 'POST',
                headers: xsrfHeaders(),
                body: JSON.stringify({ code: couponInput }),
            });
            const data = await res.json();
            if (!res.ok) {
                let msg = data?.errors?.code?.[0] || data?.message || 'Failed to apply coupon';
                if (msg === 'Coupon not currently valid.') {
                    msg = `${msg} Double-check that the coupon has already started and has not yet expired.`;
                }
                setCouponError(msg);
                return;
            }
            setCart(data as Cart);
            setCouponNotice('Coupon applied');
            setCouponInput('');
        } catch (e: any) {
            const fallback = e?.message || 'Failed to apply coupon';
            const msg = fallback === 'Coupon not currently valid.'
                ? `${fallback} Double-check that the coupon has already started and has not yet expired.`
                : fallback;
            setCouponError(msg);
        } finally {
            setCouponBusy(false);
        }
    }

    async function removeCoupon() {
        setCouponError(null);
        setCouponNotice(null);
        setCouponBusy(true);
        try {
            const res = await fetch('/cart/coupon', { method: 'DELETE', headers: xsrfHeaders() });
            const data: Cart = await res.json();
            setCart(data);
            setCouponNotice('Coupon removed');
        } catch (e: any) {
            setCouponError(e?.message || 'Failed to remove coupon');
        } finally {
            setCouponBusy(false);
        }
    }

    // Derived values
    const hasItems = cart.lines.length > 0;

    return (
        <div className="mx-auto max-w-5xl px-4 py-6">
            <Head title="Your Cart" />

            <div className="mb-6 flex items-end justify-between">
                <div>
                    <h1 className="text-2xl font-semibold">Your Cart</h1>
                    <p className="text-sm text-neutral-500">{hasItems ? `${cart.lines.length} item(s)` : 'No items yet'}</p>
                </div>
                <button
                    onClick={refreshCart}
                    disabled={loading}
                    className="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {loading ? 'Refreshing…' : 'Refresh'}
                </button>
            </div>

            {!hasItems && (
                <div className="rounded-xl border p-6 text-center">
                    <p className="mb-4 text-neutral-600">Your cart is empty.</p>
                    <a href="/products" className="inline-block rounded-lg bg-rose-600 px-4 py-2 text-white hover:bg-rose-700">
                        Continue shopping
                    </a>
                </div>
            )}

            {hasItems && (
                <div className="grid gap-6 md:grid-cols-[1fr_320px]">
                    {/* Lines */}
                    <div className="space-y-4">
                        {cart.lines.map((line) => {
                            const clamped = line.notice?.code === 'qty_clamped_to_available';

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
                                            Remove
                                        </button>
                                    </div>

                                    {/* notice if qty clamped */}
                                    {clamped && (
                                        <div className="mb-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">
                                            Requested {line.notice!.requested}, but only {line.notice!.available} available. Quantity was adjusted.
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
                                                    aria-label="Decrease quantity"
                                                >
                                                    -
                                                </button>
                                                <span className="w-10 text-center text-sm font-medium">{line.qty}</span>
                                                <button
                                                    onClick={() => updateQty(line, Math.min(20, line.qty + 1))}
                                                    disabled={busyLine === line.line_id}
                                                    className="rounded-lg border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50 disabled:cursor-not-allowed"
                                                    aria-label="Increase quantity"
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
                                            <div className="text-sm text-neutral-600">Line total: {yen(line.line_total_cents)}</div>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {/* Summary */}
                    <aside className="h-fit rounded-xl border p-4">
                        <h2 className="mb-3 text-lg font-semibold">Summary</h2>
                        <div className="mb-1 flex items-center justify-between text-sm">
                            <span>Subtotal</span>
                            <span>{yen(cart.subtotal_cents)}</span>
                        </div>
                        {cart.savings_cents > 0 && (
                            <div className="mb-1 flex items-center justify-between text-sm text-emerald-700">
                                <span>Savings</span>
                                <span>-{yen(cart.savings_cents)}</span>
                            </div>
                        )}
                        {(cart.tax_cents ?? 0) > 0 && (
                            <div className="mb-1 flex items-center justify-between text-sm">
                                <span>Tax</span>
                                <span>{yen(cart.tax_cents ?? 0)}</span>
                            </div>
                        )}
                        {cart.coupon_code && (
                            <div className="mb-1 text-sm">
                                <div className="mb-1 flex items-center justify-between">
                                    <span>
                                        Coupon
                                        <span className="ml-2 rounded-full bg-neutral-100 px-2 py-0.5 text-xs text-neutral-700">
                                            {cart.coupon_code}
                                        </span>
                                    </span>
                                    <span className="text-rose-700">
                                        -{yen(cart.coupon_discount_cents || 0)}
                                    </span>
                                </div>
                                {cart.coupon_summary && (
                                    <div className="text-xs text-neutral-500">{cart.coupon_summary}</div>
                                )}
                                <button
                                    onClick={removeCoupon}
                                    disabled={couponBusy}
                                    className="mt-2 text-xs text-neutral-600 underline hover:text-neutral-800 disabled:cursor-not-allowed"
                                >
                                    Remove coupon
                                </button>
                            </div>
                        )}
                        <div className="mt-2 border-t pt-2">
                            <div className="flex items-center justify-between text-base font-semibold">
                                <span>Total</span>
                                <span>{yen(cart.total_cents)}</span>
                            </div>
                            <p className="mt-1 text-xs text-neutral-500">Tax included. Shipping calculated at checkout.</p>
                        </div>

                        {/* Coupon entry */}
                        {!cart.coupon_code && (
                            <div className="mt-4 rounded-lg border border-neutral-200 p-3">
                                <div className="mb-2 text-sm font-medium">Have a coupon?</div>
                                <div className="flex items-center gap-2">
                                    <input
                                        value={couponInput}
                                        onChange={(e) => setCouponInput(e.target.value)}
                                        placeholder="Enter code"
                                        className="flex-1 rounded-md border px-3 py-2 text-sm"
                                    />
                                    <button
                                        onClick={applyCoupon}
                                        disabled={couponBusy || !couponInput.trim()}
                                        className="rounded-md bg-neutral-800 px-3 py-2 text-sm text-white hover:bg-neutral-900 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        Apply
                                    </button>
                                </div>
                                {couponError && (
                                    <div className="mt-2 rounded-md bg-rose-50 px-3 py-2 text-xs text-rose-700">{couponError}</div>
                                )}
                                {couponNotice && !couponError && (
                                    <div className="mt-2 rounded-md bg-emerald-50 px-3 py-2 text-xs text-emerald-700">{couponNotice}</div>
                                )}
                            </div>
                        )}

                        <div className="mt-4">
                            <a
                                href="/checkout"
                                className="block w-full rounded-lg bg-rose-600 px-4 py-3 text-center font-medium text-white hover:bg-rose-700"
                            >
                                Proceed to Checkout
                            </a>
                        </div>

                        <div className="mt-2 text-center">
                            <a href="/products" className="text-sm text-neutral-600 hover:underline">
                                Continue shopping
                            </a>
                        </div>
                    </aside>
                </div>
            )}
        </div>
    );
}
