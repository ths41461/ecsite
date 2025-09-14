import { Head } from '@inertiajs/react'

type PageProps = {
  order_number: string
  status?: string | null
  email?: string | null
  cancellation_emailed_at?: string | null
}

export default function CheckoutCancel({ order_number, status, email, cancellation_emailed_at }: PageProps) {
  const isCanceled = status === 'canceled'
  return (
    <div className="mx-auto max-w-2xl px-4 py-10">
      <Head title="Payment Cancelled" />
      <div className="mb-2 flex items-center gap-3">
        <h1 className="text-2xl font-semibold">Payment cancelled</h1>
        <span className="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-800">Canceled</span>
      </div>
      <p className="text-sm text-neutral-600">Order #{order_number}</p>
      {cancellation_emailed_at && email && (
        <p className="mb-4 text-xs text-neutral-500">We sent a copy to {email}.</p>
      )}
      <p className="mb-6 text-sm text-neutral-700">We restored your items in the cart.</p>
      <div className="flex gap-3">
        <a
          href="/cart"
          className="inline-block rounded-lg bg-neutral-800 px-5 py-3 text-white hover:bg-neutral-900"
        >
          Return to Cart
        </a>
        <a
          href="/checkout"
          className="inline-block rounded-lg border border-neutral-300 px-5 py-3 text-neutral-700 hover:bg-white"
        >
          Start checkout again
        </a>
      </div>
    </div>
  )
}
