import { ref, onMounted, onUnmounted, watch, type Ref } from 'vue'

export function useCountdown(targetDate: Ref<Date> | Date) {
  const days = ref('00')
  const hours = ref('00')
  const minutes = ref('00')
  const seconds = ref('00')
  const isExpired = ref(false)
  let interval: ReturnType<typeof setInterval> | null = null

  function update(target: Date) {
    const diff = target.getTime() - Date.now()
    if (diff <= 0) {
      isExpired.value = true
      days.value = hours.value = minutes.value = seconds.value = '00'
      if (interval) { clearInterval(interval); interval = null }
      return
    }
    isExpired.value = false
    days.value = String(Math.floor(diff / 86400000)).padStart(2, '0')
    hours.value = String(Math.floor((diff % 86400000) / 3600000)).padStart(2, '0')
    minutes.value = String(Math.floor((diff % 3600000) / 60000)).padStart(2, '0')
    seconds.value = String(Math.floor((diff % 60000) / 1000)).padStart(2, '0')
  }

  function start(target: Date) {
    if (interval) clearInterval(interval)
    update(target)
    interval = setInterval(() => update(target), 1000)
  }

  if (targetDate instanceof Date) {
    onMounted(() => start(targetDate))
  } else {
    watch(targetDate, (d) => { if (d) start(d) }, { immediate: true })
  }

  onUnmounted(() => { if (interval) clearInterval(interval) })

  return { days, hours, minutes, seconds, isExpired }
}
