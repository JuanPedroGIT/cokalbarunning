let overlayEl: HTMLDivElement | null = null

function closeOverlay() {
  if (!overlayEl) return
  overlayEl.classList.remove('active')
  document.body.style.overflow = ''
  setTimeout(() => {
    overlayEl?.remove()
    overlayEl = null
  }, 350)
}

export function useImageZoom() {
  function zoomImage(el: HTMLImageElement) {
    if (overlayEl) {
      closeOverlay()
      return
    }

    const overlay = document.createElement('div')
    overlay.className = 'poster-overlay'
    const img = document.createElement('img')
    img.src = el.src
    img.alt = el.alt || ''
    overlay.appendChild(img)
    document.body.appendChild(overlay)
    document.body.style.overflow = 'hidden'
    overlayEl = overlay

    requestAnimationFrame(() => {
      requestAnimationFrame(() => overlay.classList.add('active'))
    })

    overlay.addEventListener('click', closeOverlay)
  }

  return { zoomImage }
}
