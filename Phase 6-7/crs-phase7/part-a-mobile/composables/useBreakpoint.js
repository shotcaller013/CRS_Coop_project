// src/composables/useBreakpoint.js
// Reactive breakpoint detection — use across all pages and components
// sm = mobile (<640px), md = tablet (640–1024px), lg = desktop (>1024px)

import { ref, computed, onMounted, onUnmounted } from 'vue'

const width = ref(typeof window !== 'undefined' ? window.innerWidth : 1280)

let listeners = 0
function handleResize() { width.value = window.innerWidth }

export function useBreakpoint() {
  onMounted(() => {
    if (++listeners === 1) window.addEventListener('resize', handleResize, { passive: true })
    width.value = window.innerWidth
  })
  onUnmounted(() => {
    if (--listeners === 0) window.removeEventListener('resize', handleResize)
  })

  const isMobile  = computed(() => width.value < 640)
  const isTablet  = computed(() => width.value >= 640 && width.value < 1024)
  const isDesktop = computed(() => width.value >= 1024)

  // Convenience: "is this at least X?"
  const atLeastMd = computed(() => width.value >= 640)
  const atLeastLg = computed(() => width.value >= 1024)

  return { width, isMobile, isTablet, isDesktop, atLeastMd, atLeastLg }
}
