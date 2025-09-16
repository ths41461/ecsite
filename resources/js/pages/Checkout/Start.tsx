import { Head } from '@inertiajs/react'
import { useState } from 'react'

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
        throw new Error(err?.message || 'Failed to create checkout session')
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
      throw new Error('No checkout redirection returned')
    } catch (e: any) {
      setError(e?.message || 'Something went wrong')
    } finally {
      setLoading(false)
    }
  }

  // We do not expose a manual "cancel attempt" in UI anymore.

  return (
    <div className="mx-auto max-w-2xl px-4 py-10">
      <Head title="Checkout" />
      <h1 className="mb-2 text-2xl font-semibold">Checkout</h1>
      <p className="mb-6 text-sm text-neutral-600">You will be redirected to Stripe to complete payment.</p>
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
              msg = 'Your previous attempt was cancelled due to timeout.'
              break
            case 'changed':
              msg = 'Your previous attempt was cancelled because your cart changed.'
              break
            case 'expired':
              msg = 'Your last payment attempt expired. No charge was made.'
              break
            case 'psp_canceled':
            case 'customer_canceled':
            case 'canceled':
              msg = 'Your last payment attempt was canceled. No charge was made.'
              break
            case 'failed':
              msg = 'Your last payment attempt failed. No charge was made.'
              break
            default:
              msg = 'Your last payment attempt did not complete. No charge was made.'
          }
          return (
            <div className={`mb-4 rounded-lg px-3 py-2 text-sm ${cls}`}>
              {msg}
            </div>
          )
        })()
      )}
      {error && <div className="mb-4 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{error}</div>}
      {pendingOrderNumber && (
        <div className="mb-4 rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2 text-sm text-neutral-700">
          You have a pending payment attempt for Order #{pendingOrderNumber}.
          <div className="mt-2 flex gap-3">
            <button
              onClick={proceed}
              disabled={loading}
              className="rounded-md bg-rose-600 px-3 py-2 text-white hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {loading ? 'Preparing…' : 'Resume Payment'}
            </button>
          </div>
        </div>
      )}
      <button
        onClick={proceed}
        disabled={loading}
        className="rounded-lg bg-rose-600 px-5 py-3 font-medium text-white hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
      >
        {loading ? 'Preparing…' : 'Proceed to Payment'}
      </button>
      <div className="mt-4">
        <a href="/cart" className="text-sm text-neutral-600 hover:underline">Back to cart</a>
      </div>
    </div>
  )
}
