import { Head } from '@inertiajs/react'
import { useState } from 'react'
import { HomeNavigation } from '@/components/homeNavigation';

function getCookie(name: string) {
  const parts = document.cookie.split('; ').map((c) => c.split('='))
  const found = parts.find(([k]) => k === name)
  return found ? decodeURIComponent(found[1] ?? '') : null
}
function xsrfHeaders(): HeadersInit {
  const xsrf = getCookie('XSRF-TOKEN')
  return {
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
  }
}

type PageProps = { previousCancelledReason?: 'timeout' | 'changed' | 'expired' | 'psp_canceled' | 'failed' | 'customer_canceled' | 'canceled' | string | null; pendingOrderNumber?: string | null }

export default function CheckoutStart({ previousCancelledReason, pendingOrderNumber }: PageProps) {
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  async function proceed() {
    setError(null)
    setLoading(true)
    try {
      const res = await fetch('/checkout', {
        method: 'POST',
        headers: xsrfHeaders(),
        body: JSON.stringify({}),
      })
      if (!res.ok) {
        const err = await res.json().catch(() => null)
        throw new Error(err?.message || 'チェックアウトセッションの作成に失敗しました')
      }
      const data = await res.json()
      // For Embedded Checkout, use redirect to our pay page
      if (data?.redirect) {
        window.location.href = data.redirect
        return
      }
      // Fallback to hosted URL if present (legacy)
      if (data?.url) {
        window.location.href = data.url
        return
      }
      throw new Error('チェックアウトのリダイレクトが返されませんでした')
    } catch (e: any) {
      setError(e?.message || '何か問題が発生しました')
    } finally {
      setLoading(false)
    }
  }

  // We do not expose a manual "cancel attempt" in UI anymore.

  return (
    <div className="min-h-screen bg-white">
      <HomeNavigation />
      <div className="mx-auto max-w-2xl px-4 py-10">
        <Head title="チェックアウト" />
        <h1 className="mb-2 text-2xl font-semibold text-[#363842]">チェックアウト</h1>
      <p className="mb-6 text-sm text-neutral-600">支払いを完了するためにStripeにリダイレクトされます。</p>
      {previousCancelledReason && (
        (() => {
          const cls =
            previousCancelledReason === 'timeout'
              ? 'bg-amber-50 text-amber-700'
              : previousCancelledReason === 'changed'
              ? 'bg-sky-50 text-sky-700'
              : 'bg-rose-50 text-rose-700'
          let msg: string
          switch (previousCancelledReason) {
            case 'timeout':
              msg = '前の試行はタイムアウトのためキャンセルされました。'
              break
            case 'changed':
              msg = 'カートが変更されたため、前の試行はキャンセルされました。'
              break
            case 'expired':
              msg = '最後の支払い試行は期限切れになりました。請求は行われていません。'
              break
            case 'psp_canceled':
            case 'customer_canceled':
            case 'canceled':
              msg = '最後の支払い試行はキャンセルされました。請求は行われていません。'
              break
            case 'failed':
              msg = '最後の支払い試行は失敗しました。請求は行われていません。'
              break
            default:
              msg = '最後の支払い試行は完了しませんでした。請求は行われていません。'
          }
          return (
            <div className={`mb-4 px-3 py-2 text-sm ${cls}`}>
              {msg}
            </div>
          )
        })()
      )}
      {error && <div className="mb-4 bg-rose-50 px-3 py-2 text-sm text-rose-700">{error}</div>}
      {pendingOrderNumber && (
        <div className="mb-4 border border-neutral-200 bg-neutral-50 px-3 py-2 text-sm text-neutral-700">
          注文番号 #{pendingOrderNumber} の支払い試行が保留中です。
          <div className="mt-2 flex gap-3">
            <button
              onClick={proceed}
              disabled={loading}
              className="bg-rose-600 px-3 py-2 text-white disabled:cursor-not-allowed disabled:opacity-50"
            >
              {loading ? '準備中…' : '支払いを再開'}
            </button>
          </div>
        </div>
      )}
      <button
        onClick={proceed}
        disabled={loading}
        className="bg-rose-600 px-5 py-3 font-medium text-white disabled:cursor-not-allowed disabled:opacity-50"
      >
        {loading ? '準備中…' : '支払いに進む'}
      </button>
      <div className="mt-4">
        <a href="/cart" className="text-sm text-neutral-600">カートに戻る</a>
      </div>
    </div>
    </div>
  )
}
