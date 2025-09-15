import { Head } from '@inertiajs/react'
import { EmbeddedCheckout, EmbeddedCheckoutProvider } from '@stripe/react-stripe-js'
import { loadStripe, Stripe } from '@stripe/stripe-js'
import { useEffect, useMemo, useState } from 'react'

type PageProps = {
  order_number: string
  client_secret: string
  stripe_pk: string
  fallback_url?: string
  start_url?: string
  cancel_url?: string | null
}

export default function CheckoutPay({ order_number, client_secret, stripe_pk, fallback_url, start_url, cancel_url }: PageProps) {
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
      <Head title="Secure Payment" />
      <div className="mb-3 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Secure Payment</h1>
          <p className="text-sm text-neutral-600">Order #{order_number}</p>
        </div>
        <div className="flex items-center gap-4">
          <a href={start_url || '/checkout'} className="text-sm text-neutral-600 hover:underline">
            Return to checkout
          </a>
          {cancel_url && (
            <a href={cancel_url} className="text-sm text-rose-700 hover:underline">
              Cancel and return
            </a>
          )}
        </div>
      </div>

      <div id="checkout" className="relative min-h-[500px]">
        {!stripeReady && (
          <div className="absolute inset-0 flex items-center justify-center">
            <div className="flex items-center gap-3 rounded-lg border border-neutral-200 bg-white px-4 py-2 text-sm text-neutral-600 shadow-sm">
              <svg className="h-4 w-4 animate-spin text-neutral-500" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
              </svg>
              Preparing secure checkout…
            </div>
          </div>
        )}
        <EmbeddedCheckoutProvider
          stripe={stripePromise}
          options={{ clientSecret: client_secret }}
        >
          <EmbeddedCheckout />
        </EmbeddedCheckoutProvider>
      </div>

      <div className="mt-4 flex flex-col gap-1 text-xs text-neutral-600">
        <div>Payments are processed by Stripe. You can cancel at any time before confirming.</div>
        <div>
          Having trouble?{' '}
          {fallback_url ? (
            <a href={fallback_url} className="underline hover:no-underline">Try opening the secure payment page</a>
          ) : (
            <a href={start_url || '/checkout'} className="underline hover:no-underline">Return to checkout</a>
          )}
          .
        </div>
      </div>
    </div>
  )
}
