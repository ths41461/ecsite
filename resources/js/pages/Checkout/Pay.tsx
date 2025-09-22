import { Head } from '@inertiajs/react'
import { EmbeddedCheckout, EmbeddedCheckoutProvider } from '@stripe/react-stripe-js'
import { loadStripe, Stripe } from '@stripe/stripe-js'
import { useEffect, useMemo, useState } from 'react'
import CheckoutTimeline, { TimelineStep } from '@/components/CheckoutTimeline'

type PageProps = {
  order_number: string
  client_secret: string
  stripe_pk: string
  fallback_url?: string
  start_url?: string
  cancel_url?: string | null
  allow_cancel?: boolean
  session_status?: 'open' | 'expired' | 'unknown'
  restart_url?: string
  timeline: TimelineStep[]
}

export default function CheckoutPay({ order_number, client_secret, stripe_pk, fallback_url, start_url, cancel_url, allow_cancel, session_status, restart_url, timeline }: PageProps) {
  const stripePromise = useMemo(() => loadStripe(stripe_pk) as Promise<Stripe | null>, [stripe_pk])
  const [stripeReady, setStripeReady] = useState(false)
  useEffect(() => {
    let mounted = true
    ;(async () => {
      try {
        await stripePromise
      } finally {
        if (mounted) setStripeReady(true)
      }
    })()
    return () => {
      mounted = false
    }
  }, [stripePromise])

  return (
    <div className="mx-auto max-w-3xl px-4 py-8">
      <Head title="安全な支払い" />
      <CheckoutTimeline steps={timeline} />

      <div className="mb-3 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div>
            <h1 className="text-2xl font-semibold">安全な支払い</h1>
            <p className="text-sm text-neutral-600">注文番号 #{order_number}</p>
          </div>
          {session_status === 'open' && (
            <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">確認待ち…</span>
          )}
          {session_status === 'expired' && (
            <span className="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-800">セッション期限切れ — 再度開始</span>
          )}
        </div>
        <div className="flex items-center gap-4">
          <a href={start_url || '/checkout'} className="text-sm text-neutral-600 hover:underline">
            チェックアウトに戻る
          </a>
          {cancel_url && allow_cancel && (
            <a href={cancel_url} className="text-sm text-rose-700 hover:underline">
              キャンセルして戻る
            </a>
          )}
        </div>
      </div>

      {session_status === 'expired' ? (
        <div className="rounded-xl border p-6 text-center">
          <p className="mb-3 text-sm text-neutral-700">この支払いセッションはもう有効ではありません。</p>
          <a href={restart_url || start_url || '/checkout'} className="inline-block rounded-md bg-neutral-800 px-4 py-2 text-sm text-white hover:bg-neutral-900">
            チェックアウトを再度開始
          </a>
        </div>
      ) : (
        <div id="checkout" className="relative min-h-[500px]">
          {!stripeReady && (
            <div className="absolute inset-0 flex items-center justify-center">
              <div className="flex items-center gap-3 rounded-lg border border-neutral-200 bg-white px-4 py-2 text-sm text-neutral-600 shadow-sm">
                <svg className="h-4 w-4 animate-spin text-neutral-500" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                </svg>
                安全なチェックアウトを準備中…
              </div>
            </div>
          )}
          <EmbeddedCheckoutProvider stripe={stripePromise} options={{ clientSecret: client_secret }}>
            <EmbeddedCheckout />
          </EmbeddedCheckoutProvider>
        </div>
      )}

      <div className="mt-4 flex flex-col gap-1 text-xs text-neutral-600">
        <div>支払いはStripeによって処理されます。確認前にいつでもキャンセルできます。</div>
        <div>
          問題がありますか？{' '}
          {fallback_url ? (
            <a href={fallback_url} className="underline hover:no-underline">安全な支払いページを開いてみてください</a>
          ) : (
            <a href={start_url || '/checkout'} className="underline hover:no-underline">チェックアウトに戻る</a>
          )}
          。
        </div>
      </div>
    </div>
  )
}
