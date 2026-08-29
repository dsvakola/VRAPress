document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.createElement('div');
  overlay.className = 'vrp-zoom-overlay';
  overlay.innerHTML = `
    <div class="vrp-zoom-backdrop"></div>
    <div class="vrp-zoom-card" role="dialog" aria-modal="true">
      <button class="vrp-zoom-close" type="button" aria-label="Close">×</button>
      <img class="vrp-zoom-img" alt="">
      <div class="vrp-zoom-caption"></div>
    </div>
  `;
  document.body.appendChild(overlay);

  const imgEl = overlay.querySelector('.vrp-zoom-img');
  const capEl = overlay.querySelector('.vrp-zoom-caption');

  function openZoom(src, alt) {
    imgEl.src = src;
    imgEl.alt = alt || '';
    capEl.textContent = alt || '';
    overlay.classList.add('open');
    document.body.classList.add('vrp-zoom-lock');
  }

  function closeZoom() {
    overlay.classList.remove('open');
    document.body.classList.remove('vrp-zoom-lock');
    imgEl.src = '';
  }

  overlay.addEventListener('click', (e) => {
    if (e.target.classList.contains('vrp-zoom-backdrop') || e.target.classList.contains('vrp-zoom-close')) {
      closeZoom();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeZoom();
  });

  // Make all content images zoomable.
  document.querySelectorAll('.content-html img').forEach(img => {
    img.style.cursor = 'zoom-in';
    img.addEventListener('click', (e) => {
      // If wrapped in a link, prevent navigation for normal click.
      e.preventDefault();
      e.stopPropagation();
      const a = img.closest('a');
      const src = (a && a.getAttribute('href')) ? a.getAttribute('href') : img.getAttribute('src');
      if (!src) return;
      openZoom(src, img.getAttribute('alt') || '');
    });
  });
});
