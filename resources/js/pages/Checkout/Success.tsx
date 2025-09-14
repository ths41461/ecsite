import { Head } from '@inertiajs/react'
import { useEffect, useMemo, useState } from 'react'
import OrderSummary, { type OrderDTO as SummaryOrderDTO } from '@/components/OrderSummary'

type Item = { name: string; sku: string; qty: number; unit_price_yen: number; line_total_yen: number }
type Payment = { id: number; status_id: number | null; processed_at: string | null }
type OrderDTO = {
  order_number: string
  status?: string | null
  status_id: number | null
  subtotal_yen: number
  discount_yen: number
  shipping_yen: number
  tax_yen: number
  total_yen: number
  email?: string
  confirmation_emailed_at?: string | null
  cancellation_emailed_at?: string | null
  payments: Payment[]
  items: Item[]
  timeline?: { status: string; changed_at: string }[]
}

function yen(y: number) {
  return y.toLocaleString(undefined, { style: 'currency', currency: 'JPY' })
}

type PageProps = { order: OrderDTO; session_id?: string | null }

export default function CheckoutSuccess({ order: initialOrder, session_id }: PageProps) {
  const [order, setOrder] = useState<OrderDTO>(initialOrder)
  const paid = useMemo(() => {
    return !!order.payments?.some((p) => p.status_id && p.status_id > 0) && !!order.payments?.some((p) => p.processed_at)
  }, [order])

  useEffect(() => {
    // Poll until webhook processed the payment
    let timer: any
    let attempts = 0
    const poll = async () => {
      attempts++
      try {
        const res = await fetch(`/orders/${encodeURIComponent(order.order_number)}`, { headers: { Accept: 'application/json' } })
        if (res.ok) {
          const data: OrderDTO = await res.json()
          setOrder(data)
          // stop if processed
          if (data.payments?.some((p) => p.processed_at)) return
        }
      } catch {}
      if (attempts < 10) timer = setTimeout(poll, 1500)
    }
    if (!paid) poll()
    return () => timer && clearTimeout(timer)
  }, [])

  return (
    <div className="mx-auto max-w-3xl px-4 py-10">
      <Head title="Order Success" />
      <div className="mb-2 flex items-center gap-3">
        <h1 className="text-2xl font-semibold">Order details</h1>
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
      <p className="mb-1 text-sm text-neutral-600">Order #{order.order_number}</p>
      {(order.confirmation_emailed_at || order.cancellation_emailed_at) && order.email && (
        <p className="mb-6 text-xs text-neutral-500">We sent a copy to {order.email}.</p>
      )}

      <OrderSummary order={order as unknown as SummaryOrderDTO} />

      <div className="mb-6 rounded-xl border p-4">
        <h2 className="mb-3 text-lg font-semibold">Payment</h2>
        {order.payments?.some((p) => p.processed_at) ? (
          <div className="rounded-md bg-emerald-50 px-3 py-2 text-emerald-700">Payment processed.</div>
        ) : (
          <div className="rounded-md bg-amber-50 px-3 py-2 text-amber-700">Waiting for confirmation…</div>
        )}
      </div>

      {order.timeline && order.timeline.length > 0 && (
        <div className="mb-6 rounded-xl border p-4">
          <h2 className="mb-3 text-lg font-semibold">Order Timeline</h2>
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
        <a href={`/orders/${encodeURIComponent(order.order_number)}/view`} className="text-sm text-neutral-700 hover:underline" target="_blank" rel="noreferrer">View order</a>
      </div>
    </div>
  )
}
