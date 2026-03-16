import { Head } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { HomeNavigation } from '@/components/homeNavigation';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

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

// Cart Line Component
interface CartLineProps {
    line: Line;
    busyLine: string | null;
    couponLineIdSet: Set<string>;
    onUpdateQty: (line: Line, qty: number) => void;
    onRemoveLine: (line: Line) => void;
}

const CartLine: React.FC<CartLineProps> = ({ line, busyLine, couponLineIdSet, onUpdateQty, onRemoveLine }) => {
    const clamped = line.notice?.code === 'qty_clamped_to_available';
    const couponApplied = couponLineIdSet.has(line.line_id);

    return (
        <div className="border-b border-[#D8D9E0] pb-4 mb-4">
            {/* Header: product + sku */}
            <div className="mb-3 flex items-start justify-between">
                <div className="flex-1">
                    <a href={`/products/${line.product.slug}`} className="font-medium text-[#363842] hover:underline block">
                        {line.product.name}
                    </a>
                    <div className="text-xs text-[#81859C] mt-1">SKU: {line.sku}</div>
                </div>
                <button
                    onClick={() => onRemoveLine(line)}
                    disabled={busyLine === line.line_id}
                    className="ml-4 text-[#81859C] hover:text-[#363842] disabled:opacity-50 disabled:cursor-not-allowed"
                    aria-label="削除"
                >
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15 5L5 15M5 5L15 15" stroke="#81859C" strokeWidth="1.667" strokeLinecap="round" strokeLinejoin="round"/>
                    </svg>
                </button>
            </div>

            {/* notice if qty clamped */}
            {clamped && (
                <div className="mb-3 bg-[#FEF3C7] px-3 py-2 text-xs text-[#D97706]">
                    {line.notice!.requested} 個リクエストしましたが、在庫は {line.notice!.available} 個のみです。数量を調整しました。
                </div>
            )}

            {/* qty + price */}
            <div className="flex items-center justify-between gap-4">
                <div>
                    <div className="mb-1 text-xs text-[#81859C]">{line.stock_badge}</div>
                    <div className="flex items-center gap-3">
                        <button
                            onClick={() => onUpdateQty(line, Math.max(0, line.qty - 1))}
                            disabled={busyLine === line.line_id}
                            className="w-8 h-8 border border-[#D8D9E0] flex items-center justify-center text-[#363842] hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                            aria-label="数量を減らす"
                        >
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3.333 8H12.667" stroke="currentColor" strokeWidth="1.667" strokeLinecap="round" strokeLinejoin="round"/>
                            </svg>
                        </button>
                        <span className="w-10 text-center text-sm font-medium text-[#363842]">{line.qty}</span>
                        <button
                            onClick={() => onUpdateQty(line, Math.min(20, line.qty + 1))}
                            disabled={busyLine === line.line_id}
                            className="w-8 h-8 border border-[#D8D9E0] flex items-center justify-center text-[#363842] hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                            aria-label="数量を増やす"
                        >
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8 3.333V12.667M3.333 8H12.667" stroke="currentColor" strokeWidth="1.667" strokeLinecap="round" strokeLinejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div className="text-right">
                    {line.compare_at_cents != null && line.compare_at_cents > line.price_cents && (
                        <div className="text-xs text-[#81859C] line-through">{yen(line.compare_at_cents)}</div>
                    )}
                    <div className="text-[#363842] font-serif font-semibold">{yen(line.price_cents)}</div>
                    <div className="text-xs text-[#81859C]">小計: {yen(line.line_total_cents)}</div>
                    {couponApplied && <div className="text-xs font-medium text-[#059669]">クーポン適用</div>}
                </div>
            </div>
        </div>
    );
};

// Cart Summary Component
interface CartSummaryProps {
    cart: Cart;
    couponInput: string;
    couponBusy: boolean;
    couponError: string | null;
    couponNotice: string | null;
    onApplyCoupon: () => void;
    onRemoveCoupon: () => void;
    setCouponInput: (value: string) => void;
}

const CartSummary: React.FC<CartSummaryProps> = ({ 
    cart, 
    couponInput, 
    couponBusy, 
    couponError, 
    couponNotice, 
    onApplyCoupon, 
    onRemoveCoupon, 
    setCouponInput 
}) => {
    const couponLineIdSet = useMemo(() => new Set(cart.coupon_line_ids ?? []), [cart.coupon_line_ids]);

    return (
        <div className="border border-[#D8D9E0] bg-white p-6">
            <h2 className="font-['Hiragino_Mincho_ProN'] text-[22px] font-semibold text-[#363842] mb-6">合計</h2>
            
            <div className="space-y-3 mb-6">
                <div className="flex justify-between">
                    <span className="text-[#81859C]">小計</span>
                    <span className="text-[#363842] font-serif">{yen(cart.subtotal_cents)}</span>
                </div>
                
                {cart.savings_cents > 0 && (
                    <div className="flex justify-between text-[#059669]">
                        <span className="text-[#81859C]">割引</span>
                        <span className="text-[#059669] font-serif">-{yen(cart.savings_cents)}</span>
                    </div>
                )}
                
                {(cart.tax_cents ?? 0) > 0 && (
                    <div className="flex justify-between">
                        <span className="text-[#81859C]">税金</span>
                        <span className="text-[#363842] font-serif">{yen(cart.tax_cents ?? 0)}</span>
                    </div>
                )}
                
                {cart.coupon_code && (
                    <div className="pt-3 border-t border-[#D8D9E0]">
                        <div className="flex justify-between items-center mb-1">
                            <span className="flex items-center">
                                <span className="text-[#81859C]">クーポン</span>
                                <span className="ml-2 px-2 py-0.5 text-xs bg-[#F3F4F6] text-[#374151]">
                                    {cart.coupon_code}
                                </span>
                            </span>
                            <div className="flex items-center gap-2">
                                <span className="text-[#DC2626] font-serif">-{yen(cart.coupon_discount_cents || 0)}</span>
                                <button
                                    type="button"
                                    onClick={onRemoveCoupon}
                                    disabled={couponBusy}
                                    className="text-xs text-[#81859C] hover:text-[#363842] underline disabled:cursor-not-allowed"
                                >
                                    削除
                                </button>
                            </div>
                        </div>
                        {cart.coupon_summary && (
                            <div className="text-xs text-[#81859C]">{cart.coupon_summary}</div>
                        )}
                        {(cart.coupon_line_names?.length ?? 0) > 0 && (
                            <div className="text-xs text-[#81859C]">
                                {cart.coupon_line_names?.[0] === 'すべての商品'
                                    ? 'カート内のすべての商品に適用'
                                    : `適用対象: ${cart.coupon_line_names?.join(', ')}`}
                            </div>
                        )}
                    </div>
                )}
            </div>
            
            <div className="pt-4 border-t border-[#D8D9E0]">
                <div className="flex justify-between text-[#363842] font-semibold mb-1">
                    <span className="font-['Hiragino_Mincho_ProN']">合計</span>
                    <span className="font-['Hiragino_Mincho_ProN'] font-bold text-lg">{yen(cart.total_cents)}</span>
                </div>
                <p className="text-xs text-[#81859C] text-center">税込み。配送料はチェックアウト時に計算されます。</p>
            </div>

            {/* Coupon entry */}
            <div className="mt-6 pt-4 border-t border-[#D8D9E0]">
                <div className="flex justify-between items-center mb-3">
                    <span className="text-[#363842] font-medium">クーポンコードをお持ちですか？</span>
                    {cart.coupon_code && (
                        <button
                            type="button"
                            onClick={onRemoveCoupon}
                            disabled={couponBusy}
                            className="text-xs text-[#81859C] hover:text-[#363842] underline disabled:cursor-not-allowed"
                        >
                            現在のクーポンを削除
                        </button>
                    )}
                </div>
                
                <div className="flex gap-2">
                    <Input
                        value={couponInput}
                        onChange={(e) => setCouponInput(e.target.value)}
                        placeholder="コードを入力"
                        className="flex-1 h-[40px] border-[#D8D9E0] bg-white text-[#363842]"
                    />
                    <Button 
                        onClick={onApplyCoupon}
                        disabled={couponBusy || !couponInput.trim()}
                        variant="default"
                        className="h-[40px] bg-[#EAB308] text-[#363842] hover:bg-amber-500 rounded-none"
                    >
                        適用
                    </Button>
                </div>
                
                {cart.coupon_code && couponInput !== cart.coupon_code && (
                    <p className="mt-2 text-xs text-[#81859C]">
                        新しいコードを入力して「適用」ボタンを押すと、現在のクーポンを置き換えます。
                    </p>
                )}
            </div>

            {couponError && (
                <div className="mt-3 px-3 py-2 text-xs text-[#DC2626] bg-[#FEF2F2]">{couponError}</div>
            )}
            {couponNotice && !couponError && (
                <div className="mt-3 px-3 py-2 text-xs text-[#059669] bg-[#ECFDF5]">{couponNotice}</div>
            )}

            <Button 
                asChild
                className="w-full mt-6 h-12 bg-[#363842] hover:bg-gray-800 text-white text-base font-medium rounded-none"
            >
                <a href="/checkout">チェックアウトに進む</a>
            </Button>

            <div className="mt-4 text-center">
                <a href="/products" className="text-sm text-[#81859C] hover:text-[#363842] underline">
                    買い物を続ける
                </a>
            </div>
        </div>
    );
};

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
        <div className="min-h-screen bg-[#FCFCF7]">
            <HomeNavigation />
            <div className="max-w-[1440px] mx-auto px-4 py-6">
                <Head title="ショッピングカート" />

                <div className="mb-8">
                    <h1 className="font-['Hiragino_Mincho_ProN'] text-2xl font-semibold text-[#363842] mb-2">ショッピングカート</h1>
                    <p className="text-sm text-[#81859C]">{hasItems ? `${cart.lines.length} 個の商品` : '商品がありません'}</p>
                </div>

                {!hasItems && (
                    <div className="flex flex-col items-center justify-center py-16 border border-[#D8D9E0] bg-white">
                        <p className="mb-6 text-[#81859C] text-center">カートに商品がありません。</p>
                        <Button asChild className="bg-[#363842] hover:bg-gray-800 text-white rounded-none">
                            <a href="/products">買い物を続ける</a>
                        </Button>
                    </div>
                )}

                {hasItems && (
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Lines */}
                        <div className="lg:col-span-2">
                            <div className="border border-[#D8D9E0] bg-white p-6">
                                {cart.lines.map((line) => (
                                    <CartLine
                                        key={line.line_id}
                                        line={line}
                                        busyLine={busyLine}
                                        couponLineIdSet={couponLineIdSet}
                                        onUpdateQty={updateQty}
                                        onRemoveLine={removeLine}
                                    />
                                ))}
                                
                                <div className="pt-4 flex justify-end">
                                    <Button
                                        onClick={refreshCart}
                                        disabled={loading}
                                        variant="outline"
                                        className="border-[#363842] text-[#363842] bg-white hover:bg-[#363842] hover:text-white rounded-none h-10"
                                    >
                                        {loading ? '更新中…' : '更新'}
                                    </Button>
                                </div>
                            </div>
                            
                            <div className="mt-4 text-center">
                                <a href="/products" className="text-sm text-[#81859C] hover:text-[#363842] underline">
                                    買い物を続ける
                                </a>
                            </div>
                        </div>

                        {/* Summary */}
                        <div className="lg:col-span-1">
                            <CartSummary
                                cart={cart}
                                couponInput={couponInput}
                                couponBusy={couponBusy}
                                couponError={couponError}
                                couponNotice={couponNotice}
                                onApplyCoupon={applyCoupon}
                                onRemoveCoupon={removeCouponFromCart}
                                setCouponInput={setCouponInput}
                            />
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}