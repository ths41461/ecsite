import { useEffect, useMemo, useRef } from 'react';

type ProductRef = { id: number; name: string; slug: string };
type LineNotice = { code: 'qty_clamped_to_available'; requested: number; available: number };
export type Line = {
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
export type Cart = {
    lines: Line[];
    subtotal_cens: never; // guard against typo; see below
    subtotal_cents: number;
    savings_cents: number;
    total_cents: number;
    currency: string;
};

function yen(cents: number) {
    return (cents / 100).toLocaleString(undefined, { style: 'currency', currency: 'JPY' });
}

type Props = {
    open: boolean;
    cart: Cart | null;
    onClose: () => void;
    onUpdateQty: (line: Line, qty: number) => Promise<void> | void;
    onRemoveLine: (line: Line) => Promise<void> | void;
    onRefresh?: () => Promise<void> | void;
    busyLineId?: string | null;
};

export default function CartDrawer({ open, cart, onClose, onUpdateQty, onRemoveLine, onRefresh, busyLineId = null }: Props) {
    const overlayRef = useRef<HTMLDivElement | null>(null);
    const panelRef = useRef<HTMLDivElement | null>(null);

    // Lock body scroll when open
    useEffect(() => {
        const body = document.body;
        if (open) {
            const prev = body.style.overflow;
            body.style.overflow = 'hidden';
            return () => {
                body.style.overflow = prev;
            };
        }
    }, [open]);

    // Focus management: focus the panel when opening
    useEffect(() => {
        if (open) {
            // slight delay to allow element to mount
            const t = window.setTimeout(() => panelRef.current?.focus(), 0);
            return () => window.clearTimeout(t);
        }
    }, [open]);

    // Simple focus trap within the drawer when open
    useEffect(() => {
        if (!open) return;
        const handler = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                e.preventDefault();
                onClose();
                return;
            }
            if (e.key !== 'Tab') return;
            const root = panelRef.current;
            if (!root) return;
            const focusables = root.querySelectorAll<HTMLElement>('a,button,input,textarea,select,[tabindex]:not([tabindex="-1"])');
            const list = Array.from(focusables).filter((el) => !el.hasAttribute('disabled'));
            if (list.length === 0) return;
            const first = list[0];
            const last = list[list.length - 1];
            const active = document.activeElement as HTMLElement | null;
            if (e.shiftKey) {
                if (active === first || !root.contains(active)) {
                    e.preventDefault();
                    last.focus();
                }
            } else {
                if (active === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        };
        document.addEventListener('keydown', handler);
        return () => document.removeEventListener('keydown', handler);
    }, [open, onClose]);

    const hasItems = (cart?.lines?.length ?? 0) > 0;

    // Memo for clamped lines ids for quick check
    const clampedById = useMemo(() => {
        const map = new Set<string>();
        for (const l of cart?.lines ?? []) {
            if (l.notice?.code === 'qty_clamped_to_available') map.add(l.line_id);
        }
        return map;
    }, [cart]);

    return (
        <div aria-hidden={!open} className={`fixed inset-0 z-50 ${open ? '' : 'pointer-events-none'}`}>
            {/* Overlay */}
            <div
                ref={overlayRef}
                onClick={onClose}
                className={`absolute inset-0 bg-black/40 transition-opacity ${open ? 'opacity-100' : 'opacity-0'}`}
            />

            {/* Panel */}
            <div
                role="dialog"
                aria-modal="true"
                aria-label="Cart"
                ref={panelRef}
                tabIndex={-1}
                className={`absolute top-0 right-0 h-full w-full max-w-md transform overflow-y-auto bg-white shadow-xl transition-transform focus:outline-none dark:bg-neutral-900 ${
                    open ? 'translate-x-0' : 'translate-x-full'
                }`}
            >
                {/* Header */}
                <div className="sticky top-0 z-10 flex items-center justify-between border-b bg-white p-4 dark:bg-neutral-900">
                    <div>
                        <h2 className="text-lg font-semibold">Your Cart</h2>
                        <p className="text-xs text-neutral-500">{hasItems ? `${cart!.lines.length} item(s)` : 'No items yet'}</p>
                    </div>
                    <button
                        onClick={onClose}
                        className="rounded-lg border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-neutral-800"
                        aria-label="Close cart"
                    >
                        Esc
                    </button>
                </div>

                {/* Content */}
                <div className="flex h-[calc(100%-64px)] flex-col">
                    <div className="flex-1 space-y-3 overflow-y-auto p-4">
                        {!hasItems && (
                            <div className="rounded-xl border p-6 text-center">
                                <p className="mb-4 text-neutral-600">Your cart is empty.</p>
                                <a href="/products" className="inline-block rounded-lg bg-rose-600 px-4 py-2 text-white hover:bg-rose-700">
                                    Continue shopping
                                </a>
                            </div>
                        )}

                        {hasItems && (
                            <div className="space-y-3">
                                {cart!.lines.map((line) => {
                                    const clamped = clampedById.has(line.line_id);
                                    return (
                                        <div key={line.line_id} className="rounded-xl border p-4">
                                            <div className="mb-2 flex items-center justify-between">
                                                <div>
                                                    <a href={`/products/${line.product.slug}`} className="font-medium hover:underline">
                                                        {line.product.name}
                                                    </a>
                                                    <div className="text-xs text-neutral-500">SKU: {line.sku}</div>
                                                </div>
                                            <button
                                                onClick={() => onRemoveLine(line)}
                                                disabled={busyLineId === line.line_id}
                                                className="rounded-lg border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50 disabled:cursor-not-allowed dark:border-gray-700 dark:hover:bg-neutral-800"
                                            >
                                                Remove
                                            </button>
                                            </div>

                                            {clamped && (
                                                <div className="mb-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">
                                                    Requested {line.notice!.requested}, but only {line.notice!.available} available. Quantity was
                                                    adjusted.
                                                </div>
                                            )}

                                            <div className="flex items-end justify-between gap-4">
                                                <div>
                                                    <div className="mb-1 text-xs text-neutral-500">{line.stock_badge}</div>
                                                    <div className="flex items-center gap-2">
                                                        <button
                                                            onClick={() => onUpdateQty(line, Math.max(0, line.qty - 1))}
                                                            disabled={busyLineId === line.line_id}
                                                            className="rounded-lg border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50 disabled:cursor-not-allowed dark:border-gray-700 dark:hover:bg-neutral-800"
                                                            aria-label="Decrease quantity"
                                                        >
                                                            -
                                                        </button>
                                                        <span className="w-10 text-center text-sm font-medium">{line.qty}</span>
                                                        <button
                                                            onClick={() => onUpdateQty(line, Math.min(20, line.qty + 1))}
                                                            disabled={busyLineId === line.line_id}
                                                            className="rounded-lg border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50 disabled:cursor-not-allowed dark:border-gray-700 dark:hover:bg-neutral-800"
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
                        )}
                    </div>

                    {/* Summary */}
                    <div className="border-t p-4">
                        <div className="mb-1 flex items-center justify-between text-sm">
                            <span>Subtotal</span>
                            <span>{yen(cart?.subtotal_cents ?? 0)}</span>
                        </div>
                        {cart && cart.savings_cents > 0 && (
                            <div className="mb-1 flex items-center justify-between text-sm text-emerald-700">
                                <span>Savings</span>
                                <span>-{yen(cart.savings_cents)}</span>
                            </div>
                        )}
                        <div className="mt-2 border-t pt-2">
                            <div className="flex items-center justify-between text-base font-semibold">
                                <span>Total</span>
                                <span>{yen(cart?.total_cents ?? 0)}</span>
                            </div>
                            <p className="mt-1 text-xs text-neutral-500">Tax & shipping calculated at checkout.</p>
                        </div>

                        <div className="mt-4 flex gap-2">
                            <a
                                href="/checkout"
                                className="block flex-1 rounded-lg bg-rose-600 px-4 py-3 text-center font-medium text-white hover:bg-rose-700"
                            >
                                Checkout
                            </a>
                            {onRefresh && (
                                <button
                                    onClick={() => onRefresh()}
                                    className="rounded-lg border border-gray-300 px-4 py-3 text-sm hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-neutral-800"
                                >
                                    Refresh
                                </button>
                            )}
                        </div>

                        <div className="mt-2 text-center">
                            <a href="/cart" className="text-sm text-neutral-600 hover:underline">
                                View full cart
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
