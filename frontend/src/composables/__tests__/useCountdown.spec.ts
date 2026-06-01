import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { useCountdown } from '../useCountdown'
import { defineComponent, nextTick } from 'vue'
import { mount } from '@vue/test-utils'

describe('useCountdown', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  function mountWithCountdown(targetDate: Date) {
    const TestComponent = defineComponent({
      setup() {
        return useCountdown(targetDate)
      },
      template: '<div>{{ days }}:{{ hours }}:{{ minutes }}:{{ seconds }}</div>',
    })
    return mount(TestComponent)
  }

  it('calculates remaining time correctly', async () => {
    const target = new Date(Date.now() + 86400000 + 3600000 + 60000 + 1000)
    const wrapper = mountWithCountdown(target)
    await nextTick()

    expect(wrapper.vm.days).toBe('01')
    expect(wrapper.vm.hours).toBe('01')
    expect(wrapper.vm.minutes).toBe('01')
    expect(wrapper.vm.seconds).toBe('01')
  })

  it('decrements seconds every tick', async () => {
    const target = new Date(Date.now() + 5000)
    const wrapper = mountWithCountdown(target)
    await nextTick()

    expect(wrapper.vm.seconds).toBe('05')

    vi.advanceTimersByTime(1000)
    await nextTick()

    expect(wrapper.vm.seconds).toBe('04')
  })

  it('stops at zero when target is reached', async () => {
    const target = new Date(Date.now() + 500)
    const wrapper = mountWithCountdown(target)
    await nextTick()

    vi.advanceTimersByTime(2000)
    await nextTick()

    expect(wrapper.vm.days).toBe('00')
    expect(wrapper.vm.hours).toBe('00')
    expect(wrapper.vm.minutes).toBe('00')
    expect(wrapper.vm.seconds).toBe('00')
  })
})
