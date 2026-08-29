document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.js-vsa-editor').forEach(initEditor);

  function initEditor(wrapper) {
    const source = wrapper.querySelector('.vsa-editor-source');
    const area = wrapper.querySelector('.vsa-editor-area');
    const uploadInput = wrapper.querySelector('.vsa-editor-upload');
    const uploadUrl = wrapper.dataset.uploadUrl || '';
    const libraryUrl = wrapper.dataset.libraryUrl || '';
    const csrf = wrapper.dataset.csrf || '';
    const modal = wrapper.querySelector('.vsa-media-modal');
    const modalBody = wrapper.querySelector('.vsa-media-modal-body');
    let savedRange = null;
    let selectedImage = null;

    if (!source || !area) return;
    area.innerHTML = source.value || '';

    area.addEventListener('mouseup', saveSelection);
    area.addEventListener('keyup', saveSelection);
    area.addEventListener('focus', saveSelection);
    area.addEventListener('input', sync);
    area.closest('form')?.addEventListener('submit', sync);
    area.addEventListener('click', (e) => {
      const img = e.target.closest('img');
      if (img) {
        selectImage(img);
      } else {
        clearImageSelection();
      }
      saveSelection();
    });

    wrapper.querySelectorAll('[data-cmd]').forEach(btn => {
      btn.addEventListener('click', () => {
        restoreSelection();
        const cmd = btn.dataset.cmd;
        area.focus();
        if (cmd === 'createLink') {
          const url = prompt('Enter link URL:');
          if (url) document.execCommand('createLink', false, url);
        } else if (cmd === 'removeFormat') {
          document.execCommand('removeFormat', false, null);
        } else if (cmd === 'formatBlock') {
          document.execCommand('formatBlock', false, btn.dataset.value || 'p');
        } else {
          document.execCommand(cmd, false, null);
        }
        sync();
        saveSelection();
      });
    });

    wrapper.querySelectorAll('[data-image-size]').forEach(btn => {
      btn.addEventListener('click', () => applyImageSize(btn.dataset.imageSize));
    });

    wrapper.querySelectorAll('[data-image-align]').forEach(btn => {
      btn.addEventListener('click', () => applyImageAlign(btn.dataset.imageAlign));
    });

    wrapper.querySelectorAll('[data-image-alt]').forEach(btn => {
      btn.addEventListener('click', () => {
        const wrapperEl = ensureWrapper();
        if (!wrapperEl || !selectedImage) return;
        const currentAlt = selectedImage.getAttribute('alt') || '';
        const nextAlt = askAltText(currentAlt || 'Image');
        selectedImage.setAttribute('alt', nextAlt);
        const cap = wrapperEl.querySelector('figcaption');
        if (cap) cap.textContent = nextAlt;
        sync();
      });
    });

    wrapper.querySelectorAll('.js-open-upload').forEach(btn => {
      btn.addEventListener('click', () => {
        saveSelection();
        uploadInput?.click();
      });
    });

    wrapper.querySelectorAll('.js-open-library').forEach(btn => {
      btn.addEventListener('click', async () => {
        saveSelection();
        await openLibrary();
      });
    });

    if (uploadInput && uploadUrl) {
      uploadInput.addEventListener('change', async () => {
        const files = Array.from(uploadInput.files || []);
        if (!files.length) return;
        for (const file of files) {
          const fd = new FormData();
          fd.append('csrf_token', csrf);
          fd.append('file', file);
          try {
            const res = await fetch(uploadUrl, { method: 'POST', body: fd });
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.message || 'Upload failed');
            insertMedia(data, { askAlt: true });
            sync();
          } catch (err) {
            alert(err.message || 'Upload failed');
            break;
          }
        }
        uploadInput.value = '';
      });
    }

    modal?.addEventListener('click', (e) => {
      if (e.target.matches('.vsa-media-modal, .js-close-media-modal')) {
        closeLibrary();
        return;
      }
      const insertBtn = e.target.closest('.js-insert-selected');
      if (insertBtn) {
        const selected = Array.from(modal.querySelectorAll('input.vsa-media-check:checked'))
          .map(chk => JSON.parse(chk.dataset.mediaItem || '{}'))
          .filter(it => it && it.file_url);
        if (!selected.length) {
          alert('Select one or more media items first.');
          return;
        }
        for (const item of selected) {
          insertMedia(item, { askAlt: selected.length === 1 });
        }
        closeLibrary();
        return;
      }
      const card = e.target.closest('[data-media-item]');
      if (card) {
        const chk = card.querySelector('input.vsa-media-check');
        if (chk) {
          // Prevent double-toggling when clicking the checkbox/label.
          if (e.target.closest('.vsa-media-checkwrap')) {
            e.preventDefault();
          }
          chk.checked = !chk.checked;
          card.classList.toggle('is-selected', chk.checked);
        }
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeLibrary();
    });

    function saveSelection() {
      const sel = window.getSelection();
      if (sel && sel.rangeCount > 0 && area.contains(sel.anchorNode)) {
        savedRange = sel.getRangeAt(0).cloneRange();
      }
    }

    function restoreSelection() {
      area.focus();
      const sel = window.getSelection();
      if (savedRange && sel) {
        sel.removeAllRanges();
        sel.addRange(savedRange);
      }
    }

    function sync() {
      source.value = area.innerHTML.trim();
    }

    function askAltText(defaultAlt) {
      let alt = prompt('Alt text for the image (for accessibility/SEO):', defaultAlt || '');
      if (alt === null) alt = defaultAlt || '';
      alt = String(alt || '').trim();
      if (!alt) alt = defaultAlt || 'Image';
      return alt;
    }

    function insertMedia(item, options = {}) {
      restoreSelection();
      clearImageSelection();
      if ((item.mime_type || '').startsWith('image/')) {
        const defaultAlt = (item.original_name || 'Image').replace(/\.[a-z0-9]+$/i, '');
        const altText = options.askAlt ? askAltText(defaultAlt) : defaultAlt;
        const alt = escapeHtml(altText);
        const url = escapeAttr(item.file_url);
        const html = `<figure class="vsa-image-wrap size-medium align-center"><a class="vrp-zoom" href="${url}" target="_blank" rel="noopener"><img src="${url}" alt="${alt}" data-media-id="${escapeAttr(String(item.id || ''))}"></a><figcaption>${alt}</figcaption></figure><p><br></p>`;
        document.execCommand('insertHTML', false, html);
        const imgs = area.querySelectorAll(`img[src="${cssEscape(item.file_url)}"]`);
        if (imgs.length) selectImage(imgs[imgs.length - 1]);
      } else {
        const label = escapeHtml(item.original_name || item.file_name || 'Download file');
        document.execCommand('insertHTML', false, `<p><a href="${escapeAttr(item.file_url)}" target="_blank" rel="noopener">${label}</a></p>`);
      }
      sync();
      saveSelection();
    }

    async function openLibrary() {
      if (!modal || !modalBody || !libraryUrl) return;
      modal.classList.add('open');
      modalBody.innerHTML = '<div class="muted">Loading media library...</div>';
      try {
        const res = await fetch(libraryUrl, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!res.ok || !data.ok) throw new Error(data.message || 'Could not load media');
        if (!data.items.length) {
          modalBody.innerHTML = '<div class="muted">No media uploaded yet.</div>';
          return;
        }
        const actions = `<div class="vsa-media-actions"><button type="button" class="btn js-insert-selected">Insert Selected</button><span class="muted small">Tip: click items to select multiple</span></div>`;
        modalBody.innerHTML = actions + '<div class="vsa-media-grid">' + data.items.map(item => {
          const preview = (item.mime_type || '').startsWith('image/')
            ? `<img src="${escapeAttr(item.file_url)}" alt="">`
            : '<div class="media-pdf">PDF</div>';
          const payload = escapeAttr(JSON.stringify(item));
          return `<div class="vsa-media-select-card" data-media-item='${payload}'>
              <label class="vsa-media-checkwrap"><input class="vsa-media-check" type="checkbox" data-media-item='${payload}'></label>
              <div class="vsa-media-select-thumb">${preview}</div>
              <div class="vsa-media-select-name">${escapeHtml(item.original_name || '')}</div>
              <div class="small muted">${escapeHtml(item.mime_type || '')}</div>
            </div>`;
        }).join('') + '</div>';
      } catch (err) {
        modalBody.innerHTML = `<div class="flash error">${escapeHtml(err.message || 'Could not load media')}</div>`;
      }
    }

    function closeLibrary() {
      modal?.classList.remove('open');
    }

    function selectImage(img) {
      clearImageSelection();
      selectedImage = img;
      img.classList.add('is-selected');
    }

    function clearImageSelection() {
      if (selectedImage) selectedImage.classList.remove('is-selected');
      selectedImage = null;
    }

    function ensureWrapper() {
      if (!selectedImage) {
        alert('Click an image in the editor first.');
        return null;
      }
      let wrapperEl = selectedImage.closest('figure.vsa-image-wrap');
      if (!wrapperEl) {
        wrapperEl = document.createElement('figure');
        wrapperEl.className = 'vsa-image-wrap size-medium align-center';
        selectedImage.parentNode.insertBefore(wrapperEl, selectedImage);
        wrapperEl.appendChild(selectedImage);
      }
      return wrapperEl;
    }

    function applyImageSize(size) {
      const wrapperEl = ensureWrapper();
      if (!wrapperEl) return;
      wrapperEl.classList.remove('size-small', 'size-medium', 'size-large', 'size-full');
      wrapperEl.classList.add(`size-${size}`);
      sync();
    }

    function applyImageAlign(align) {
      const wrapperEl = ensureWrapper();
      if (!wrapperEl) return;
      wrapperEl.classList.remove('align-left', 'align-center', 'align-right');
      wrapperEl.classList.add(`align-${align}`);
      sync();
    }
  }

  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, s => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[s]));
  }
  function escapeAttr(str) { return escapeHtml(str); }
  function cssEscape(str) {
    if (window.CSS && CSS.escape) return CSS.escape(str);
    return String(str).replace(/(["\\.#:[\]()])/g, '\\$1');
  }
});
