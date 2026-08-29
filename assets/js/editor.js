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
    const styleMenu = wrapper.querySelector('[data-style-menu]');
    let savedRange = null;
    let selectedImage = null;

    const contentStyles = {
      paragraph: { label: 'Paragraph', tag: 'p', className: '' },
      lead: { label: 'Lead paragraph', tag: 'p', className: 'vrp-lead' },
      note: { label: 'Note box', tag: 'p', className: 'vrp-callout vrp-callout-note' },
      tip: { label: 'Tip box', tag: 'p', className: 'vrp-callout vrp-callout-tip' },
      warning: { label: 'Warning box', tag: 'p', className: 'vrp-callout vrp-callout-warning' },
      success: { label: 'Success box', tag: 'p', className: 'vrp-callout vrp-callout-success' },
      quote: { label: 'Quotation', tag: 'blockquote', className: 'vrp-blockquote' },
      code: { label: 'Code block', tag: 'pre', className: 'vrp-code-block' },
    };
    const managedStyleClasses = [
      'vrp-lead', 'vrp-callout', 'vrp-callout-note', 'vrp-callout-tip',
      'vrp-callout-warning', 'vrp-callout-success', 'vrp-blockquote', 'vrp-code-block',
    ];

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

    styleMenu?.addEventListener('change', () => {
      if (styleMenu.value) applyContentStyle(styleMenu.value);
      styleMenu.value = '';
    });

    wrapper.querySelectorAll('.js-open-style-guide').forEach(btn => {
      btn.addEventListener('click', openStyleGuide);
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

    function applyContentStyle(styleKey) {
      const style = contentStyles[styleKey];
      if (!style) return;
      restoreSelection();
      area.focus();
      document.execCommand('formatBlock', false, style.tag);

      const block = getActiveBlock();
      if (block) {
        block.classList.remove(...managedStyleClasses);
        if (style.className) {
          block.classList.add(...style.className.split(' '));
        }
      }
      sync();
      saveSelection();
    }

    function getActiveBlock() {
      const selection = window.getSelection();
      if (!selection || !selection.rangeCount) return null;
      let node = selection.anchorNode;
      if (node?.nodeType === Node.TEXT_NODE) node = node.parentElement;
      const block = node?.closest?.('p, blockquote, pre, h1, h2, h3, h4, div');
      return block && area.contains(block) ? block : null;
    }

    function openStyleGuide() {
      saveSelection();
      let guide = wrapper.querySelector('.vsa-style-guide-modal');
      if (!guide) {
        guide = document.createElement('div');
        guide.className = 'vsa-style-guide-modal open';
        guide.innerHTML = `
          <div class="vsa-style-guide-card" role="dialog" aria-modal="true" aria-label="VRAPress content style guide">
            <div class="vsa-style-guide-head">
              <div><h3>VRAPress Style Guide</h3><p>Place the cursor in a paragraph, then choose a style.</p></div>
              <button type="button" class="btn light js-close-style-guide">Close</button>
            </div>
            <div class="vsa-style-guide-grid">
              ${Object.entries(contentStyles).map(([key, style]) => `
                <button type="button" class="vsa-style-sample" data-apply-style="${key}">
                  <span class="vsa-style-sample-name">${escapeHtml(style.label)}</span>
                  <span class="vsa-style-preview ${escapeAttr(style.className)}">${stylePreviewText(key)}</span>
                </button>`).join('')}
            </div>
          </div>`;
        wrapper.appendChild(guide);
        guide.addEventListener('click', (event) => {
          if (event.target === guide || event.target.closest('.js-close-style-guide')) {
            guide.classList.remove('open');
            return;
          }
          const applyButton = event.target.closest('[data-apply-style]');
          if (applyButton) {
            applyContentStyle(applyButton.dataset.applyStyle);
            guide.classList.remove('open');
          }
        });
      } else {
        guide.classList.add('open');
      }
    }

    function stylePreviewText(key) {
      return ({
        paragraph: 'Standard body paragraph for normal content.',
        lead: 'A prominent introduction or summary paragraph.',
        note: 'Note: Useful supporting information for readers.',
        tip: 'Tip: A practical recommendation or shortcut.',
        warning: 'Warning: Important caution requiring attention.',
        success: 'Success: A completed or positive result.',
        quote: '“A highlighted quotation or testimonial.”',
        code: 'const vrapress = "clear and dependable";',
      })[key] || '';
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
