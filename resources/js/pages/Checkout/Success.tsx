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
  coupon_code?: string | null
  coupon_discount_yen?: number | null
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
          const data: any = await res.json()
          if (data?.status === 'canceled') {
            window.location.href = `/checkout/cancel/${encodeURIComponent(order.order_number)}`
            return
          }
          const dataTyped: OrderDTO = data
          setOrder(dataTyped)
          // stop if processed
          if (dataTyped.payments?.some((p) => p.processed_at)) return
        }
      } catch {}
      if (attempts < 10) timer = setTimeout(poll, 1500)
    }
    if (!paid) poll()
    return () => timer && clearTimeout(timer)
  }, [])

  return (
    <div className="mx-auto max-w-3xl px-4 py-10">
      <Head title="注文成功" />
      <div
        className={`mb-6 rounded-xl border px-4 py-3 text-sm ${
          order.status === 'canceled'
            ? 'border-rose-200 bg-rose-50 text-rose-700'
            : paid
            ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
            : 'border-amber-200 bg-amber-50 text-amber-800'
        }`}
      >
        <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
          <div>
            <p className="font-semibold">
              {order.status === 'canceled'
                ? 'この注文はキャンセルされました。'
                : paid
                ? '支払いを受け取りました。ご購入ありがとうございます！'
                : '支払いの確認を待っています。'}
            </p>
            <p className="text-xs opacity-80">注文番号 #{order.order_number}</p>
          </div>
          <div className="flex flex-wrap gap-2">
            <a
              className="rounded-md border border-current px-3 py-1 text-xs font-medium hover:bg-white/20"
              href={`/orders/${encodeURIComponent(order.order_number)}/view`}
              target="_blank"
              rel="noreferrer"
            >
              注文を表示
            </a>
            <a
              className="rounded-md border border-current px-3 py-1 text-xs font-medium hover:bg-white/20"
              href={`/orders/${encodeURIComponent(order.order_number)}/view?download=1`}
              target="_blank"
              rel="noreferrer"
            >
              領収書をダウンロード
            </a>
          </div>
        </div>
      </div>
      <div className="mb-2 flex items-center gap-3">
        <h1 className="text-2xl font-semibold">注文詳細</h1>
        {(() => {
          const isCanceled = order.status === 'canceled'
          const isPaid = order.payments?.some((p) => p.processed_at)
          const label = isCanceled ? 'キャンセル' : isPaid ? '支払い済み' : '保留中'
          const cls = isCanceled
            ? 'bg-rose-100 text-rose-800'
            : isPaid
            ? 'bg-emerald-100 text-emerald-800'
            : 'bg-amber-100 text-amber-800'
          return <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${cls}`}>{label}</span>
        })()}
      </div>
      <p className="mb-1 text-sm text-neutral-600">注文番号 #{order.order_number}</p>
      {(order.confirmation_emailed_at || order.cancellation_emailed_at) && order.email && (
        <p className="mb-6 text-xs text-neutral-500">{order.email} にも同じ内容を送信しました。</p>
      )}

      <OrderSummary order={order as unknown as SummaryOrderDTO} />

      <div className="mb-6 rounded-xl border p-4">
        <h2 className="mb-3 text-lg font-semibold">支払い</h2>
        {order.payments?.some((p) => p.processed_at) ? (
          <div className="rounded-md bg-emerald-50 px-3 py-2 text-emerald-700">支払いが処理されました。</div>
        ) : (
          <div className="rounded-md bg-amber-50 px-3 py-2 text-amber-700">確認を待っています…</div>
        )}
      </div>

      {order.timeline && order.timeline.length > 0 && (
        <div className="mb-6 rounded-xl border p-4">
          <h2 className="mb-3 text-lg font-semibold">注文タイムライン</h2>
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
        <a href="/products" className="text-sm text-neutral-700 hover:underline">買い物を続ける</a>
        <a href="/" className="text-sm text-neutral-700 hover:underline">ホーム</a>
        <a href={`/orders/${encodeURIComponent(order.order_number)}/view`} className="text-sm text-neutral-700 hover:underline" target="_blank" rel="noreferrer">注文を表示</a>
      </div>
    </div>
  )
}