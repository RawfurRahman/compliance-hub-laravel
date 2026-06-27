import { ref, computed } from 'vue'

export function useAuth() {
  const isSuperAdmin = ref(false)
  const loading = ref(true)
  const error = ref(null)

  const checkSuperAdmin = async () => {
    loading.value = true
    error.value = null

    try {
      const response = await fetch('/api/v1/dashboard/user', { credentials: 'same-origin' })
      const data = await response.json()
      isSuperAdmin.value = data.user?.roles?.some(role => role.name === 'Super Admin') || false
    } catch (err) {
      error.value = 'Failed to check user permissions'
      console.error('Error checking Super Admin status:', err)
    } finally {
      loading.value = false
    }
  }

  return {
    isSuperAdmin,
    loading,
    error,
    checkSuperAdmin,
  }
}