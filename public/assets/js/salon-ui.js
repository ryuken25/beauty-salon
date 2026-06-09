/*!
 * SW Beauty Salon — UI helpers
 *
 *  - window.salonToast(message, type='info', timeout=3500)
 *      type ∈ success | error | info. Fixed bottom-right, auto-hide.
 *
 *  - window.salonConfirm({ title, body, confirmText='Ya', cancelText='Batal', danger=false })
 *      Returns Promise<boolean>. Uses one shared Bootstrap modal injected
 *      into the document. Theme-token styled (modal-salon).
 *
 *  Deklaratif (DOMContentLoaded):
 *    - <form data-confirm="pesan" [data-confirm-title="..."] [data-confirm-danger="1"]>
 *      → intercept submit, show salonConfirm; if true, lepas listener & submit.
 *    - any element with data-copy="#targetId"
 *      → copy target.value || target.textContent → salonToast('Disalin','success').
 */
(function () {
  'use strict';

  // ── Toast container ─────────────────────────────────────────
  function ensureToastWrap() {
    let wrap = document.querySelector('.toast-salon__wrap');
    if (! wrap) {
      wrap = document.createElement('div');
      wrap.className = 'toast-salon__wrap';
      document.body.appendChild(wrap);
    }
    return wrap;
  }

  window.salonToast = function (message, type = 'info', timeout = 3500) {
    const wrap = ensureToastWrap();
    const el = document.createElement('div');
    el.className = 'toast-salon toast-salon--' + (['success', 'error', 'info'].includes(type) ? type : 'info');
    el.textContent = message;
    wrap.appendChild(el);
    // Animate in next frame so the transition applies.
    requestAnimationFrame(() => el.classList.add('toast-salon--shown'));
    setTimeout(() => {
      el.classList.remove('toast-salon--shown');
      setTimeout(() => el.remove(), 250);
    }, timeout);
  };

  // ── Confirm modal — single shared instance ──────────────────
  let _confirmEl = null;
  function ensureConfirmModal() {
    if (_confirmEl) return _confirmEl;
    _confirmEl = document.createElement('div');
    _confirmEl.className = 'modal fade modal-salon';
    _confirmEl.tabIndex = -1;
    _confirmEl.setAttribute('aria-hidden', 'true');
    _confirmEl.innerHTML = `
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" data-role="title" style="font-family:var(--font-display);"></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body" data-role="body"></div>
          <div class="modal-footer" style="gap:0.5rem;">
            <button type="button" class="btn-salon-secondary" data-role="cancel"></button>
            <button type="button" class="btn-salon-primary" data-role="confirm"></button>
          </div>
        </div>
      </div>`;
    document.body.appendChild(_confirmEl);
    return _confirmEl;
  }

  window.salonConfirm = function (opts) {
    opts = opts || {};
    const el = ensureConfirmModal();
    el.querySelector('[data-role=title]').textContent = opts.title || 'Konfirmasi';
    el.querySelector('[data-role=body]').textContent = opts.body || 'Lanjutkan?';
    const cancel = el.querySelector('[data-role=cancel]');
    const confirm = el.querySelector('[data-role=confirm]');
    cancel.textContent = opts.cancelText || 'Batal';
    confirm.textContent = opts.confirmText || 'Ya';
    confirm.className = opts.danger ? 'btn-salon-danger' : 'btn-salon-primary';

    if (! window.bootstrap) return Promise.resolve(window.confirm(opts.body || 'Lanjutkan?'));
    const modal = bootstrap.Modal.getOrCreateInstance(el);

    return new Promise((resolve) => {
      let resolved = false;
      const cleanup = () => {
        confirm.removeEventListener('click', onConfirm);
        cancel.removeEventListener('click', onCancel);
        el.removeEventListener('hidden.bs.modal', onHidden);
      };
      const onConfirm = () => { resolved = true; cleanup(); modal.hide(); resolve(true); };
      const onCancel  = () => { resolved = true; cleanup(); modal.hide(); resolve(false); };
      const onHidden  = () => { if (! resolved) { cleanup(); resolve(false); } };
      confirm.addEventListener('click', onConfirm);
      cancel.addEventListener('click', onCancel);
      el.addEventListener('hidden.bs.modal', onHidden);
      modal.show();
    });
  };

  // ── Auto-wire deklaratif ────────────────────────────────────
  document.addEventListener('DOMContentLoaded', () => {
    // Form[data-confirm] → confirm modal sebelum submit
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
      form.addEventListener('submit', (ev) => {
        if (form.dataset._confirmed === '1') return;
        ev.preventDefault();
        salonConfirm({
          title: form.dataset.confirmTitle || 'Konfirmasi',
          body: form.dataset.confirm,
          confirmText: form.dataset.confirmYes || 'Ya',
          cancelText: form.dataset.confirmNo || 'Batal',
          danger: form.dataset.confirmDanger === '1',
        }).then((ok) => {
          if (ok) {
            form.dataset._confirmed = '1';
            form.submit();
          }
        });
      });
    });

    // [data-copy="#id"] → copy & toast
    document.querySelectorAll('[data-copy]').forEach((btn) => {
      btn.addEventListener('click', (ev) => {
        ev.preventDefault();
        const tgt = document.querySelector(btn.dataset.copy);
        if (! tgt) return;
        const text = ('value' in tgt) ? tgt.value : tgt.textContent;
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(text).then(
            () => salonToast('Disalin ke clipboard', 'success'),
            () => salonToast('Gagal menyalin', 'error')
          );
        } else {
          // Fallback for old browsers
          const ta = document.createElement('textarea');
          ta.value = text; document.body.appendChild(ta); ta.select();
          try { document.execCommand('copy'); salonToast('Disalin ke clipboard', 'success'); }
          catch (e) { salonToast('Gagal menyalin', 'error'); }
          ta.remove();
        }
      });
    });
  });
})();
