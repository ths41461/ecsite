import CheckoutTimeline, { TimelineStep } from '@/components/CheckoutTimeline';
import { Head, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

type CartLine = {
    line_id: string;
    product: { id: number; name: string; slug: string };
    sku: string;
    qty: number;
    price_cents: number;
    line_total_cents: number;
    savings_cents: number;
    managed: boolean;
    available_qty?: number | null;
    notice?: { code: string; requested: number; available: number };
};

type CartPayload = {
    lines: CartLine[];
    subtotal_cents: number;
    savings_cents: number;
    coupon_code?: string | null;
    coupon_summary?: string | null;
    tax_cents?: number;
    coupon_discount_cents?: number;
    coupon_line_ids?: string[] | null;
    coupon_line_names?: string[] | null;
    total_cents: number;
    currency: string;
};

type OrderItem = {
    name: string;
    sku: string;
    qty: number;
    unit_price_yen: number;
    line_total_yen: number;
};

type OrderPayload = {
    order_number: string;
    status: string;
    subtotal_yen: number;
    discount_yen: number;
    shipping_yen: number;
    tax_yen: number;
    total_yen: number;
    coupon_code?: string | null;
    coupon_discount_yen?: number | null;
    email: string;
    name: string;
    phone?: string | null;
    address_line1?: string | null;
    address_line2?: string | null;
    city?: string | null;
    state?: string | null;
    zip?: string | null;
    items?: OrderItem[];
};

type SavedContact = {
    email?: string | null;
    name?: string | null;
};

type Props = {
    step: 'review' | 'details';
    previousCancelledReason?: string | null;
    cart?: CartPayload | null;
    order?: OrderPayload | null;
    timeline: TimelineStep[];
    savedContact?: SavedContact | null;
};

function formatYen(value: number) {
    return new Intl.NumberFormat('ja-JP', { style: 'currency', currency: 'JPY' }).format(value);
}

function xsrfHeaders(): HeadersInit {
    const match = document.cookie.split('; ').find((row) => row.startsWith('XSRF-TOKEN='));
    const token = match ? decodeURIComponent(match.split('=')[1] ?? '') : null;
    return {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(token ? { 'X-XSRF-TOKEN': token } : {}),
    };
}

export default function CheckoutWizard({ step, previousCancelledReason, cart, order, timeline, savedContact }: Props) {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [form, setForm] = useState({
        email: order?.email ?? savedContact?.email ?? '',
        name: order?.name ?? savedContact?.name ?? '',
        phone: order?.phone ?? '',
        address_line1: order?.address_line1 ?? '',
        address_line2: order?.address_line2 ?? '',
        city: order?.city ?? '',
        state: order?.state ?? '',
        zip: order?.zip ?? '',
    });
    const [validationErrors, setValidationErrors] = useState<Record<string, string[]>>({});
    const [cartSnapshot, setCartSnapshot] = useState<CartPayload | null>(cart ?? null);
    const [couponBusy, setCouponBusy] = useState(false);
    const [couponError, setCouponError] = useState<string | null>(null);
    const [couponNotice, setCouponNotice] = useState<string | null>(null);

    const orderNumber = order?.order_number ?? null;

    const cartLines = useMemo(() => {
        return cartSnapshot?.lines ?? [];
    }, [cartSnapshot]);

    const couponLineIdSet = useMemo(() => {
        if (!cartSnapshot?.coupon_line_ids) return new Set<string>();
        return new Set(cartSnapshot.coupon_line_ids);
    }, [cartSnapshot?.coupon_line_ids]);

    async function startOrder() {
        if (loading) return;
        setError(null);
        setLoading(true);
        try {
            const res = await fetch('/checkout/order', {
                method: 'POST',
                headers: xsrfHeaders(),
                body: JSON.stringify({}),
            });
            if (!res.ok) {
                const data = await res.json().catch(() => null);
                throw new Error(data?.message || 'チェックアウトを開始できません');
            }
            const data = await res.json();
            if (data?.redirect) {
                router.visit(data.redirect);
            } else {
                window.location.reload();
            }
        } catch (e: any) {
            setError(e?.message || '問題が発生しました');
        } finally {
            setLoading(false);
        }
    }

    async function submitDetails(event: React.FormEvent) {
        event.preventDefault();
        if (!orderNumber || loading) return;
        setError(null);
        setValidationErrors({});
        setLoading(true);
        try {
            const res = await fetch(`/checkout/${orderNumber}/details`, {
                method: 'POST',
                headers: xsrfHeaders(),
                body: JSON.stringify(form),
            });
            if (res.status === 422) {
                const data = await res.json().catch(() => null);
                setValidationErrors(data?.errors || {});
                throw new Error(data?.message || 'ハイライトされたフィールドを修正してください。');
            }
            if (!res.ok) {
                const data = await res.json().catch(() => null);
                throw new Error(data?.message || '詳細を保存できません');
            }
            const data = await res.json();
            const nextOrderNumber = data?.order?.order_number ?? orderNumber;
            await beginPayment(nextOrderNumber);
        } catch (e: any) {
            setError(e?.message || '問題が発生しました');
        } finally {
            setLoading(false);
        }
    }

    async function beginPayment(orderNumber: string) {
        const res = await fetch('/checkout', {
            method: 'POST',
            headers: xsrfHeaders(),
            body: JSON.stringify({ order_number: orderNumber }),
        });
        if (!res.ok) {
            const data = await res.json().catch(() => null);
            throw new Error(data?.message || '支払いを開始できません');
        }
        const data = await res.json();
        if (data?.redirect) {
            window.location.href = data.redirect;
            return;
        }
        if (data?.url) {
            window.location.href = data.url;
            return;
        }
        throw new Error('チェックアウトセッションを作成できませんでした');
    }

    async function removeCoupon() {
        if (couponBusy) return;
        setCouponBusy(true);
        setCouponError(null);
        setCouponNotice(null);
        try {
            const res = await fetch('/cart/coupon', {
                method: 'DELETE',
                headers: xsrfHeaders(),
                credentials: 'same-origin',
            });
            const data = (await res.json().catch(() => null)) as CartPayload | null;
            if (!res.ok || !data) {
                const message = (data as any)?.errors?.code?.[0] || (data as any)?.message || 'クーポンの削除に失敗しました';
                throw new Error(message);
            }
            setCartSnapshot(data);
            setCouponNotice('クーポンを削除しました。');
        } catch (e: any) {
            setCouponError(e?.message || 'クーポンの削除に失敗しました。');
        } finally {
            setCouponBusy(false);
        }
    }

    function renderCart() {
        if (!cartSnapshot) {
            return <div className="rounded-lg border border-neutral-200 p-4 text-sm text-neutral-600">カートが空です。</div>;
        }

        return (
            <div className="space-y-4">
                <div className="rounded-lg border border-neutral-200">
                    <table className="w-full text-sm">
                        <thead className="bg-neutral-50 text-left text-xs text-neutral-500 uppercase">
                            <tr>
                                <th className="px-4 py-2">商品</th>
                                <th className="px-4 py-2">SKU</th>
                                <th className="px-4 py-2 text-right">数量</th>
                                <th className="px-4 py-2 text-right">価格</th>
                            </tr>
                        </thead>
                        <tbody>
                            {cartLines.map((line) => (
                                <tr key={line.line_id} className="border-t">
                                    <td className="px-4 py-2">
                                        <div className="font-medium text-neutral-800">{line.product.name}</div>
                                        <div className="text-xs text-neutral-500">#{line.product.slug}</div>
                                        {couponLineIdSet.has(line.line_id) && (
                                            <div className="text-xs font-medium text-emerald-600">クーポン適用</div>
                                        )}
                                    </td>
                                    <td className="px-4 py-2 text-sm text-neutral-600">{line.sku}</td>
                                    <td className="px-4 py-2 text-right">{line.qty}</td>
                                    <td className="px-4 py-2 text-right">{formatYen(line.line_total_cents / 100)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="rounded-lg border border-neutral-200 bg-neutral-100 px-4 py-3 text-sm">
                    <div className="flex justify-between">
                        <span>小計</span>
                        <span>{formatYen((cartSnapshot.subtotal_cents ?? 0) / 100)}</span>
                    </div>
                    {(cartSnapshot.coupon_discount_cents ?? 0) > 0 && (
                        <div className="flex items-center justify-between text-rose-600">
                            <span>
                                クーポン{cartSnapshot.coupon_code ? ` (${cartSnapshot.coupon_code})` : ''}
                                {cartSnapshot.coupon_summary && (
                                    <span className="ml-2 text-[11px] text-neutral-500">{cartSnapshot.coupon_summary}</span>
                                )}
                            </span>
                            <div className="flex items-center gap-2">
                                <span>-{formatYen((cartSnapshot.coupon_discount_cents ?? 0) / 100)}</span>
                                <button
                                    type="button"
                                    onClick={removeCoupon}
                                    disabled={couponBusy}
                                    className="rounded-md border border-neutral-300 px-2 py-0.5 text-xs text-neutral-600 hover:bg-neutral-100 disabled:cursor-not-allowed"
                                >
                                    削除
                                </button>
                            </div>
                        </div>
                    )}
                    {(cartSnapshot.tax_cents ?? 0) > 0 && (
                        <div className="flex justify-between">
                            <span>税金</span>
                            <span>{formatYen((cartSnapshot.tax_cents ?? 0) / 100)}</span>
                        </div>
                    )}
                    <div className="mt-1 flex justify-between font-semibold">
                        <span>合計</span>
                        <span>{formatYen((cartSnapshot.total_cents ?? 0) / 100)}</span>
                    </div>
                    {couponError && (
                        <div className="mt-2 rounded-md bg-rose-50 px-3 py-2 text-xs text-rose-700">{couponError}</div>
                    )}
                    {couponNotice && !couponError && (
                        <div className="mt-2 rounded-md bg-emerald-50 px-3 py-2 text-xs text-emerald-700">{couponNotice}</div>
                    )}
                    {(cartSnapshot.coupon_line_names?.length ?? 0) > 0 && (
                        <div className="mt-2 text-xs text-neutral-500">
                            {cartSnapshot.coupon_line_names?.[0] === 'すべての商品'
                                ? 'クーポンはカート内のすべての商品に適用されます'
                                : `クーポン適用対象: ${cartSnapshot.coupon_line_names?.join(', ')}`}
                        </div>
                    )}
                </div>
            </div>
        );
    }

    function renderOrderItems(items: OrderItem[] | undefined) {
        if (!items?.length) return null;
        return (
            <div className="rounded-lg border border-neutral-200">
                <table className="w-full text-sm">
                    <thead className="bg-neutral-50 text-left text-xs text-neutral-500 uppercase">
                        <tr>
                            <th className="px-4 py-2">商品</th>
                            <th className="px-4 py-2">SKU</th>
                            <th className="px-4 py-2 text-right">数量</th>
                            <th className="px-4 py-2 text-right">価格</th>
                        </tr>
                    </thead>
                    <tbody>
                        {items.map((item, idx) => (
                            <tr key={`${item.sku}-${idx}`} className="border-t">
                                <td className="px-4 py-2">{item.name}</td>
                                <td className="px-4 py-2 text-sm text-neutral-600">{item.sku}</td>
                                <td className="px-4 py-2 text-right">{item.qty}</td>
                                <td className="px-4 py-2 text-right">{formatYen(item.line_total_yen)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        );
    }

    return (
        <div className="mx-auto max-w-3xl px-4 py-8">
            <Head title="チェックアウト" />
            <CheckoutTimeline steps={timeline} />

            {previousCancelledReason && step === 'review' && (
                <div className="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    前回のチェックアウトは以下の理由で終了しました: {previousCancelledReason}
                </div>
            )}

            {error && <div className="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{error}</div>}

            {step === 'review' && (
                <div className="space-y-6">
                    <h1 className="text-2xl font-semibold">カートを確認</h1>
                    {renderCart()}
                    <div className="flex items-center justify-between">
                        <a href="/cart" className="text-sm text-neutral-600 hover:underline">
                            カートに戻る
                        </a>
                        <button
                            onClick={startOrder}
                            disabled={loading || !cartLines.length}
                            className="rounded-md bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {loading ? '準備中…' : '詳細へ進む'}
                        </button>
                    </div>
                </div>
            )}

            {step === 'details' && orderNumber && (
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-semibold">配送先と連絡先</h1>
                            <p className="text-sm text-neutral-600">注文番号 #{orderNumber}</p>
                        </div>
                        <a href="/checkout" className="text-sm text-neutral-600 hover:underline">
                            最初からやり直す
                        </a>
                    </div>

                    {renderOrderItems(order?.items)}
                    <div className="rounded-lg border border-neutral-200 bg-neutral-100 px-4 py-3 text-sm">
                        <div className="flex justify-between">
                            <span>小計</span>
                            <span>{formatYen(order?.subtotal_yen ?? 0)}</span>
                        </div>
                        {(order?.discount_yen ?? 0) > 0 && (
                            <div className="flex justify-between text-rose-600">
                                <span>割引</span>
                                <span>-{formatYen(order?.discount_yen ?? 0)}</span>
                            </div>
                        )}
                        {order?.coupon_code && (order?.coupon_discount_yen ?? 0) > 0 && (
                            <div className="flex justify-between text-rose-600 text-xs">
                                <span>クーポン ({order.coupon_code})</span>
                                <span>-{formatYen(order.coupon_discount_yen ?? 0)}</span>
                            </div>
                        )}
                        <div className="flex justify-between">
                            <span>送料</span>
                            <span>{formatYen(order?.shipping_yen ?? 0)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span>税金</span>
                            <span>{formatYen(order?.tax_yen ?? 0)}</span>
                        </div>
                        <div className="mt-1 flex justify-between font-semibold">
                            <span>合計</span>
                            <span>{formatYen(order?.total_yen ?? 0)}</span>
                        </div>
                    </div>

          <p className="text-sm text-neutral-600">この情報は注文の確認と更新通知に使用されます。</p>
          <form onSubmit={submitDetails} className="space-y-4">
            <div className="grid gap-4 md:grid-cols-2">
              <div>
                <label className="text-xs font-semibold text-neutral-500 uppercase">
                  メールアドレス <span className="text-rose-500">*</span>
                </label>
                <input
                  type="email"
                  value={form.email}
                  onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))}
                  className="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none"
                  required
                  autoComplete="email"
                  aria-required="true"
                />
                {validationErrors.email && <p className="mt-1 text-xs text-rose-600">{validationErrors.email[0]}</p>}
              </div>
              <div>
                <label className="text-xs font-semibold text-neutral-500 uppercase">
                  お名前 <span className="text-rose-500">*</span>
                </label>
                <input
                  type="text"
                  value={form.name}
                  onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                  className="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none"
                  required
                  autoComplete="name"
                  aria-required="true"
                />
                {validationErrors.name && <p className="mt-1 text-xs text-rose-600">{validationErrors.name[0]}</p>}
              </div>
            </div>
            <div className="grid gap-4 md:grid-cols-2">
              <div>
                <label className="text-xs font-semibold text-neutral-500 uppercase">電話番号 <span className="text-neutral-400">(任意)</span></label>
                <input
                  type="tel"
                  value={form.phone ?? ''}
                  onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))}
                  className="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none"
                  autoComplete="tel"
                />
                {validationErrors.phone && <p className="mt-1 text-xs text-rose-600">{validationErrors.phone[0]}</p>}
              </div>
            </div>
            <div>
              <label className="text-xs font-semibold text-neutral-500 uppercase">
                住所1 <span className="text-rose-500">*</span>
              </label>
              <input
                type="text"
                value={form.address_line1}
                onChange={(e) => setForm((f) => ({ ...f, address_line1: e.target.value }))}
                className="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none"
                required
                autoComplete="address-line1"
                aria-required="true"
              />
              {validationErrors.address_line1 && <p className="mt-1 text-xs text-rose-600">{validationErrors.address_line1[0]}</p>}
            </div>
            <div>
              <label className="text-xs font-semibold text-neutral-500 uppercase">住所2 <span className="text-neutral-400">(任意)</span></label>
              <input
                type="text"
                value={form.address_line2 ?? ''}
                onChange={(e) => setForm((f) => ({ ...f, address_line2: e.target.value }))}
                className="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none"
                autoComplete="address-line2"
              />
              {validationErrors.address_line2 && <p className="mt-1 text-xs text-rose-600">{validationErrors.address_line2[0]}</p>}
            </div>
            <div className="grid gap-4 md:grid-cols-3">
              <div>
                <label className="text-xs font-semibold text-neutral-500 uppercase">市区町村 <span className="text-neutral-400">(任意)</span></label>
                <input
                  type="text"
                  value={form.city ?? ''}
                  onChange={(e) => setForm((f) => ({ ...f, city: e.target.value }))}
                  className="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none"
                  autoComplete="address-level2"
                />
                {validationErrors.city && <p className="mt-1 text-xs text-rose-600">{validationErrors.city[0]}</p>}
              </div>
              <div>
                <label className="text-xs font-semibold text-neutral-500 uppercase">都道府県 <span className="text-neutral-400">(任意)</span></label>
                <input
                  type="text"
                  value={form.state ?? ''}
                  onChange={(e) => setForm((f) => ({ ...f, state: e.target.value }))}
                  className="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none"
                  autoComplete="address-level1"
                />
                {validationErrors.state && <p className="mt-1 text-xs text-rose-600">{validationErrors.state[0]}</p>}
              </div>
              <div>
                <label className="text-xs font-semibold text-neutral-500 uppercase">郵便番号 <span className="text-neutral-400">(任意)</span></label>
                <input
                  type="text"
                  value={form.zip ?? ''}
                  onChange={(e) => setForm((f) => ({ ...f, zip: e.target.value }))}
                  className="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none"
                  autoComplete="postal-code"
                />
                {validationErrors.zip && <p className="mt-1 text-xs text-rose-600">{validationErrors.zip[0]}</p>}
              </div>
            </div>

                        <div className="flex items-center justify-between">
                            <a href="/checkout" className="text-sm text-neutral-600 hover:underline">
                                カート確認に戻る
                            </a>
                            <button
                                type="submit"
                                disabled={loading}
                                className="rounded-md bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {loading ? '保存中…' : '支払いへ進む'}
                            </button>
                        </div>
                    </form>
                </div>
            )}
        </div>
    );
}