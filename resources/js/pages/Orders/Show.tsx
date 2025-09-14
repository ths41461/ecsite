import { Head } from '@inertiajs/react'
import OrderSummary, { type OrderDTO as SummaryOrderDTO } from '@/components/OrderSummary'

type TimelineEntry = { status: string; changed_at: string }
type Payment = { id: number; status_id: number | null; processed_at: string | null }

type PageProps = {
  order: SummaryOrderDTO & {
    payments?: Payment[]
    timeline?: TimelineEntry[]
    status?: string | null
    email?: string
    confirmation_emailed_at?: string | null
    cancellation_emailed_at?: string | null
  }
}

export default function OrdersShow({ order }: PageProps) {
  return (
    <div className="mx-auto max-w-3xl px-4 py-10">
      <Head title={`Order ${order.order_number}`} />
      <div className="mb-1 flex items-center gap-3">
        <h1 className="text-2xl font-semibold">Order #{order.order_number}</h1>
        {(() => {
          const isCanceled = order.status === 'canceled'
          const isPaid = order.payments?.some((p) => p.processed_at)
          const label = isCanceled ? 'Canceled' : isPaid ? 'Paid' : 'Pending'
          const cls = isCanceled
            ? 'bg-rose-100 text-rose-800'
            : isPaid
            ? 'bg-emerald-100 text-emerald-800'
            : 'bg-amber-100 text-amber-800'
          return <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${cls}`}>{label}</span>
        })()}
      </div>
      {(order.confirmation_emailed_at || order.cancellation_emailed_at) && order.email && (
        <p className="mb-4 text-xs text-neutral-500">We sent a copy to {order.email}.</p>
      )}
      <OrderSummary order={order} />
      {order.timeline && order.timeline.length > 0 && (
        <div className="mb-6 rounded-xl border p-4">
          <h2 className="mb-3 text-lg font-semibold">Timeline</h2>
          <ul className="space-y-2">
            {order.timeline.map((t, i) => (
              <li key={i} className="flex items-center justify-between text-sm">
                <span className="capitalize">{t.status.replace('_', ' ')}</span>
                <span className="text-neutral-500">{new Date(t.changed_at).toLocaleString()}</span>
              </li>
            ))}
          </ul>
        </div>
      )}
      <div className="flex items-center gap-4">
        <a href="/products" className="text-sm text-neutral-700 hover:underline">Continue shopping</a>
        <a href="/" className="text-sm text-neutral-700 hover:underline">Home</a>
      </div>
    </div>
  )}
