export function formatNumber(n) {
  if (n === null || n === undefined) return '—'
  n = Number(n)
  if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M'
  if (n >= 1000) return (n / 1000).toFixed(1) + 'K'
  return n.toLocaleString()
}

export function formatPct(n) {
  if (n === null || n === undefined) return '—'
  return Number(n).toFixed(1) + '%'
}

export function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('en-US', {
    year: 'numeric', month: 'short', day: 'numeric',
  })
}

export function riskBadgeClass(rating) {
  const map = {
    Critical: 'bg-red-100 text-red-700 ring-red-300',
    High: 'bg-orange-100 text-orange-700 ring-orange-300',
    Medium: 'bg-amber-100 text-amber-700 ring-amber-300',
    Low: 'bg-emerald-100 text-emerald-700 ring-emerald-300',
    None: 'bg-gray-100 text-gray-500 ring-gray-200',
  }
  return map[rating] || 'bg-gray-100 text-gray-600 ring-gray-200'
}

export function phaseBadgeClass(phase) {
  const map = {
    gap_in_progress: 'bg-blue-100 text-blue-700 ring-blue-300',
    gap_done: 'bg-indigo-100 text-indigo-700 ring-indigo-300',
    final_pending: 'bg-amber-100 text-amber-700 ring-amber-300',
    final_in_progress: 'bg-purple-100 text-purple-700 ring-purple-300',
    final_done: 'bg-emerald-100 text-emerald-700 ring-emerald-300',
  }
  return map[phase] || 'bg-gray-100 text-gray-600 ring-gray-200'
}

export function phaseLabel(phase) {
  const map = {
    gap_in_progress: 'Gap In Progress',
    gap_done: 'Gap Complete',
    final_pending: 'Final Pending',
    final_in_progress: 'Final In Progress',
    final_done: 'Fully Compliant',
  }
  return map[phase] || phase
}
