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

// Function to translate order status to Japanese
const translateOrderStatus = (status: string): string => {
  const translations: Record<string, string> = {
    'ordered': '注文済み',
    'processing': '処理中',
    'paid': '支払い済み',
    'shipped': '発送済み',
    'delivered': '配送済み',
    'canceled': 'キャンセル済み',
    'refunded': '返金済み'
  }
  
  return translations[status] || status.replace('_', ' ')
}

// Function to translate order status badge to Japanese
const translateOrderStatusBadge = (status: string | null | undefined): string => {
  if (!status) return '保留中'
  
  const translations: Record<string, string> = {
    'canceled': 'キャンセル済み',
    'paid': '支払い済み',
    'pending': '保留中'
  }
  
  return translations[status] || status
}

export default function OrdersShow({ order }: PageProps) {
  return (
    <div className="mx-auto max-w-3xl px-4 py-10">
      <Head title={`注文 ${order.order_number}`} />
      <div className="mb-1 flex items-center gap-3">
        <h1 className="text-2xl font-semibold">注文 #{order.order_number}</h1>
        {(() => {
          const isCanceled = order.status === 'canceled'
          const isPaid = order.payments?.some((p) => p.processed_at)
          const label = isCanceled ? translateOrderStatusBadge('canceled') : isPaid ? translateOrderStatusBadge('paid') : translateOrderStatusBadge('pending')
          const cls = isCanceled
            ? 'bg-rose-100 text-rose-800'
            : isPaid
            ? 'bg-emerald-100 text-emerald-800'
            : 'bg-amber-100 text-amber-800'
          return <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${cls}`}>{label}</span>
        })()}
      </div>
      {(order.confirmation_emailed_at || order.cancellation_emailed_at) && order.email && (
        <p className="mb-4 text-xs text-neutral-500">{order.email} にコピーを送信しました。</p>
      )}
      <OrderSummary order={order} />
      {order.timeline && order.timeline.length > 0 && (
        <div className="mb-6 rounded-xl border p-4">
          <h2 className="mb-3 text-lg font-semibold">タイムライン</h2>
          <ul className="space-y-2">
            {order.timeline.map((t, i) => (
              <li key={i} className="flex items-center justify-between text-sm">
                <span>{translateOrderStatus(t.status)}</span>
                <span className="text-neutral-500">{new Date(t.changed_at).toLocaleString('ja-JP')}</span>
              </li>
            ))}
          </ul>
        </div>
      )}
      <div className="flex items-center gap-4">
        <a href="/products" className="text-sm text-neutral-700 hover:underline">買い物を続ける</a>
        <a href="/" className="text-sm text-neutral-700 hover:underline">ホーム</a>
      </div>
    </div>
  )}
