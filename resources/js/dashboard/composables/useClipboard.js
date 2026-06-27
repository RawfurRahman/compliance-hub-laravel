import { ref, computed } from 'vue'

export function useClipboard() {
  const clipboardImage = ref(null)
  const isProcessing = ref(false)
  const error = ref(null)
  const isDragOver = ref(false)

  const hasImage = computed(() => !!clipboardImage.value)
  
  const previewUrl = computed(() => {
    if (!clipboardImage.value) return null
    if (typeof clipboardImage.value === 'string') {
      return clipboardImage.value
    }
    return URL.createObjectURL(clipboardImage.value)
  })

  const validateImage = (file) => {
    const maxSize = 20 * 1024 * 1024
    const allowedTypes = ['image/png', 'image/jpeg', 'image/webp', 'image/heic', 'image/heif']
    
    if (file.size > maxSize) {
      error.value = 'Image size must be less than 20MB'
      return false
    }
    
    if (!allowedTypes.includes(file.type.toLowerCase())) {
      error.value = 'Only PNG, JPEG, WEBP, HEIC, and HEIF images are supported'
      return false
    }
    
    error.value = null
    return true
  }

  const handlePaste = (event) => {
    const items = event.clipboardData?.items
    if (!items) return
    
    for (const item of items) {
      if (item.type.startsWith('image/')) {
        event.preventDefault()
        const file = item.getAsFile()
        if (validateImage(file)) {
          processImageFile(file)
        }
        break
      }
    }
  }

  const processImageFile = async (file) => {
    isProcessing.value = true
    error.value = null
    
    try {
      const base64 = await convertToBase64(file)
      clipboardImage.value = file
      error.value = null
      return { file, base64 }
    } catch (err) {
      error.value = 'Failed to process image. Please try again.'
      console.error('Image processing error:', err)
    } finally {
      isProcessing.value = false
    }
  }

  const convertToBase64 = (file) => {
    return new Promise((resolve, reject) => {
      const reader = new FileReader()
      reader.onload = () => resolve(reader.result)
      reader.onerror = (err) => reject(err)
      reader.readAsDataURL(file)
    })
  }

  const clearImage = () => {
    clipboardImage.value = null
    error.value = null
  }

  const handleDrop = (event) => {
    event.preventDefault()
    isDragOver.value = false
    
    const files = event.dataTransfer?.files
    if (!files || files.length === 0) return
    
    const file = files[0]
    if (validateImage(file)) {
      processImageFile(file)
    }
  }

  const startDragOver = () => {
    isDragOver.value = true
  }

  const endDragOver = () => {
    isDragOver.value = false
  }

  return {
    clipboardImage,
    isProcessing,
    error,
    isDragOver,
    hasImage,
    previewUrl,
    handlePaste,
    processImageFile,
    clearImage,
    handleDrop,
    startDragOver,
    endDragOver,
  }
}