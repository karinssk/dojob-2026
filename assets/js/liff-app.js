/**
 * DoJob LIFF App — Common helpers
 */

const LiffApp = (() => {
  const BASE = document.documentElement.dataset.base || '';
  const BASE_URL = BASE.endsWith('/') ? BASE : BASE + '/';

  /* ── API fetch ── */
  async function api(path, method = 'GET', body = null) {
    const url = BASE_URL + path;
    const debug = {
      url,
      method,
      request: summarizeBody(body),
      timestamp: new Date().toISOString(),
    };
    const opts = {
      method,
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    };
    if (body) {
      if (body instanceof FormData) {
        opts.body = body;
      } else {
        opts.headers['Content-Type'] = 'application/x-www-form-urlencoded';
        opts.body = new URLSearchParams(body).toString();
      }
    }

    let res;
    try {
      res = await fetch(url, opts);
    } catch (e) {
      debug.network_error = e.message;
      logDebug(debug);
      return { success: false, message: 'Network error', debug };
    }

    debug.status = res.status;
    debug.statusText = res.statusText;

    const text = await res.text();
    debug.response = text;

    let data = null;
    try {
      data = JSON.parse(text);
      debug.response_json = data;
    } catch (e) {
      debug.parse_error = e.message;
    }

    const isError = !res.ok || !data || data.success === false;
    if (isError) {
      logDebug(debug);
    }

    if (data) {
      return data;
    }
    return { success: false, message: `Request failed (HTTP ${res.status})`, debug };
  }

  /* ── Toast notification ── */
  function toast(msg, type = 'info', duration = 3000) {
    const el = document.createElement('div');
    el.className = `liff-toast liff-toast-${type}`;
    el.textContent = msg;
    Object.assign(el.style, {
      position: 'fixed', top: '16px', left: '50%', transform: 'translateX(-50%)',
      background: type === 'success' ? '#6FCBA3' : type === 'error' ? '#F97FA3' : '#6C8EF5',
      color: '#fff', padding: '10px 20px', borderRadius: '999px',
      fontSize: '14px', fontWeight: '600', zIndex: '9999',
      boxShadow: '0 4px 20px rgba(0,0,0,0.15)',
      animation: 'slide-up 0.2s ease',
      whiteSpace: 'nowrap', maxWidth: '90vw',
    });
    document.body.appendChild(el);
    setTimeout(() => el.remove(), duration);
  }

  /* ── Tabs ── */
  function initTabs(containerSel) {
    const container = document.querySelector(containerSel);
    if (!container) return;
    container.querySelectorAll('.tab-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const target = btn.dataset.tab;
        container.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        container.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        const panel = container.querySelector(`.tab-panel[data-tab="${target}"]`);
        if (panel) panel.classList.add('active');
      });
    });
  }

  /* ── LINE Notify toggle ── */
  function initNotifyToggle(toggleId, sectionId) {
    const toggle  = document.getElementById(toggleId);
    const section = document.getElementById(sectionId);
    if (!toggle || !section) return;

    const defaults = {
      before_start: parseInt(document.documentElement.dataset.defaultStart || '30'),
      before_end:   parseInt(document.documentElement.dataset.defaultEnd   || '60'),
      no_update:    parseInt(document.documentElement.dataset.defaultUpdate || '24'),
    };

    const applyState = () => {
      if (toggle.checked) {
        section.classList.add('open');
        // Pre-fill with system defaults if empty
        const f1 = section.querySelector('[name="line_notify_before_start"]');
        const f2 = section.querySelector('[name="line_notify_before_end"]');
        const f3 = section.querySelector('[name="line_notify_no_update_hours"]');
        if (f1 && !f1.value) f1.value = defaults.before_start;
        if (f2 && !f2.value) f2.value = defaults.before_end;
        if (f3 && !f3.value) f3.value = defaults.no_update;
      } else {
        section.classList.remove('open');
      }
    };

    toggle.addEventListener('change', applyState);
    applyState();
  }

  /* ── Image upload preview ── */
  function initImageUpload(inputId, previewsId) {
    const input    = document.getElementById(inputId);
    const previews = document.getElementById(previewsId);
    if (!input || !previews) return;

    input.addEventListener('change', () => {
      previews.innerHTML = '';
      Array.from(input.files).forEach(file => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = e => {
          const wrap = document.createElement('div');
          wrap.className = 'upload-preview-wrap';
          wrap.innerHTML = `
            <img src="${e.target.result}" class="upload-preview" alt="">
            <button class="remove-img" onclick="this.parentElement.remove()">×</button>`;
          previews.appendChild(wrap);
        };
        reader.readAsDataURL(file);
      });
    });

    // Drag & drop
    const zone = input.closest('.upload-zone');
    if (zone) {
      zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
      zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
      zone.addEventListener('drop', e => {
        e.preventDefault(); zone.classList.remove('drag-over');
        input.files = e.dataTransfer.files;
        input.dispatchEvent(new Event('change'));
      });
      zone.addEventListener('click', () => input.click());
    }
  }

  /* ── Quick status update ── */
  async function updateTaskStatus(taskId, statusId, chipEl) {
    console.log('[updateTaskStatus] called', { taskId, statusId });
    const res = await api('liff/api/tasks/update_status', 'POST', { task_id: taskId, status_id: statusId });
    console.log('[updateTaskStatus] API response', res);
    if (res.success) {
      console.log('[updateTaskStatus] status_key =', res.status_key, '| is done?', res.status_key === 'done');
      if (chipEl) { chipEl.textContent = res.status_title; }
      if (res.status_key === 'done') {
        console.log('[updateTaskStatus] triggering celebrate()');
        celebrate();
      } else {
        toast('อัปเดตสถานะแล้ว', 'success');
      }
    } else {
      console.log('[updateTaskStatus] FAILED', res.message);
      toast(res.message || 'เกิดข้อผิดพลาด', 'error');
    }
  }

  /* ── Celebration confetti (triggered on task done) ── */
  function celebrate() {
    const EMOJIS = ['🍤', '🥑', '🧀', '🍓', '🫐', '🥚', '⭐', '✨', '🎉', '🌟'];
    const COUNT  = 50;

    const overlay = document.createElement('div');
    overlay.className = 'celebrate-overlay';
    document.body.appendChild(overlay);

    // Confetti pieces
    for (var i = 0; i < COUNT; i++) {
      var piece = document.createElement('div');
      piece.className = 'confetti-piece';
      var dx       = (Math.random() - 0.5) * 350;
      var dy       = (Math.random() - 1)   * 500 - 150;
      var rot      = Math.random() * 720 - 360;
      var scale    = 0.6 + Math.random() * 0.8;
      var delay    = Math.random() * 0.15;
      var duration = i % 6 === 4 ? 5 : i % 6 === 1 || i % 6 === 3 ? 3 : 4;
      piece.textContent = EMOJIS[i % EMOJIS.length];
      piece.style.cssText = [
        '--dx:' + dx + 'px',
        '--dy:' + dy + 'px',
        '--rot:' + rot + 'deg',
        '--sc:'  + scale,
        'animation-delay:'    + delay + 's',
        'animation-duration:' + duration + 's',
      ].join(';');
      overlay.appendChild(piece);
    }

    // Central card
    var card = document.createElement('div');
    card.className = 'celebrate-card';
    card.innerHTML =
      '<div class="celebrate-mascot">👨‍🍳</div>' +
      '<div class="celebrate-title">เสร็จแล้ว!</div>' +
      '<div class="celebrate-sub">ทำได้ดีมากเลย 🎉</div>';
    overlay.appendChild(card);

    // Dismiss helpers
    function dismiss() {
      overlay.classList.add('celebrate-out');
      setTimeout(function () { overlay.remove(); }, 400);
    }
    overlay.onclick = dismiss;
    setTimeout(dismiss, 3000);
  }

  /* ── Todo toggle ── */
  async function toggleTodo(todoId, checkEl) {
    const res = await api('liff/api/todo/toggle', 'POST', { id: todoId });
    if (res.success) {
      const card = checkEl.closest('.todo-item');
      if (card) card.classList.toggle('done', res.done);
    } else {
      toast(res.message || 'เกิดข้อผิดพลาด', 'error');
    }
  }

  /* ── Confirm dialog ── */
  function confirm(msg) {
    return window.confirm(msg);
  }

  /* ── Format date ── */
  function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' });
  }

  function summarizeBody(body) {
    if (!body) return null;
    if (body instanceof FormData) {
      const out = {};
      for (const [k, v] of body.entries()) {
        out[k] = v instanceof File ? `[file:${v.name || 'blob'}]` : v;
      }
      return out;
    }
    return body;
  }

  function logDebug(payload) {
    if (!payload) return;
    console.error('[LIFF API DEBUG]', payload);
    const el = document.getElementById('liff-debug-log');
    if (el) {
      el.textContent = JSON.stringify(payload, null, 2);
      el.style.display = 'block';
    }
  }

  return { api, toast, initTabs, initNotifyToggle, initImageUpload, updateTaskStatus, toggleTodo, confirm, formatDate, celebrate };
})();

// Auto-init tabs on page load
document.addEventListener('DOMContentLoaded', () => {
  LiffApp.initTabs('[data-tabs]');
});
