import { ref, watch, toValue } from 'vue'
import axios from 'axios'

const api = axios.create({
  withCredentials: true,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
  },
})

export function useDashboardApi(endpoint, filterSource, options = {}) {
  const { immediate = true, transform = null } = options

  const data = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const isRetrying = ref(false)

  async function fetch(params = {}) {
    loading.value = true
    error.value = null
    isRetrying.value = true

    try {
      const url = `/api/v1/dashboard/${endpoint}`
      const response = await api.get(url, { params })

      let result = response.data
      if (transform) {
        result = transform(result)
      }

      data.value = result
      isRetrying.value = false
    } catch (e) {
      const status = e.response?.status
      
      if (status === 401) {
        error.value = 'Authentication failed. Please log in to access this dashboard.'
        data.value = null
        isRetrying.value = false
      } else if (status === 403) {
        error.value = 'Access denied. You do not have permission to view this data.'
        data.value = null
        isRetrying.value = false
      } else if (status === 429) {
        error.value = 'Too many requests. Please wait a moment and try again.'
        setTimeout(() => fetch(params), 2000)
      } else if (status >= 500) {
        error.value = 'Server error. Please try again later.'
        data.value = null
        isRetrying.value = false
      } else {
        error.value = e.response?.data?.message || e.message || 'Failed to fetch data'
        data.value = null
        isRetrying.value = false
      }
    } finally {
      loading.value = false
    }
  }

  function refresh() {
    const params = toValue(filterSource) || {}
    fetch(params)
  }

  if (immediate) {
    fetch(toValue(filterSource) || {})
  }

  if (filterSource) {
    watch(
      () => toValue(filterSource),
      (params) => fetch(params || {}),
      { deep: true }
    )
  }

  return { data, loading, error, fetch, refresh }
}
