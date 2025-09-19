import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEvent, useEffect, useMemo, useState } from 'react';

import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { destroy as destroyCouponRoute, store as storeCouponRoute, update as updateCouponRoute } from '@/routes/coupons';

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

type ProductOption = {
    id: number;
    name: string;
    slug: string;
};

type PaginationLink = { url: string | null; label: string; active: boolean };

type Props = {
    coupons: {
        data: Coupon[];
        links: PaginationLink[];
    };
    productOptions: ProductOption[];
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
    product_ids: number[];
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
    product_ids: [],
};


type StatusFilter = 'all' | 'active' | 'scheduled' | 'expired' | 'inactive';
type StatusMeta = { label: string; tone: 'neutral' | 'warning' | 'danger' | 'success' };

const statusFilters: { key: StatusFilter; label: string }[] = [
    { key: 'all', label: 'All' },
    { key: 'active', label: 'Active' },
    { key: 'scheduled', label: 'Scheduled' },
    { key: 'expired', label: 'Expired' },
    { key: 'inactive', label: 'Inactive' },
];

type WizardStep = 'basics' | 'discount' | 'schedule' | 'eligibility' | 'review';
type WizardStepDefinition = { key: WizardStep; title: string; description: string };

const wizardSteps: WizardStepDefinition[] = [
    { key: 'basics', title: 'Basics', description: 'Code and description' },
    { key: 'discount', title: 'Discount', description: 'Value and exclusions' },
    { key: 'schedule', title: 'Schedule', description: 'Timing and limits' },
    { key: 'eligibility', title: 'Eligibility', description: 'Products and thresholds' },
    { key: 'review', title: 'Review', description: 'Preview and launch' },
];

type StatCardProps = {
    label: string;
    value: number;
    description: string;
    tone?: 'default' | 'success' | 'warning' | 'muted';
};

function StatCard({ label, value, description, tone = 'default' }: StatCardProps) {
    const palette = {
        default: 'border-neutral-200/70 bg-neutral-50/80 text-neutral-700 shadow-sm',
        success: 'border-emerald-200/80 bg-emerald-50/70 text-emerald-700 shadow-sm',
        warning: 'border-amber-200/80 bg-amber-50/70 text-amber-700 shadow-sm',
        muted: 'border-neutral-200/70 bg-neutral-100/70 text-neutral-600 shadow-sm',
    } as const;

    return (
        <div className={`rounded-xl border px-4 py-3 transition ${palette[tone]}`}>
            <div className="text-xs font-semibold uppercase tracking-wide text-neutral-500">{label}</div>
            <div className="mt-1 text-2xl font-semibold text-neutral-800">{value}</div>
            <p className="text-xs text-neutral-600">{description}</p>
        </div>
    );
}

function describeStatus(coupon: Coupon): StatusMeta {
    if (!coupon.is_active) return { label: 'Inactive', tone: 'neutral' };

    const now = Date.now();
    const starts = coupon.starts_at ? new Date(coupon.starts_at).getTime() : null;
    if (starts && starts > now) return { label: `Scheduled • ${new Date(starts).toLocaleString()}`, tone: 'warning' };

    const ends = coupon.ends_at ? new Date(coupon.ends_at).getTime() : null;
    if (ends && ends < now) return { label: `Expired ${new Date(ends).toLocaleString()}`, tone: 'danger' };

    return { label: 'Active now', tone: 'success' };
}

function statusMatches(coupon: Coupon, filter: StatusFilter): boolean {
    if (filter === 'all') return true;
    const status = describeStatus(coupon);
    if (filter === 'inactive') return !coupon.is_active;
    if (filter === 'scheduled') return status.tone === 'warning';
    if (filter === 'expired') return status.tone === 'danger';
    if (filter === 'active') return status.tone === 'success';
    return true;
}

function discountSummary(coupon: Coupon): string {
    return coupon.type === 'percent' ? `${coupon.value}% off` : `¥${coupon.value.toLocaleString('en-US')} off`;
}

function formatYen(value?: number | null): string {
    if (value === null || value === undefined || Number.isNaN(value)) return 'None';
    return `¥${value.toLocaleString('en-US')}`;
}

function usageSummary(coupon: Coupon): string {
    const max = coupon.max_uses ?? Infinity;
    const used = coupon.used_count;
    if (max === Infinity) return `${used.toLocaleString()} redemptions`;
    return `${used.toLocaleString()} of ${max.toLocaleString()} used`;
}

function statusBadgeClasses(tone: StatusMeta['tone']): string {
    switch (tone) {
        case 'success':
            return 'bg-emerald-100 text-emerald-700';
        case 'warning':
            return 'bg-amber-100 text-amber-700';
        case 'danger':
            return 'bg-rose-100 text-rose-700';
        default:
            return 'bg-neutral-200 text-neutral-700';
    }
}

type CouponListProps = {
    coupons: Coupon[];
    paginationLinks: PaginationLink[];
    activeId: number | null;
    search: string;
    onSearchChange: (value: string) => void;
    statusFilter: StatusFilter;
    onStatusChange: (value: StatusFilter) => void;
    onSelect: (coupon: Coupon) => void;
    onToggleActive: (coupon: Coupon) => void;
    onDelete: (coupon: Coupon) => void;
};

function CouponListPanel({
    coupons,
    paginationLinks,
    activeId,
    search,
    onSearchChange,
    statusFilter,
    onStatusChange,
    onSelect,
    onToggleActive,
    onDelete,
}: CouponListProps) {
    const hasCoupons = coupons.length > 0;

    return (
        <aside className="lg:sticky lg:top-28">
            <div
                className="flex min-h-[min(760px,75vh)] flex-col overflow-hidden rounded-3xl border border-neutral-200/80 bg-neutral-50/85 shadow-md backdrop-blur"
            >
                <header className="flex items-start justify-between gap-3 border-b border-neutral-200/70 px-6 py-5">
                    <div className="space-y-1">
                        <h2 className="text-sm font-semibold uppercase tracking-wide text-neutral-600">Coupon Control Center</h2>
                        <p className="text-xs text-neutral-500">Browse, filter, and jump into any coupon in seconds.</p>
                    </div>
                    <div className="rounded-full border border-neutral-300/80 bg-neutral-100/70 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-neutral-600">
                        {hasCoupons ? `${coupons.length} entr${coupons.length === 1 ? 'y' : 'ies'}` : 'No entries'}
                    </div>
                </header>

                <div className="grid gap-4 border-b border-neutral-200/60 px-6 py-5">
                    <label className="space-y-2">
                        <span className="text-xs font-semibold uppercase tracking-wide text-neutral-500">Quick search</span>
                        <div className="relative">
                            <input
                                value={search}
                                onChange={(event) => onSearchChange(event.target.value)}
                                className="w-full rounded-lg border border-neutral-200 bg-neutral-50/90 px-3 py-2 text-sm text-neutral-700 shadow-inner focus:border-neutral-900 focus:ring-neutral-900"
                                placeholder="Find by code, description, or product"
                            />
                            <span className="pointer-events-none absolute inset-y-0 right-3 hidden items-center text-[11px] uppercase tracking-wide text-neutral-400 sm:flex">
                                Ctrl / ⌘ K
                            </span>
                        </div>
                    </label>

                    <div className="space-y-3">
                        <p className="text-xs font-semibold uppercase tracking-wide text-neutral-500">Status</p>
                        <div className="flex flex-wrap gap-2">
                            {statusFilters.map((chip) => (
                                <button
                                    key={chip.key}
                                    type="button"
                                    onClick={() => onStatusChange(chip.key)}
                                    className={`rounded-full px-3 py-1 text-xs font-semibold transition ${
                                        statusFilter === chip.key
                                            ? 'bg-neutral-900 text-white shadow-sm'
                                            : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
                                    }`}
                                >
                                    {chip.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-3 text-[11px] text-neutral-500">
                        <span className="rounded-full bg-neutral-200/70 px-3 py-1 font-semibold uppercase tracking-wide text-neutral-600">
                            Active view: {statusFilter}
                        </span>
                        <span>Tip: select a coupon to edit on the right.</span>
                    </div>
                </div>

                <div className="flex-1 space-y-3 overflow-y-auto px-6 py-5">
                    {!hasCoupons && (
                        <p className="rounded-2xl border border-dashed border-neutral-300 px-4 py-6 text-sm text-neutral-500">
                            No coupons match your filters. Adjust the search or status filter to see more results.
                        </p>
                    )}

                    {coupons.map((coupon) => {
                        const status = describeStatus(coupon);
                        const isActive = activeId === coupon.id;

                        return (
                            <article
                                key={coupon.id}
                                className={`group rounded-2xl border px-4 py-4 transition-all duration-200 ${
                                    isActive
                                        ? 'border-neutral-900 bg-neutral-900/[0.06] shadow-lg shadow-neutral-900/5 ring-1 ring-neutral-900/10'
                                        : 'border-transparent bg-neutral-100/70 hover:border-neutral-300 hover:bg-neutral-100 hover:shadow-md'
                                }`}
                            >
                                <button type="button" onClick={() => onSelect(coupon)} className="w-full text-left">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="rounded-md bg-neutral-900 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-neutral-50">
                                                    {coupon.code}
                                                </span>
                                                <span className="rounded-full bg-neutral-200 px-2 py-0.5 text-[11px] font-medium uppercase tracking-wide text-neutral-600">
                                                    {coupon.type === 'percent' ? 'Percent' : 'Fixed'}
                                                </span>
                                            </div>
                                            <p className="text-sm font-medium text-neutral-700">{discountSummary(coupon)}</p>
                                            <p className="text-xs text-neutral-500">
                                                {coupon.description ? coupon.description : 'No description provided yet.'}
                                            </p>
                                            <div className="flex flex-wrap items-center gap-3 text-[11px] text-neutral-500">
                                                <span>{usageSummary(coupon)}</span>
                                                {coupon.starts_at && <span>Starts {new Date(coupon.starts_at).toLocaleDateString()}</span>}
                                                {coupon.ends_at && <span>Ends {new Date(coupon.ends_at).toLocaleDateString()}</span>}
                                            </div>
                                        </div>
                                        <span className={`rounded-full px-3 py-1 text-xs font-semibold ${statusBadgeClasses(status.tone)}`}>
                                            {status.label}
                                        </span>
                                    </div>
                                </button>

                                <div className="mt-4 flex flex-wrap items-center justify-between gap-3 text-[11px]">
                                    <div className="flex flex-wrap items-center gap-2 text-neutral-500">
                                        {coupon.min_subtotal_yen && <span>Min {formatYen(coupon.min_subtotal_yen)}</span>}
                                        {coupon.max_discount_yen && <span>Cap {formatYen(coupon.max_discount_yen)}</span>}
                                        {coupon.product_names.length > 0 && <span>{coupon.product_names.length} product(s)</span>}
                                        {coupon.exclude_sale_items && <span>Excludes sale</span>}
                                    </div>
                                    <div className="flex items-center gap-2 text-xs">
                                        <button
                                            type="button"
                                            onClick={() => onToggleActive(coupon)}
                                            className="rounded-full border border-neutral-300 px-3 py-1 font-semibold text-neutral-600 transition hover:border-neutral-400 hover:bg-neutral-100"
                                        >
                                            {coupon.is_active ? 'Pause' : 'Activate'}
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => onDelete(coupon)}
                                            className="rounded-full border border-rose-200 px-3 py-1 font-semibold text-rose-600 transition hover:border-rose-300 hover:bg-rose-50"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </article>
                        );
                    })}
                </div>

                {paginationLinks.length > 1 && (
                    <nav className="flex flex-wrap gap-2 border-t border-neutral-200/60 px-6 py-4">
                        {paginationLinks.map((link, index) => (
                            <Link
                                key={index}
                                href={link.url ?? '#'}
                                className={`rounded-full px-3 py-1 text-xs font-semibold transition ${
                                    link.active
                                        ? 'bg-neutral-900 text-white'
                                        : link.url
                                          ? 'border border-neutral-300 text-neutral-600 hover:bg-neutral-100'
                                          : 'cursor-default border border-transparent text-neutral-400'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </nav>
                )}
            </div>
        </aside>
    );
}

type ProductSelectorProps = {
    selected: ProductOption[];
    options: ProductOption[];
    search: string;
    onSearchChange: (value: string) => void;
    onAdd: (productId: number) => void;
    onRemove: (productId: number) => void;
};

function ProductSelector({ selected, options, search, onSearchChange, onAdd, onRemove }: ProductSelectorProps) {
    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between text-sm font-semibold text-neutral-700">
                <span>Eligible products</span>
                <span className="text-xs text-neutral-500">{selected.length === 0 ? 'All products eligible' : `${selected.length} selected`}</span>
            </div>

            <div className="flex flex-wrap gap-2">
                {selected.length === 0 && <span className="rounded-full bg-neutral-200/60 px-3 py-1 text-xs text-neutral-600">All products</span>}
                {selected.map((product) => (
                    <span key={product.id} className="inline-flex items-center gap-2 rounded-full bg-neutral-300/60 px-3 py-1 text-xs text-neutral-700">
                        <span>{product.name}</span>
                        <button
                            type="button"
                            onClick={() => onRemove(product.id)}
                            className="text-neutral-500 transition hover:text-neutral-700"
                            aria-label={`Remove ${product.name}`}
                        >
                            ×
                        </button>
                    </span>
                ))}
            </div>

            <div className="space-y-2">
                <input
                    value={search}
                    onChange={(event) => onSearchChange(event.target.value)}
                    placeholder="Search products by name or slug"
                    className="w-full rounded-lg border border-neutral-200 bg-neutral-50/90 px-3 py-2 text-sm text-neutral-700 shadow-inner focus:border-neutral-900 focus:ring-neutral-900"
                    type="search"
                />
                <div className="max-h-48 overflow-y-auto rounded-lg border border-neutral-200 bg-neutral-50/80 text-sm">
                    {options.length === 0 ? (
                        <p className="px-3 py-2 text-neutral-500">No matches. Try a different search.</p>
                    ) : (
                        options.map((product) => (
                            <button
                                type="button"
                                key={product.id}
                                onClick={() => onAdd(product.id)}
                                className="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-neutral-100"
                            >
                                <span className="font-medium text-neutral-700">{product.name}</span>
                                <span className="text-xs text-neutral-500">{product.slug}</span>
                            </button>
                        ))
                    )}
                </div>
            </div>
        </div>
    );
}

type CouponPreviewProps = {
    summary: {
        headline: string;
        description: string;
        discount: string;
        schedule: string;
        limits: string;
        minSubtotal: string;
        cap: string;
        saleNotice: string;
        eligibleProducts: string;
    };
    isActive: boolean;
    className?: string;
};

function CouponPreview({ summary, isActive, className }: CouponPreviewProps) {
    return (
        <div className={`rounded-2xl border border-neutral-200/70 bg-neutral-50/80 p-4 ${className ?? ''}`}>
            <h3 className="text-sm font-semibold uppercase tracking-wide text-neutral-500">Customer-facing preview</h3>

            <div className="mt-3 space-y-3">
                <div className="flex items-start justify-between gap-4">
                    <div className="space-y-1">
                        <div className="text-base font-semibold text-neutral-800">{summary.headline}</div>
                        <div className="text-sm text-neutral-600">{summary.description}</div>
                    </div>
                    <div className="text-right text-lg font-semibold text-neutral-800">{summary.discount}</div>
                </div>

                <dl className="grid gap-3 text-xs text-neutral-600 md:grid-cols-2">
                    <div className="space-y-1">
                        <dt className="font-semibold text-neutral-700">Status</dt>
                        <dd className="text-neutral-600">{isActive ? 'Active' : 'Inactive'}</dd>
                    </div>
                    <div className="space-y-1">
                        <dt className="font-semibold text-neutral-700">Schedule</dt>
                        <dd className="text-neutral-600">{summary.schedule}</dd>
                    </div>
                    <div className="space-y-1">
                        <dt className="font-semibold text-neutral-700">Limits</dt>
                        <dd className="text-neutral-600">{summary.limits}</dd>
                    </div>
                    <div className="space-y-1">
                        <dt className="font-semibold text-neutral-700">Min subtotal</dt>
                        <dd className="text-neutral-600">{summary.minSubtotal}</dd>
                    </div>
                    <div className="space-y-1">
                        <dt className="font-semibold text-neutral-700">Discount cap</dt>
                        <dd className="text-neutral-600">{summary.cap}</dd>
                    </div>
                    <div className="space-y-1">
                        <dt className="font-semibold text-neutral-700">Sale items</dt>
                        <dd className="text-neutral-600">{summary.saleNotice}</dd>
                    </div>
                    <div className="space-y-1 md:col-span-2">
                        <dt className="font-semibold text-neutral-700">Eligibility</dt>
                        <dd className="text-neutral-600">{summary.eligibleProducts}</dd>
                    </div>
                </dl>
            </div>
        </div>
    );
}

type CouponFormProps = {
    editing: Coupon | null;
    data: FormShape;
    errors: Record<string, string>;
    processing: boolean;
    onSubmit: (event: FormEvent) => void;
    onReset: () => void;
    onRemoveCoupon: () => void;
    onFieldChange: <K extends keyof FormShape>(key: K, value: FormShape[K]) => void;
    selectedProducts: ProductOption[];
    productOptions: ProductOption[];
    productSearch: string;
    onProductSearchChange: (value: string) => void;
    onAddProduct: (productId: number) => void;
    onRemoveProduct: (productId: number) => void;
};

function CouponForm({
    editing,
    data,
    errors,
    processing,
    onSubmit,
    onReset,
    onRemoveCoupon,
    onFieldChange,
    selectedProducts,
    productOptions,
    productSearch,
    onProductSearchChange,
    onAddProduct,
    onRemoveProduct,
}: CouponFormProps) {
    const previewSummary = {
        headline: data.code || 'COUPONCODE',
        description: data.description || 'Describe this coupon so shoppers immediately understand it.',
        discount: data.type === 'percent' ? `${data.value || 0}% off` : formatYen(typeof data.value === 'number' ? data.value : 0),
        schedule: `${data.starts_at || 'Immediate'} → ${data.ends_at || 'No end'}`,
        limits: `${data.max_uses || '∞'} total • ${data.max_uses_per_user || '∞'} per customer`,
        minSubtotal: data.min_subtotal_yen ? formatYen(Number(data.min_subtotal_yen)) : 'None',
        cap: data.max_discount_yen ? formatYen(Number(data.max_discount_yen)) : 'None',
        saleNotice: data.exclude_sale_items ? 'Excludes sale items' : 'Includes sale items',
        eligibleProducts: selectedProducts.length === 0 ? 'All products eligible' : `${selectedProducts.length} product(s) targeted`,
    };

    return (
        <section className="space-y-6">
            <div className="rounded-2xl border border-neutral-200/70 bg-neutral-50/85 p-6 shadow-lg backdrop-blur">
                <header className="flex flex-col gap-2 border-b border-neutral-200/60 pb-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <h2 className="text-xl font-semibold text-neutral-800">
                                {editing ? `Edit coupon • ${editing.code}` : 'Create a new coupon'}
                            </h2>
                            <p className="text-sm text-neutral-500">
                                {editing ? 'Update the coupon details and save your changes.' : 'Fill in the details below to publish a new coupon.'}
                            </p>
                        </div>
                        <label className="inline-flex items-center gap-2 rounded-full border border-neutral-300 bg-neutral-50/90 px-3 py-2 text-sm font-medium text-neutral-600 shadow-sm transition hover:border-neutral-400">
                            <input
                                type="checkbox"
                                checked={data.is_active}
                                onChange={(event) => onFieldChange('is_active', event.target.checked)}
                                className="h-4 w-4 rounded border-neutral-300 text-neutral-900 focus:ring-neutral-900"
                            />
                            Active now
                        </label>
                    </div>
                </header>

                <form onSubmit={onSubmit} className="mt-5 space-y-6">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <label className="text-xs font-semibold tracking-wide text-neutral-500 uppercase">Code</label>
                            <input
                                value={data.code}
                                onChange={(event) => onFieldChange('code', event.target.value.toUpperCase())}
                                className="w-full rounded-lg border px-3 py-2 text-sm focus:border-neutral-900 focus:ring-neutral-900"
                                placeholder="Example: SPRING25"
                                required
                            />
                            <p className="text-xs text-neutral-500">Shoppers will enter this exact code at checkout. Use uppercase for clarity.</p>
                            {errors.code && <p className="text-xs text-rose-600">{errors.code}</p>}
                        </div>

                        <div className="space-y-2">
                            <label className="text-xs font-semibold tracking-wide text-neutral-500 uppercase">Short description</label>
                            <input
                                value={data.description}
                                onChange={(event) => onFieldChange('description', event.target.value)}
                                className="w-full rounded-lg border px-3 py-2 text-sm focus:border-neutral-900 focus:ring-neutral-900"
                                placeholder="Shown to shoppers (optional)"
                            />
                            {errors.description && <p className="text-xs text-rose-600">{errors.description}</p>}
                        </div>
                    </div>

                    <div className="rounded-xl border border-neutral-200/70 bg-neutral-50/80 p-4">
                        <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-neutral-500">Discount</h3>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <label className="text-xs font-semibold tracking-wide text-neutral-500 uppercase">Type</label>
                                <select
                                    value={data.type}
                                    onChange={(event) => onFieldChange('type', event.target.value as 'percent' | 'fixed')}
                                    className="w-full rounded-lg border px-3 py-2 text-sm focus:border-neutral-900 focus:ring-neutral-900"
                                >
                                    <option value="percent">Percent</option>
                                    <option value="fixed">Fixed amount (¥)</option>
                                </select>
                                {errors.type && <p className="text-xs text-rose-600">{errors.type}</p>}
                            </div>

                            <div className="space-y-2">
                                <label className="text-xs font-semibold tracking-wide text-neutral-500 uppercase">Value</label>
                                <input
                                    type="number"
                                    min={1}
                                    value={data.value}
                                    onChange={(event) => onFieldChange('value', event.target.value === '' ? '' : Number(event.target.value))}
                                    className="w-full rounded-lg border px-3 py-2 text-sm focus:border-neutral-900 focus:ring-neutral-900"
                                    placeholder={data.type === 'percent' ? 'e.g. 10 for 10% off' : 'Amount in ¥'}
                                    required
                                />
                                {errors.value && <p className="text-xs text-rose-600">{errors.value}</p>}
                            </div>

                            <div className="space-y-2">
                                <label className="text-xs font-semibold tracking-wide text-neutral-500 uppercase">Maximum discount (¥)</label>
                                <input
                                    type="number"
                                    min={0}
                                    value={data.max_discount_yen}
                                    onChange={(event) =>
                                        onFieldChange('max_discount_yen', event.target.value === '' ? '' : Number(event.target.value))
                                    }
                                    className="w-full rounded-lg border px-3 py-2 text-sm focus:border-neutral-900 focus:ring-neutral-900"
                                    placeholder="Optional cap"
                                />
                                {errors.max_discount_yen && <p className="text-xs text-rose-600">{errors.max_discount_yen}</p>}
                            </div>

                            <div className="flex items-center gap-2 pt-6">
                                <input
                                    type="checkbox"
                                    checked={data.exclude_sale_items}
                                    onChange={(event) => onFieldChange('exclude_sale_items', event.target.checked)}
                                    className="h-4 w-4 rounded border-neutral-300 text-neutral-900 focus:ring-neutral-900"
                                />
                                <span className="text-sm text-neutral-600">Exclude sale-priced items</span>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-neutral-200/70 bg-neutral-50/80 p-4">
                        <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-neutral-500">Limits & schedule</h3>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <label className="text-xs font-semibold tracking-wide text-neutral-500 uppercase">Starts at</label>
                                <input
                                    type="datetime-local"
                                    value={data.starts_at}
                                    onChange={(event) => onFieldChange('starts_at', event.target.value)}
                                    className="w-full rounded-lg border px-3 py-2 text-sm focus:border-neutral-900 focus:ring-neutral-900"
                                />
                                {errors.starts_at && <p className="text-xs text-rose-600">{errors.starts_at}</p>}
                            </div>

                            <div className="space-y-2">
                                <label className="text-xs font-semibold tracking-wide text-neutral-500 uppercase">Ends at</label>
                                <input
                                    type="datetime-local"
                                    value={data.ends_at}
                                    onChange={(event) => onFieldChange('ends_at', event.target.value)}
                                    className="w-full rounded-lg border px-3 py-2 text-sm focus:border-neutral-900 focus:ring-neutral-900"
                                />
                                {errors.ends_at && <p className="text-xs text-rose-600">{errors.ends_at}</p>}
                            </div>

                            <div className="space-y-2">
                                <label className="text-xs font-semibold tracking-wide text-neutral-500 uppercase">Max uses (total)</label>
                                <input
                                    type="number"
                                    min={0}
                                    value={data.max_uses}
                                    onChange={(event) => onFieldChange('max_uses', event.target.value === '' ? '' : Number(event.target.value))}
                                    className="w-full rounded-lg border px-3 py-2 text-sm focus:border-neutral-900 focus:ring-neutral-900"
                                    placeholder="Leave blank for unlimited"
                                />
                                {errors.max_uses && <p className="text-xs text-rose-600">{errors.max_uses}</p>}
                            </div>

                            <div className="space-y-2">
                                <label className="text-xs font-semibold tracking-wide text-neutral-500 uppercase">Max uses per customer</label>
                                <input
                                    type="number"
                                    min={0}
                                    value={data.max_uses_per_user}
                                    onChange={(event) =>
                                        onFieldChange('max_uses_per_user', event.target.value === '' ? '' : Number(event.target.value))
                                    }
                                    className="w-full rounded-lg border px-3 py-2 text-sm focus:border-neutral-900 focus:ring-neutral-900"
                                    placeholder="Leave blank for unlimited"
                                />
                                {errors.max_uses_per_user && <p className="text-xs text-rose-600">{errors.max_uses_per_user}</p>}
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-neutral-200/70 bg-neutral-50/80 p-4">
                        <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-neutral-500">Eligibility</h3>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <label className="text-xs font-semibold tracking-wide text-neutral-500 uppercase">Minimum subtotal (¥)</label>
                                <input
                                    type="number"
                                    min={0}
                                    value={data.min_subtotal_yen}
                                    onChange={(event) =>
                                        onFieldChange('min_subtotal_yen', event.target.value === '' ? '' : Number(event.target.value))
                                    }
                                    className="w-full rounded-lg border px-3 py-2 text-sm focus:border-neutral-900 focus:ring-neutral-900"
                                    placeholder="Optional"
                                />
                                {errors.min_subtotal_yen && <p className="text-xs text-rose-600">{errors.min_subtotal_yen}</p>}
                            </div>
                        </div>

                        <ProductSelector
                            selected={selectedProducts}
                            options={productOptions}
                            search={productSearch}
                            onSearchChange={onProductSearchChange}
                            onAdd={onAddProduct}
                            onRemove={onRemoveProduct}
                        />
                    </div>

                    <CouponPreview summary={previewSummary} isActive={data.is_active} />

                    <div className="flex flex-wrap items-center gap-3">
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-md bg-neutral-900 px-4 py-2 text-sm font-semibold text-white hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            {processing ? 'Saving…' : editing ? 'Update coupon' : 'Create coupon'}
                        </button>
                        {editing && (
                            <button
                                type="button"
                                onClick={onReset}
                                className="rounded-md border border-neutral-300 px-4 py-2 text-sm text-neutral-600 hover:bg-neutral-50"
                            >
                                Cancel editing
                            </button>
                        )}
                    </div>
                </form>
            </div>

            {editing && (
                <div className="rounded-3xl border border-rose-200/70 bg-rose-50/80 p-5 text-sm text-rose-700 shadow-sm">
                    <h3 className="mb-2 text-sm font-semibold tracking-wide uppercase">Danger zone</h3>
                    <p className="mb-3 text-xs text-rose-600">
                        Deleting a coupon removes it immediately. Existing orders keep their applied discount history.
                    </p>
                    <button
                        type="button"
                        onClick={onRemoveCoupon}
                        className="rounded-md border border-rose-300 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100"
                    >
                        Delete coupon
                    </button>
                </div>
            )}
        </section>
    );
}

export default function CouponsSettings({ coupons, productOptions }: Props) {
    const [editing, setEditing] = useState<Coupon | null>(null);
    const { data, setData, transform, post, put, delete: destroy, processing, reset, errors } = useForm<FormShape>(defaultForm);
    const [clockLabel, setClockLabel] = useState(() => new Date().toLocaleString());
    const [productSearch, setProductSearch] = useState('');
    const [listSearch, setListSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState<StatusFilter>('all');

    useEffect(() => {
        const id = window.setInterval(() => setClockLabel(new Date().toLocaleString()), 60000);
        return () => window.clearInterval(id);
    }, []);

    const handleFieldChange = <K extends keyof FormShape>(key: K, value: FormShape[K]) => {
        setData(key, value);
    };

    const filteredCoupons = useMemo(() => {
        const term = listSearch.trim().toLowerCase();
        return coupons.data.filter((coupon) => {
            const matchesTerm = term
                ? [coupon.code, coupon.description ?? '', coupon.product_names.join(' ')].some((field) => field.toLowerCase().includes(term))
                : true;
            return matchesTerm && statusMatches(coupon, statusFilter);
        });
    }, [coupons.data, listSearch, statusFilter]);

    const selectedProducts = useMemo(() => {
        const ids = new Set(data.product_ids);
        return productOptions.filter((product) => ids.has(product.id));
    }, [data.product_ids, productOptions]);

    const filteredProductOptions = useMemo(() => {
        const term = productSearch.trim().toLowerCase();
        const taken = new Set(data.product_ids);
        return productOptions
            .filter((option) => {
                if (taken.has(option.id)) {
                    return false;
                }
                if (!term) {
                    return true;
                }
                const haystack = `${option.name} ${option.slug}`.toLowerCase();
                return haystack.includes(term);
            })
            .slice(0, 24);
    }, [data.product_ids, productOptions, productSearch]);

    const statusCounts = useMemo(() => {
        let active = 0;
        let scheduled = 0;
        let expired = 0;
        let inactive = 0;

        coupons.data.forEach((coupon) => {
            if (!coupon.is_active) {
                inactive += 1;
                return;
            }

            const status = describeStatus(coupon);
            if (status.tone === 'success') active += 1;
            else if (status.tone === 'warning') scheduled += 1;
            else if (status.tone === 'danger') expired += 1;
        });

        return {
            total: coupons.data.length,
            active,
            scheduled,
            expired,
            inactive,
        };
    }, [coupons.data]);

    const submit = (event: FormEvent) => {
        event.preventDefault();

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
                onSuccess: () => {
                    resetForm();
                },
                preserveScroll: true,
            });
        } else {
            post(storeCouponRoute().url, {
                onSuccess: () => {
                    resetForm();
                },
                preserveScroll: true,
            });
        }
    };

    const resetForm = () => {
        reset();
        setData({ ...defaultForm });
        setEditing(null);
        setProductSearch('');
    };

    const handleDelete = (coupon: Coupon) => {
        if (!confirm(`Delete coupon ${coupon.code}? This action cannot be undone.`)) return;
        destroy(destroyCouponRoute(coupon.id).url, {
            onSuccess: () => {
                if (editing?.id === coupon.id) {
                    resetForm();
                }
            },
            preserveScroll: true,
        });
    };

    const handleToggleActive = (coupon: Coupon) => {
        const payload = {
            code: coupon.code,
            description: coupon.description,
            type: coupon.type,
            value: coupon.value,
            starts_at: coupon.starts_at,
            ends_at: coupon.ends_at,
            max_uses: coupon.max_uses,
            max_uses_per_user: coupon.max_uses_per_user,
            min_subtotal_yen: coupon.min_subtotal_yen,
            max_discount_yen: coupon.max_discount_yen,
            exclude_sale_items: coupon.exclude_sale_items,
            is_active: !coupon.is_active,
            product_ids: coupon.product_ids,
        };

        router.put(updateCouponRoute(coupon.id).url, payload, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const handleAddProduct = (productId: number) => {
        if (data.product_ids.includes(productId)) return;
        setData('product_ids', [...data.product_ids, productId]);
    };

    const handleRemoveProduct = (productId: number) => {
        setData(
            'product_ids',
            data.product_ids.filter((id) => id !== productId),
        );
    };

    return (
        <AppLayout>
            <Head title="Coupon settings" />

            <SettingsLayout fullWidth>
                <div className="mx-auto max-w-6xl space-y-10 px-4 py-10">
                    <header className="rounded-3xl border border-neutral-200/80 bg-gradient-to-br from-neutral-50 via-neutral-100 to-neutral-50 p-6 shadow-lg">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div className="space-y-1">
                                <h1 className="text-3xl font-semibold text-neutral-800">Coupons</h1>
                                <p className="text-sm text-neutral-600">
                                    Build, schedule, and optimize discounts. Local time: <span className="font-medium text-neutral-700">{clockLabel}</span>
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <button
                                    onClick={resetForm}
                                    className="rounded-md border border-neutral-300 bg-neutral-50/90 px-4 py-2 text-sm font-medium text-neutral-700 shadow-sm backdrop-blur-md transition hover:border-neutral-400 hover:bg-neutral-100"
                                >
                                    {editing ? 'Create new coupon' : 'Reset form'}
                                </button>
                            </div>
                        </div>

                        <div className="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <StatCard label="Total" value={statusCounts.total} description="Coupons in this workspace" />
                            <StatCard label="Active" value={statusCounts.active} description="Live and redeemable" tone="success" />
                            <StatCard label="Scheduled" value={statusCounts.scheduled} description="Starting soon" tone="warning" />
                            <StatCard label="Inactive & expired" value={statusCounts.inactive + statusCounts.expired} description="Paused or expired" tone="muted" />
                        </div>
                    </header>

                    <div className="space-y-10">
                        <CouponListPanel
                            coupons={filteredCoupons}
                            paginationLinks={coupons.links}
                            activeId={editing?.id ?? null}
                            search={listSearch}
                            onSearchChange={setListSearch}
                            statusFilter={statusFilter}
                            onStatusChange={setStatusFilter}
                            onSelect={(coupon) => {
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
                                    product_ids: [...coupon.product_ids],
                                });
                                setProductSearch('');
                            }}
                            onToggleActive={handleToggleActive}
                            onDelete={handleDelete}
                        />

                        <CouponForm
                            editing={editing}
                            data={data}
                            errors={errors}
                            processing={processing}
                            onSubmit={submit}
                            onReset={resetForm}
                            onRemoveCoupon={() => editing && handleDelete(editing)}
                            onFieldChange={handleFieldChange}
                            selectedProducts={selectedProducts}
                            productOptions={filteredProductOptions}
                            productSearch={productSearch}
                            onProductSearchChange={setProductSearch}
                            onAddProduct={handleAddProduct}
                            onRemoveProduct={handleRemoveProduct}
                        />
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
