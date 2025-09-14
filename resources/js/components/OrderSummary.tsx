type Item = { name: string; sku: string; qty: number; unit_price_yen: number; line_total_yen: number }

export type OrderDTO = {
  order_number: string
  status_id: number | null
  subtotal_yen: number
  discount_yen: number
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
      <div className="mb-6 rounded-xl border p-4">
        <div className="mb-2 flex items-center justify-between">
          <span className="text-sm text-neutral-600">Subtotal</span>
          <span className="font-medium">{yen(order.subtotal_yen)}</span>
        </div>
        {order.discount_yen > 0 && (
          <div className="mb-2 flex items-center justify-between text-emerald-700">
            <span className="text-sm">Discount</span>
            <span>-{yen(order.discount_yen)}</span>
          </div>
        )}
        {order.shipping_yen > 0 && (
          <div className="mb-2 flex items-center justify-between">
            <span className="text-sm text-neutral-600">Shipping</span>
            <span className="font-medium">{yen(order.shipping_yen)}</span>
          </div>
        )}
        {order.tax_yen > 0 && (
          <div className="mb-2 flex items-center justify-between">
            <span className="text-sm text-neutral-600">Tax</span>
            <span className="font-medium">{yen(order.tax_yen)}</span>
          </div>
        )}
        <div className="mt-2 border-t pt-2">
          <div className="flex items-center justify-between text-lg font-semibold">
            <span>Total</span>
            <span>{yen(order.total_yen)}</span>
          </div>
        </div>
      </div>

      <div className="mb-6 rounded-xl border p-4">
        <h2 className="mb-3 text-lg font-semibold">Items</h2>
        <div className="space-y-3">
          {order.items.map((it, idx) => (
            <div key={idx} className="flex items-center justify-between">
              <div>
                <div className="font-medium">{it.name}</div>
                <div className="text-xs text-neutral-500">SKU: {it.sku}</div>
              </div>
              <div className="text-right text-sm">
                <div>Qty: {it.qty}</div>
                <div>{yen(it.line_total_yen)}</div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}

