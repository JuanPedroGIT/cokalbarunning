import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'
import NavBar from '../layout/NavBar.vue'

describe('NavBar', () => {
  const router = createRouter({
    history: createWebHistory(),
    routes: [
      { path: '/', name: 'home', component: { template: '<div>Home</div>' } },
      { path: '/carrera', name: 'race', component: { template: '<div>Race</div>' } },
    ],
  })

  it('renders navigation links', async () => {
    const wrapper = mount(NavBar, {
      global: { plugins: [router] },
    })

    expect(wrapper.text()).toContain('Inicio')
    expect(wrapper.text()).toContain('Carrera')
  })
})
