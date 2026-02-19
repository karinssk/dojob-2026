/**
 * DoJob LIFF App — Common helpers
 */

const LiffApp = (() => {
  const BASE = document.documentElement.dataset.base || '';
  const BASE_URL = BASE.endsWith('/') ? BASE : BASE + '/';

  /* ── API fetch ── */
  async function api(path, method = 'GET', body = null) {
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
    const res  = await fetch(BASE_URL + path, opts);
    const text = await res.text();
    try { return JSON.parse(text); } catch { return { success: false, message: text }; }
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

    toggle.addEventListener('change', () => {
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
    });
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
    const res = await api('liff/api/tasks/update_status', 'POST', { task_id: taskId, status_id: statusId });
    if (res.success) {
      toast('อัปเดตสถานะแล้ว', 'success');
      if (chipEl) { chipEl.textContent = res.status_title; }
    } else {
      toast(res.message || 'เกิดข้อผิดพลาด', 'error');
    }
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

  return { api, toast, initTabs, initNotifyToggle, initImageUpload, updateTaskStatus, toggleTodo, confirm, formatDate };
})();

// Auto-init tabs on page load
document.addEventListener('DOMContentLoaded', () => {
  LiffApp.initTabs('[data-tabs]');
});
