type Item = { name: string; sku: string; qty: number; unit_price_yen: number; line_total_yen: number }

export type OrderDTO = {
  order_number: string
  status_id: number | null
  subtotal_yen: number
  discount_yen: number
  coupon_code?: string | null
  coupon_discount_yen?: number | null
  shipping_yen: number
  tax_yen: number
  total_yen: number
  items: Item[]
}

function yen(y: number) {
  return y.toLocaleString(undefined, { style: 'currency', currency: 'JPY' })
}

export default function OrderSummary({ order }: { order: OrderDTO }) {
  return (
    <div>
      <div className="mb-6 border p-4">
        <div className="mb-2 flex items-center justify-between">
          <span className="text-sm font-hiragino-mincho text-[#363842]">小計</span>
          <span className="font-medium font-hiragino-mincho text-[#363842]">{yen(order.subtotal_yen)}</span>
        </div>
        {order.discount_yen > 0 && (
          <div className="mb-2 flex items-center justify-between text-emerald-700">
            <span className="text-sm font-hiragino-mincho">割引</span>
            <span className="font-hiragino-mincho">-{yen(order.discount_yen)}</span>
          </div>
        )}
        {order.coupon_code && (order.coupon_discount_yen ?? 0) > 0 && (
          <div className="mb-2 flex items-center justify-between text-sm text-rose-600">
            <span className="font-hiragino-mincho">クーポン ({order.coupon_code})</span>
            <span className="font-hiragino-mincho">-{yen(order.coupon_discount_yen ?? 0)}</span>
          </div>
        )}
        {order.shipping_yen > 0 && (
          <div className="mb-2 flex items-center justify-between">
            <span className="text-sm font-hiragino-mincho text-[#363842]">送料</span>
            <span className="font-medium font-hiragino-mincho text-[#363842]">{yen(order.shipping_yen)}</span>
          </div>
        )}
        {order.tax_yen > 0 && (
          <div className="mb-2 flex items-center justify-between">
            <span className="text-sm font-hiragino-mincho text-[#363842]">税金</span>
            <span className="font-medium font-hiragino-mincho text-[#363842]">{yen(order.tax_yen)}</span>
          </div>
        )}
        <div className="mt-2 border-t pt-2">
          <div className="flex items-center justify-between text-lg font-semibold font-hiragino-mincho text-[#363842]">
            <span>合計</span>
            <span>{yen(order.total_yen)}</span>
          </div>
        </div>
      </div>

      <div className="mb-6 border p-4">
        <h2 className="mb-3 text-lg font-semibold font-hiragino-mincho text-[#363842]">商品</h2>
        <div className="space-y-3">
          {order.items.map((it, idx) => (
            <div key={idx} className="flex items-center justify-between">
              <div>
                <div className="font-medium font-hiragino-mincho text-[#363842]">{it.name}</div>
                <div className="text-xs font-hiragino-mincho text-[#363842]">SKU: {it.sku}</div>
              </div>
              <div className="text-right text-sm font-hiragino-mincho text-[#363842]">
                <div>数量: {it.qty}</div>
                <div>{yen(it.line_total_yen)}</div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}