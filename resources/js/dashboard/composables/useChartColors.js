export const CHART_COLORS = {
  good: '#059669',
  warning: '#d97706',
  bad: '#dc2626',
  info: '#2563eb',
  neutral: '#6b7280',
  muted: '#9ca3af',
}

export const PALETTE = [
  '#2563eb', '#059669', '#d97706', '#dc2626',
  '#7c3aed', '#0891b2', '#db2777', '#65a30d',
]

export function heatmapColor(count, max) {
  const intensity = max > 0 ? count / max : 0
  if (intensity === 0) return 'bg-gray-50'
  if (intensity <= 0.2) return 'bg-blue-100'
  if (intensity <= 0.4) return 'bg-blue-200'
  if (intensity <= 0.6) return 'bg-amber-200'
  if (intensity <= 0.8) return 'bg-orange-300'
  return 'bg-red-400'
}

export function heatmapTextColor(count, max) {
  const intensity = max > 0 ? count / max : 0
  if (intensity >= 0.6) return 'text-white'
  return 'text-gray-800'
}
