import React from 'react'

type StepStatus = 'complete' | 'current' | 'pending' | 'canceled'

export type TimelineStep = {
  key: string
  label: string
  status: StepStatus
  completed_at?: string | null
  started_at?: string | null
}

type Props = {
  steps: TimelineStep[]
}

function statusClasses(status: StepStatus) {
  switch (status) {
    case 'complete':
      return 'bg-emerald-500 text-white'
    case 'current':
      return 'bg-rose-600 text-white'
    case 'canceled':
      return 'bg-rose-200 text-rose-800'
    default:
      return 'bg-neutral-200 text-neutral-600'
  }
}

export default function CheckoutTimeline({ steps }: Props) {
  if (!steps?.length) return null

  return (
    <div className="mb-6">
      <div className="flex items-center justify-between">
        {steps.map((step, idx) => {
          const cls = statusClasses(step.status)
          return (
            <React.Fragment key={step.key}>
              <div className="flex flex-col items-center text-center">
                <div className={`flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold ${cls}`}>{idx + 1}</div>
                <div className="mt-2 text-xs font-medium text-neutral-700">{step.label}</div>
              </div>
              {idx < steps.length - 1 && <div className="h-px flex-1 bg-neutral-200" />}
            </React.Fragment>
          )
        })}
      </div>
    </div>
  )
}