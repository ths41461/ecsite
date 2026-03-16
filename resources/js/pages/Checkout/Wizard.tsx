import CheckoutTimeline, { TimelineStep } from '@/components/CheckoutTimeline';
import { HomeNavigation } from '@/components/homeNavigation';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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

// Minimalist Cart Item List Component
interface CartItemListProps {
    cartLines: CartLine[];
    couponLineIdSet: Set<string>;
}

const CartItemList: React.FC<CartItemListProps> = ({ cartLines, couponLineIdSet }) => {
    return (
        <div className="divide-y divide-[#D8D9E0] border border-[#D8D9E0]">
            <div className="grid grid-cols-4 gap-4 border-b border-[#D8D9E0] px-4 py-3 text-xs text-[#81859C] uppercase">
                <div>商品</div>
                <div>SKU</div>
                <div className="text-right">数量</div>
                <div className="text-right">価格</div>
            </div>
            <div className="divide-y divide-[#D8D9E0]">
                {cartLines.map((line) => (
                    <div key={line.line_id} className="grid grid-cols-4 gap-4 px-4 py-3 text-sm">
                        <div className="font-medium text-[#363842]">
                            <div>{line.product.name}</div>
                            <div className="text-xs text-[#81859C]">#{line.product.slug}</div>
                            {couponLineIdSet.has(line.line_id) && <div className="mt-1 text-xs font-medium text-[#059669]">クーポン適用</div>}
                        </div>
                        <div className="text-sm text-[#81859C]">{line.sku}</div>
                        <div className="text-right text-[#363842]">{line.qty}</div>
                        <div className="text-right font-serif text-[#363842]">{formatYen(line.line_total_cents / 100)}</div>
                    </div>
                ))}
            </div>
        </div>
    );
};

// Minimalist Order Summary Component
interface OrderSummaryProps {
    cartSnapshot: CartPayload | null;
    couponError: string | null;
    couponNotice: string | null;
    couponBusy: boolean;
    onRemoveCoupon: () => void;
}

const OrderSummary: React.FC<OrderSummaryProps> = ({ cartSnapshot, couponError, couponNotice, couponBusy, onRemoveCoupon }) => {
    if (!cartSnapshot) {
        return <div className="border border-[#D8D9E0] p-6 text-sm text-[#81859C]">カートが空です。</div>;
    }

    return (
        <div className="border border-[#D8D9E0] p-6 space-y-3">
            <div className="flex justify-between">
                <span className="text-[#81859C]">小計</span>
                <span className="text-[#363842]">{formatYen((cartSnapshot.subtotal_cents ?? 0) / 100)}</span>
            </div>
            
            {(cartSnapshot.coupon_discount_cents ?? 0) > 0 && (
                <div className="flex items-center justify-between">
                    <div>
                        <span className="text-[#DC2626]">
                            クーポン{cartSnapshot.coupon_code ? ` (${cartSnapshot.coupon_code})` : ''}
                        </span>
                        {cartSnapshot.coupon_summary && <div className="text-xs text-[#81859C] mt-1">{cartSnapshot.coupon_summary}</div>}
                    </div>
                    <div className="flex items-center gap-2">
                        <span className="text-[#DC2626]">-{formatYen((cartSnapshot.coupon_discount_cents ?? 0) / 100)}</span>
                        <button
                            type="button"
                            onClick={onRemoveCoupon}
                            disabled={couponBusy}
                            className="text-xs text-[#81859C] underline hover:text-[#363842] disabled:cursor-not-allowed"
                        >
                            削除
                        </button>
                    </div>
                </div>
            )}
            
            {(cartSnapshot.tax_cents ?? 0) > 0 && (
                <div className="flex justify-between">
                    <span className="text-[#81859C]">税金</span>
                    <span className="text-[#363842]">{formatYen((cartSnapshot.tax_cents ?? 0) / 100)}</span>
                </div>
            )}
            
            <div className="pt-3 border-t border-[#D8D9E0]">
                <div className="flex justify-between font-semibold text-[#363842]">
                    <span className="font-['Hiragino_Mincho_ProN']">合計</span>
                    <span className="font-['Hiragino_Mincho_ProN'] text-lg font-bold">{formatYen((cartSnapshot.total_cents ?? 0) / 100)}</span>
                </div>
            </div>

            {couponError && <div className="mt-3 py-2 text-xs text-[#DC2626]">{couponError}</div>}
            {couponNotice && !couponError && <div className="mt-3 py-2 text-xs text-[#059669]">{couponNotice}</div>}
        </div>
    );
};

// Minimalist Order Items List Component
interface OrderItemsListProps {
    items: OrderItem[] | undefined;
}

const OrderItemsList: React.FC<OrderItemsListProps> = ({ items }) => {
    if (!items?.length) return null;
    return (
        <div className="divide-y divide-[#D8D9E0] border border-[#D8D9E0]">
            <div className="grid grid-cols-4 gap-4 border-b border-[#D8D9E0] px-4 py-3 text-xs text-[#81859C] uppercase">
                <div>商品</div>
                <div>SKU</div>
                <div className="text-right">数量</div>
                <div className="text-right">価格</div>
            </div>
            <div className="divide-y divide-[#D8D9E0]">
                {items.map((item, idx) => (
                    <div key={`${item.sku}-${idx}`} className="grid grid-cols-4 gap-4 px-4 py-3 text-sm">
                        <div className="text-[#363842]">{item.name}</div>
                        <div className="text-sm text-[#81859C]">{item.sku}</div>
                        <div className="text-right text-[#363842]">{item.qty}</div>
                        <div className="text-right font-serif text-[#363842]">{formatYen(item.line_total_yen)}</div>
                    </div>
                ))}
            </div>
        </div>
    );
};

// Minimalist Order Summary Details Component
interface OrderSummaryDetailsProps {
    order: OrderPayload | undefined;
}

const OrderSummaryDetails: React.FC<OrderSummaryDetailsProps> = ({ order }) => {
    return (
        <div className="border border-[#D8D9E0] p-6 space-y-3">
            <div className="flex justify-between">
                <span className="text-[#81859C]">小計</span>
                <span className="text-[#363842]">{formatYen(order?.subtotal_yen ?? 0)}</span>
            </div>
            
            {(order?.discount_yen ?? 0) > 0 && (
                <div className="flex justify-between">
                    <span className="text-[#81859C]">割引</span>
                    <span className="text-[#059669]">-{formatYen(order?.discount_yen ?? 0)}</span>
                </div>
            )}
            
            {order?.coupon_code && (order?.coupon_discount_yen ?? 0) > 0 && (
                <div className="flex justify-between">
                    <span className="text-[#81859C]">クーポン ({order.coupon_code})</span>
                    <span className="text-[#DC2626]">-{formatYen(order.coupon_discount_yen ?? 0)}</span>
                </div>
            )}
            
            <div className="flex justify-between">
                <span className="text-[#81859C]">送料</span>
                <span className="text-[#363842]">{formatYen(order?.shipping_yen ?? 0)}</span>
            </div>
            
            <div className="flex justify-between">
                <span className="text-[#81859C]">税金</span>
                <span className="text-[#363842]">{formatYen(order?.tax_yen ?? 0)}</span>
            </div>
            
            <div className="pt-3 border-t border-[#D8D9E0]">
                <div className="flex justify-between font-semibold text-[#363842]">
                    <span className="font-['Hiragino_Mincho_ProN']">合計</span>
                    <span className="font-['Hiragino_Mincho_ProN'] text-lg font-bold">{formatYen(order?.total_yen ?? 0)}</span>
                </div>
            </div>
        </div>
    );
};

// Minimalist Contact Form Component
interface ContactFormProps {
    form: {
        email: string;
        name: string;
        phone: string;
        address_line1: string;
        address_line2: string;
        city: string;
        state: string;
        zip: string;
    };
    validationErrors: Record<string, string[]>;
    loading: boolean;
    orderNumber: string | null;
    onSubmit: (event: React.FormEvent) => void;
    onChange: (field: string, value: string) => void;
}

const ContactForm: React.FC<ContactFormProps> = ({ form, validationErrors, loading, orderNumber, onSubmit, onChange }) => {
    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label className="mb-2 block text-sm font-medium text-[#363842]">
                        メールアドレス <span className="text-[#DC2626]">*</span>
                    </label>
                    <Input
                        type="email"
                        value={form.email}
                        onChange={(e) => onChange('email', e.target.value)}
                        className="h-[40px] border-[#D8D9E0] bg-white text-[#363842] rounded-none"
                        required
                        autoComplete="email"
                    />
                    {validationErrors.email && <p className="mt-1 text-xs text-[#DC2626]">{validationErrors.email[0]}</p>}
                </div>
                <div>
                    <label className="mb-2 block text-sm font-medium text-[#363842]">
                        お名前 <span className="text-[#DC2626]">*</span>
                    </label>
                    <Input
                        type="text"
                        value={form.name}
                        onChange={(e) => onChange('name', e.target.value)}
                        className="h-[40px] border-[#D8D9E0] bg-white text-[#363842] rounded-none"
                        required
                        autoComplete="name"
                    />
                    {validationErrors.name && <p className="mt-1 text-xs text-[#DC2626]">{validationErrors.name[0]}</p>}
                </div>
            </div>

            <div>
                <label className="mb-2 block text-sm font-medium text-[#363842]">
                    電話番号 <span className="text-[#81859C]">(任意)</span>
                </label>
                <Input
                    type="tel"
                    value={form.phone}
                    onChange={(e) => onChange('phone', e.target.value)}
                    className="h-[40px] border-[#D8D9E0] bg-white text-[#363842] rounded-none"
                    autoComplete="tel"
                />
                {validationErrors.phone && <p className="mt-1 text-xs text-[#DC2626]">{validationErrors.phone[0]}</p>}
            </div>

            <div>
                <label className="mb-2 block text-sm font-medium text-[#363842]">
                    住所 <span className="text-[#DC2626]">*</span>
                </label>
                <Input
                    type="text"
                    value={form.address_line1}
                    onChange={(e) => onChange('address_line1', e.target.value)}
                    className="h-[40px] border-[#D8D9E0] bg-white text-[#363842] rounded-none"
                    required
                    autoComplete="address-line1"
                />
                {validationErrors.address_line1 && <p className="mt-1 text-xs text-[#DC2626]">{validationErrors.address_line1[0]}</p>}
            </div>

            <div>
                <Input
                    type="text"
                    value={form.address_line2}
                    onChange={(e) => onChange('address_line2', e.target.value)}
                    className="h-[40px] border-[#D8D9E0] bg-white text-[#363842] rounded-none mt-2"
                    placeholder="住所2 (任意)"
                    autoComplete="address-line2"
                />
                {validationErrors.address_line2 && <p className="mt-1 text-xs text-[#DC2626]">{validationErrors.address_line2[0]}</p>}
            </div>

            <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div>
                    <Input
                        type="text"
                        value={form.city}
                        onChange={(e) => onChange('city', e.target.value)}
                        className="h-[40px] border-[#D8D9E0] bg-white text-[#363842] rounded-none"
                        placeholder="市区町村 (任意)"
                        autoComplete="address-level2"
                    />
                    {validationErrors.city && <p className="mt-1 text-xs text-[#DC2626]">{validationErrors.city[0]}</p>}
                </div>
                <div>
                    <Input
                        type="text"
                        value={form.state}
                        onChange={(e) => onChange('state', e.target.value)}
                        className="h-[40px] border-[#D8D9E0] bg-white text-[#363842] rounded-none"
                        placeholder="都道府県 (任意)"
                        autoComplete="address-level1"
                    />
                    {validationErrors.state && <p className="mt-1 text-xs text-[#DC2626]">{validationErrors.state[0]}</p>}
                </div>
                <div>
                    <Input
                        type="text"
                        value={form.zip}
                        onChange={(e) => onChange('zip', e.target.value)}
                        className="h-[40px] border-[#D8D9E0] bg-white text-[#363842] rounded-none"
                        placeholder="郵便番号 (任意)"
                        autoComplete="postal-code"
                    />
                    {validationErrors.zip && <p className="mt-1 text-xs text-[#DC2626]">{validationErrors.zip[0]}</p>}
                </div>
            </div>

            <div className="pt-6 border-t border-[#D8D9E0] flex justify-between">
                <Button
                    asChild
                    variant="outline"
                    className="rounded-none border-[#D8D9E0] text-[#363842]"
                >
                    <a href="/checkout">カート確認に戻る</a>
                </Button>
                <Button
                    type="submit"
                    disabled={loading}
                    className="h-12 rounded-none bg-[#363842] text-base font-medium text-white"
                >
                    {loading ? '保存中…' : '支払いへ進む'}
                </Button>
            </div>
        </form>
    );
};

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

    const handleFormChange = (field: string, value: string) => {
        setForm((prev) => ({ ...prev, [field]: value }));
    };

    return (
        <div className="min-h-screen bg-[#FCFCF7]">
            <HomeNavigation />
            <div className="mx-auto max-w-[1440px] px-4 py-8">
                <Head title="チェックアウト" />
                <CheckoutTimeline steps={timeline} />

                {step === 'review' && previousCancelledReason && (
                    <div className="mb-6 py-3 text-sm text-[#92400E] bg-[#FFFBEB] border border-[#F59E0B]">
                        前回のチェックアウトは以下の理由で終了しました: {previousCancelledReason}
                    </div>
                )}

                {error && (
                    <div className="mb-6 py-3 text-sm text-[#B91C1C] bg-[#FEE2E2] border border-[#EF4444]">
                        {error}
                    </div>
                )}

                {step === 'review' && (
                    <div className="space-y-8">
                        <div className="mb-6">
                            <h1 className="mb-2 font-['Hiragino_Mincho_ProN'] text-2xl font-semibold text-[#363842]">カートを確認</h1>
                            <p className="text-sm text-[#81859C]">ご購入内容をご確認ください</p>
                        </div>

                        <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                            {/* Cart Items */}
                            <div className="lg:col-span-2">
                                <CartItemList cartLines={cartLines} couponLineIdSet={couponLineIdSet} />
                            </div>

                            {/* Order Summary */}
                            <div className="lg:col-span-1">
                                <OrderSummary
                                    cartSnapshot={cartSnapshot}
                                    couponError={couponError}
                                    couponNotice={couponNotice}
                                    couponBusy={couponBusy}
                                    onRemoveCoupon={removeCoupon}
                                />

                                <div className="mt-6 text-center">
                                    <Button 
                                        asChild 
                                        variant="outline" 
                                        className="rounded-none border-[#D8D9E0] text-[#363842]"
                                    >
                                        <a href="/cart">カートに戻る</a>
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div className="flex justify-end">
                            <Button
                                onClick={startOrder}
                                disabled={loading || !cartLines.length}
                                className="h-12 rounded-none bg-[#363842] text-base font-medium text-white hover:bg-gray-800"
                            >
                                {loading ? '準備中…' : '詳細へ進む'}
                            </Button>
                        </div>
                    </div>
                )}

                {step === 'details' && orderNumber && (
                    <div className="space-y-8">
                        <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h1 className="mb-1 font-['Hiragino_Mincho_ProN'] text-2xl font-semibold text-[#363842]">配送先と連絡先</h1>
                                <p className="text-sm text-[#81859C]">注文番号 #{orderNumber}</p>
                            </div>
                            <Button
                                asChild
                                variant="outline"
                                className="w-full rounded-none border-[#D8D9E0] text-[#363842] sm:w-auto"
                            >
                                <a href="/checkout">最初からやり直す</a>
                            </Button>
                        </div>

                        {order?.items && (
                            <div>
                                <h2 className="mb-4 text-lg font-medium text-[#363842]">ご購入商品</h2>
                                <OrderItemsList items={order.items} />
                            </div>
                        )}

                        <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                            {/* Contact Form */}
                            <div className="lg:col-span-2">
                                <p className="mb-6 text-sm text-[#81859C]">この情報は注文の確認と更新通知に使用されます。</p>
                                <ContactForm
                                    form={form}
                                    validationErrors={validationErrors}
                                    loading={loading}
                                    orderNumber={orderNumber}
                                    onSubmit={submitDetails}
                                    onChange={handleFormChange}
                                />
                            </div>

                            {/* Order Summary */}
                            <div className="lg:col-span-1">
                                <OrderSummaryDetails order={order} />
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
