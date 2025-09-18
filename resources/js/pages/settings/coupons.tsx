import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { destroy as destroyCouponRoute, index as couponsIndexRoute, store as storeCouponRoute, update as updateCouponRoute } from '@/routes/coupons';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useMemo, useState } from 'react';

type Coupon = {
    id: number;
    code: string;
    description: string | null;
    type: 'percent' | 'fixed';
    value: number;
    is_active: boolean;
    starts_at: string | null;
    ends_at: string | null;
    max_uses: number | null;
    used_count: number;
    max_uses_per_user: number | null;
    min_subtotal_yen: number | null;
    max_discount_yen: number | null;
    exclude_sale_items: boolean;
    product_ids: number[];
    product_names: string[];
};

type PaginationLink = { url: string | null; label: string; active: boolean };

type Props = {
    coupons: {
        data: Coupon[];
        links: PaginationLink[];
    };
};

type FormShape = {
    code: string;
    description: string;
    type: 'percent' | 'fixed';
    value: number | '';
    starts_at: string;
    ends_at: string;
    max_uses: number | '';
    max_uses_per_user: number | '';
    min_subtotal_yen: number | '';
    max_discount_yen: number | '';
    exclude_sale_items: boolean;
    is_active: boolean;
    product_ids: string;
};

const defaultForm: FormShape = {
    code: '',
    description: '',
    type: 'percent',
    value: '',
    starts_at: '',
    ends_at: '',
    max_uses: '',
    max_uses_per_user: '',
    min_subtotal_yen: '',
    max_discount_yen: '',
    exclude_sale_items: false,
    is_active: true,
    product_ids: '',
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Coupon settings',
        href: couponsIndexRoute().url,
    },
];

export default function CouponsSettings({ coupons }: Props) {
    const [editing, setEditing] = useState<Coupon | null>(null);
    const { data, setData, transform, post, put, delete: destroy, processing, reset, errors } = useForm<FormShape>(defaultForm);

    function resetForm() {
        reset();
        setData(defaultForm);
        setEditing(null);
    }

    function beginCreate() {
        resetForm();
    }

    function beginEdit(coupon: Coupon) {
        setEditing(coupon);
        setData({
            code: coupon.code,
            description: coupon.description ?? '',
            type: coupon.type,
            value: coupon.value,
            starts_at: coupon.starts_at ?? '',
            ends_at: coupon.ends_at ?? '',
            max_uses: coupon.max_uses ?? '',
            max_uses_per_user: coupon.max_uses_per_user ?? '',
            min_subtotal_yen: coupon.min_subtotal_yen ?? '',
            max_discount_yen: coupon.max_discount_yen ?? '',
            exclude_sale_items: coupon.exclude_sale_items,
            is_active: coupon.is_active,
            product_ids: coupon.product_ids.join(', '),
        });
    }

    function submit(e: FormEvent) {
        e.preventDefault();

        transform((formData) => ({
            ...formData,
            value: formData.value === '' ? '' : Number(formData.value),
            max_uses: formData.max_uses === '' ? null : Number(formData.max_uses),
            max_uses_per_user: formData.max_uses_per_user === '' ? null : Number(formData.max_uses_per_user),
            min_subtotal_yen: formData.min_subtotal_yen === '' ? null : Number(formData.min_subtotal_yen),
            max_discount_yen: formData.max_discount_yen === '' ? null : Number(formData.max_discount_yen),
        }));

        if (editing) {
            put(updateCouponRoute(editing.id).url, {
                onSuccess: () => resetForm(),
            });
        } else {
            post(storeCouponRoute().url, {
                onSuccess: () => resetForm(),
            });
        }
    }

    function remove(coupon: Coupon) {
        if (!confirm(`Delete coupon ${coupon.code}?`)) return;
        destroy(destroyCouponRoute(coupon.id).url, {
            onSuccess: () => {
                if (editing?.id === coupon.id) {
                    resetForm();
                }
            },
        });
    }

    const heading = useMemo(() => (editing ? `Edit coupon ${editing.code}` : 'Create coupon'), [editing]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Coupon settings" />

            <SettingsLayout fullWidth>
                <div className="mx-auto max-w-5xl px-4 py-8">
                    <div className="mb-6 flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-semibold">Coupons</h1>
                            <p className="text-sm text-neutral-500">Manage discount codes available at checkout.</p>
                        </div>
                        <button onClick={beginCreate} className="rounded-md border border-neutral-300 px-4 py-2 text-sm hover:bg-neutral-50">
                            New coupon
                        </button>
                    </div>

                    <div className="grid gap-6 md:grid-cols-[1.2fr_1fr]">
                        <div className="rounded-xl border p-4">
                            <h2 className="mb-3 text-lg font-semibold">{heading}</h2>
                            <form onSubmit={submit} className="space-y-4">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label className="text-xs font-semibold text-neutral-500 uppercase">Code</label>
                                        <input
                                            value={data.code}
                                            onChange={(e) => setData('code', e.target.value)}
                                            className="mt-1 w-full rounded-md border px-3 py-2 text-sm"
                                            required
                                        />
                                        {errors.code && <p className="mt-1 text-xs text-rose-600">{errors.code}</p>}
                                    </div>
                                    <div>
                                        <label className="text-xs font-semibold text-neutral-500 uppercase">Type</label>
                                        <select
                                            value={data.type}
                                            onChange={(e) => setData('type', e.target.value as 'percent' | 'fixed')}
                                            className="mt-1 w-full rounded-md border px-3 py-2 text-sm"
                                        >
                                            <option value="percent">Percent</option>
                                            <option value="fixed">Fixed amount (¥)</option>
                                        </select>
                                        {errors.type && <p className="mt-1 text-xs text-rose-600">{errors.type}</p>}
                                    </div>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label className="text-xs font-semibold text-neutral-500 uppercase">Value</label>
                                        <input
                                            type="number"
                                            min={1}
                                            value={data.value}
                                            onChange={(e) => setData('value', e.target.value === '' ? '' : Number(e.target.value))}
                                            className="mt-1 w-full rounded-md border px-3 py-2 text-sm"
                                            required
                                        />
                                        {errors.value && <p className="mt-1 text-xs text-rose-600">{errors.value}</p>}
                                    </div>
                                    <div>
                                        <label className="text-xs font-semibold text-neutral-500 uppercase">Description</label>
                                        <input
                                            value={data.description}
                                            onChange={(e) => setData('description', e.target.value)}
                                            className="mt-1 w-full rounded-md border px-3 py-2 text-sm"
                                            placeholder="Optional summary"
                                        />
                                        {errors.description && <p className="mt-1 text-xs text-rose-600">{errors.description}</p>}
                                    </div>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label className="text-xs font-semibold text-neutral-500 uppercase">Starts at</label>
                                        <input
                                            type="datetime-local"
                                            value={data.starts_at}
                                            onChange={(e) => setData('starts_at', e.target.value)}
                                            className="mt-1 w-full rounded-md border px-3 py-2 text-sm"
                                        />
                                        {errors.starts_at && <p className="mt-1 text-xs text-rose-600">{errors.starts_at}</p>}
                                    </div>
                                    <div>
                                        <label className="text-xs font-semibold text-neutral-500 uppercase">Ends at</label>
                                        <input
                                            type="datetime-local"
                                            value={data.ends_at}
                                            onChange={(e) => setData('ends_at', e.target.value)}
                                            className="mt-1 w-full rounded-md border px-3 py-2 text-sm"
                                        />
                                        {errors.ends_at && <p className="mt-1 text-xs text-rose-600">{errors.ends_at}</p>}
                                    </div>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label className="text-xs font-semibold text-neutral-500 uppercase">Max uses</label>
                                        <input
                                            type="number"
                                            min={0}
                                            value={data.max_uses}
                                            onChange={(e) => setData('max_uses', e.target.value === '' ? '' : Number(e.target.value))}
                                            className="mt-1 w-full rounded-md border px-3 py-2 text-sm"
                                            placeholder="Unlimited"
                                        />
                                        {errors.max_uses && <p className="mt-1 text-xs text-rose-600">{errors.max_uses}</p>}
                                    </div>
                                    <div>
                                        <label className="text-xs font-semibold text-neutral-500 uppercase">Max uses per user</label>
                                        <input
                                            type="number"
                                            min={0}
                                            value={data.max_uses_per_user}
                                            onChange={(e) => setData('max_uses_per_user', e.target.value === '' ? '' : Number(e.target.value))}
                                            className="mt-1 w-full rounded-md border px-3 py-2 text-sm"
                                            placeholder="Unlimited"
                                        />
                                        {errors.max_uses_per_user && <p className="mt-1 text-xs text-rose-600">{errors.max_uses_per_user}</p>}
                                    </div>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label className="text-xs font-semibold text-neutral-500 uppercase">Min subtotal (¥)</label>
                                        <input
                                            type="number"
                                            min={0}
                                            value={data.min_subtotal_yen}
                                            onChange={(e) => setData('min_subtotal_yen', e.target.value === '' ? '' : Number(e.target.value))}
                                            className="mt-1 w-full rounded-md border px-3 py-2 text-sm"
                                            placeholder="Optional"
                                        />
                                        {errors.min_subtotal_yen && <p className="mt-1 text-xs text-rose-600">{errors.min_subtotal_yen}</p>}
                                    </div>
                                    <div>
                                        <label className="text-xs font-semibold text-neutral-500 uppercase">Max discount (¥)</label>
                                        <input
                                            type="number"
                                            min={0}
                                            value={data.max_discount_yen}
                                            onChange={(e) => setData('max_discount_yen', e.target.value === '' ? '' : Number(e.target.value))}
                                            className="mt-1 w-full rounded-md border px-3 py-2 text-sm"
                                            placeholder="Optional cap"
                                        />
                                        {errors.max_discount_yen && <p className="mt-1 text-xs text-rose-600">{errors.max_discount_yen}</p>}
                                    </div>
                                </div>

                                <div>
                                    <label className="text-xs font-semibold text-neutral-500 uppercase">Eligible product IDs</label>
                                    <textarea
                                        value={data.product_ids}
                                        onChange={(e) => setData('product_ids', e.target.value)}
                                        placeholder="Comma-separated product IDs. Leave blank for all products."
                                        className="mt-1 w-full rounded-md border px-3 py-2 text-sm"
                                        rows={2}
                                    />
                                    {errors.product_ids && <p className="mt-1 text-xs text-rose-600">{errors.product_ids}</p>}
                                </div>

                                <div className="flex flex-wrap gap-4">
                                    <label className="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={data.exclude_sale_items}
                                            onChange={(e) => setData('exclude_sale_items', e.target.checked)}
                                        />
                                        Exclude sale items
                                    </label>
                                    <label className="flex items-center gap-2 text-sm">
                                        <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />
                                        Active
                                    </label>
                                </div>

                                <div className="flex gap-3 pt-2">
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="rounded-md bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        {processing ? 'Saving…' : editing ? 'Update coupon' : 'Create coupon'}
                                    </button>
                                    {editing && (
                                        <button
                                            type="button"
                                            onClick={resetForm}
                                            className="rounded-md border border-neutral-300 px-4 py-2 text-sm hover:bg-neutral-50"
                                        >
                                            Cancel
                                        </button>
                                    )}
                                </div>
                            </form>
                </div>

                <div className="rounded-xl border p-4">
                    <h2 className="mb-3 text-lg font-semibold">Existing coupons</h2>

                    {coupons.data.length === 0 ? (
                        <p className="text-sm text-neutral-500">No coupons yet.</p>
                    ) : (
                        <div className="space-y-3">
                            {coupons.data.map((coupon) => (
                                <div key={coupon.id} className="rounded-lg border px-3 py-3">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <div className="text-sm font-semibold">
                                                {coupon.code}
                                                {!coupon.is_active && <span className="ml-2 text-xs text-neutral-500">(inactive)</span>}
                                            </div>
                                            <div className="text-xs text-neutral-500">
                                                {coupon.type === 'percent' ? `${coupon.value}% off` : `¥${coupon.value} off`}
                                                {coupon.description ? ` — ${coupon.description}` : ''}
                                            </div>
                                        </div>
                                        <div className="flex gap-2">
                                            <button
                                                onClick={() => beginEdit(coupon)}
                                                className="rounded-md border border-neutral-300 px-3 py-1 text-xs hover:bg-neutral-50"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                onClick={() => remove(coupon)}
                                                className="rounded-md border border-neutral-300 px-3 py-1 text-xs text-rose-600 hover:bg-neutral-50"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </div>

                                    <dl className="mt-2 grid gap-1 text-xs text-neutral-600 md:grid-cols-2">
                                        <div>
                                            <span className="font-semibold">Period:</span> {coupon.starts_at ? coupon.starts_at : '—'} →{' '}
                                            {coupon.ends_at ? coupon.ends_at : '—'}
                                        </div>
                                        <div>
                                            <span className="font-semibold">Usage:</span> {coupon.used_count}/{coupon.max_uses ?? '∞'}
                                        </div>
                                        <div>
                                            <span className="font-semibold">Per user:</span> {coupon.max_uses_per_user ?? '∞'}
                                        </div>
                                        <div>
                                            <span className="font-semibold">Min subtotal:</span>{' '}
                                            {coupon.min_subtotal_yen ? `¥${coupon.min_subtotal_yen}` : 'None'}
                                        </div>
                                        <div>
                                            <span className="font-semibold">Max discount:</span>{' '}
                                            {coupon.max_discount_yen ? `¥${coupon.max_discount_yen}` : 'None'}
                                        </div>
                                        <div>
                                            <span className="font-semibold">Exclude sale items:</span> {coupon.exclude_sale_items ? 'Yes' : 'No'}
                                        </div>
                                        <div className="md:col-span-2">
                                            <span className="font-semibold">Products:</span>{' '}
                                            {coupon.product_names.length > 0 ? coupon.product_names.join(', ') : 'All products'}
                                        </div>
                                    </dl>
                                </div>
                            ))}

                            <div className="flex flex-wrap gap-2 pt-2 text-xs">
                                {coupons.links.map((link, idx) => (
                                    <Link
                                        key={idx}
                                        href={link.url || '#'}
                                        className={`rounded border px-2 py-1 ${
                                            link.active ? 'bg-neutral-900 text-white' : link.url ? 'hover:bg-neutral-100' : 'text-neutral-400'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
            </SettingsLayout>
        </AppLayout>
    );
}
