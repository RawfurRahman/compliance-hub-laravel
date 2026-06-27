import { reactive, watch } from 'vue'

const filters = reactive({
  businessUnit: null,
  framework: null,
  owner: null,
  dateFrom: null,
  dateTo: null,
  riskStatus: null,
})

let onFilterChange = null

export function useDashboardFilters() {
  function set(key, value) {
    filters[key] = value || null
  }

  function reset() {
    filters.businessUnit = null
    filters.framework = null
    filters.owner = null
    filters.dateFrom = null
    filters.dateTo = null
    filters.riskStatus = null
  }

  function toQueryParams() {
    const params = {}
    for (const [key, value] of Object.entries(filters)) {
      if (value !== null && value !== undefined) {
        const paramKey = key.replace(/([A-Z])/g, '_$1').toLowerCase()
        params[paramKey] = value
      }
    }
    return params
  }

  function fromQueryParams(params) {
    filters.businessUnit = params.business_unit || null
    filters.framework = params.framework || null
    filters.owner = params.owner || null
    filters.dateFrom = params.date_from || null
    filters.dateTo = params.date_to || null
    filters.riskStatus = params.risk_status || null
  }

  function onChange(cb) {
    onFilterChange = cb
  }

  watch(filters, () => {
    if (onFilterChange) onFilterChange(filters)
  }, { deep: true })

  return { filters, set, reset, toQueryParams, fromQueryParams, onChange }
}
