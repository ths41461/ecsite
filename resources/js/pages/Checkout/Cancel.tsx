import { Head } from '@inertiajs/react'

type PageProps = {
  order_number: string
  status?: string | null
  email?: string | null
  cancellation_emailed_at?: string | null
  cancel_reason?: 'expired' | 'psp_canceled' | 'failed' | string | null
}

export default function CheckoutCancel({ order_number, status, email, cancellation_emailed_at, cancel_reason }: PageProps) {
  return (
    <div className="mx-auto max-w-2xl px-4 py-10">
      <Head title="支払いキャンセル" />
      <div className="mb-2 flex items-center gap-3">
        <h1 className="text-2xl font-semibold">支払いキャンセル</h1>
        <span className="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-800">
          {cancel_reason === 'expired' ? '期限切れ' : cancel_reason === 'psp_canceled' ? 'キャンセル' : cancel_reason === 'failed' ? '支払い失敗' : 'キャンセル'}
        </span>
      </div>
      <p className="text-sm text-neutral-600">注文番号 #{order_number}</p>
      {cancellation_emailed_at && email && (
        <p className="mb-4 text-xs text-neutral-500">{email} にもコピーを送信しました。</p>
      )}
      <p className="mb-6 text-sm text-neutral-700">商品をカートに戻しました。</p>
      <div className="flex gap-3">
        <a
          href="/cart"
          className="inline-block rounded-lg bg-neutral-800 px-5 py-3 text-white hover:bg-neutral-900"
        >
          カートに戻る
        </a>
        <a
          href="/checkout"
          className="inline-block rounded-lg border border-neutral-300 px-5 py-3 text-neutral-700 hover:bg-white"
        >
          チェックアウトを再度開始
        </a>
      </div>
    </div>
  )
}
