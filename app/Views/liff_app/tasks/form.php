<div class="page-header">
  <h1><?= esc($page_title) ?></h1>
</div>

<div id="form-alert"></div>

<form id="task-form" onsubmit="submitTask(event)">
  <?php $tid = $task->id ?? 0; ?>
  <input type="hidden" name="id" value="<?= $tid ?>">

  <div class="form-group">
    <label class="form-label">ชื่องาน *</label>
    <input class="form-control" name="title" required placeholder="ระบุชื่องาน..." value="<?= esc($task->title ?? '') ?>">
  </div>

  <div class="form-group">
    <label class="form-label">รายละเอียด</label>
    <textarea class="form-control" name="description" rows="3" placeholder="รายละเอียดเพิ่มเติม..."><?= esc($task->description ?? '') ?></textarea>
  </div>

  <div class="form-group">
    <label class="form-label">โปรเจกต์</label>
    <?php
      $selected_project = null;
      foreach ($projects as $p) {
        if (($task->project_id ?? 0) == $p->id) { $selected_project = $p; break; }
      }
    ?>
    <div class="custom-dropdown" id="project-dd">
      <input type="hidden" name="project_id" value="<?= esc($task->project_id ?? '') ?>">
      <button type="button" class="dropdown-trigger" id="project-trigger">
        <span><?= $selected_project ? esc($selected_project->title) : '— ไม่ระบุโปรเจกต์ —' ?></span>
        <span class="chev">▾</span>
      </button>
      <div class="dropdown-menu" id="project-menu">
        <div class="dropdown-item" data-value="">— ไม่ระบุโปรเจกต์ —</div>
        <?php foreach ($projects as $p): ?>
        <div class="dropdown-item" data-value="<?= $p->id ?>">
          <?= esc($p->title) ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="form-group">
    <label class="form-label">มอบหมายให้</label>
    <?php
      $selected_user = null;
      foreach ($users as $u) {
        if (($task->assigned_to ?? $login_user->id) == $u->id) { $selected_user = $u; break; }
      }
    ?>
    <div class="custom-dropdown" id="assignee-dd">
      <input type="hidden" name="assigned_to" value="<?= esc($task->assigned_to ?? $login_user->id) ?>">
      <button type="button" class="dropdown-trigger" id="assignee-trigger">
        <span><?= $selected_user ? esc(trim($selected_user->first_name . ' ' . $selected_user->last_name)) : '—' ?></span>
        <span class="chev">▾</span>
      </button>
      <div class="dropdown-menu" id="assignee-menu">
        <?php foreach ($users as $u): ?>
        <div class="dropdown-item" data-value="<?= $u->id ?>">
          <?= esc(trim($u->first_name . ' ' . $u->last_name)) ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="d-flex gap-8">
    <div class="form-group flex-1">
      <label class="form-label">วันเริ่ม</label>
      <input class="form-control" type="date" name="start_date" value="<?= esc(($task->start_date ?? '') ? date('Y-m-d', strtotime($task->start_date)) : '') ?>">
    </div>
    <div class="form-group flex-1">
      <label class="form-label">เวลาเริ่ม</label>
      <input class="form-control" type="time" name="start_time" value="<?= esc($task->start_time ?? '') ?>">
    </div>
  </div>

  <div class="d-flex gap-8">
    <div class="form-group flex-1">
      <label class="form-label">วันสิ้นสุด</label>
      <input class="form-control" type="date" name="deadline" value="<?= esc(($task->deadline ?? '') ? date('Y-m-d', strtotime($task->deadline)) : '') ?>">
    </div>
    <div class="form-group flex-1">
      <label class="form-label">เวลาสิ้นสุด</label>
      <input class="form-control" type="time" name="end_time" value="<?= esc($task->end_time ?? '') ?>">
    </div>
  </div>

  <div class="d-flex gap-8">
    <div class="form-group flex-1">
      <label class="form-label">ลำดับความสำคัญ</label>
      <?php
        $selected_prio = null;
        foreach ($priorities as $p) {
          if (($task->priority_id ?? 0) == $p->id) { $selected_prio = $p; break; }
        }
      ?>
      <div class="custom-dropdown" id="priority-dd">
        <input type="hidden" name="priority_id" value="<?= esc($task->priority_id ?? '') ?>">
        <button type="button" class="dropdown-trigger" id="priority-trigger">
          <span><?= $selected_prio ? esc($selected_prio->title) : '— เลือก —' ?></span>
          <span class="chev">▾</span>
        </button>
        <div class="dropdown-menu" id="priority-menu">
          <div class="dropdown-item" data-value="">— เลือก —</div>
          <?php foreach ($priorities as $p): ?>
          <div class="dropdown-item" data-value="<?= $p->id ?>">
            <?= esc($p->title) ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="form-group flex-1">
      <label class="form-label">สถานะ</label>
      <select class="form-control" name="status_id">
        <?php foreach ($statuses as $s): ?>
        <option value="<?= $s->id ?>" <?= ($task->status_id ?? 0) == $s->id ? 'selected' : '' ?>><?= esc($s->title) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- Image upload -->
  <div class="form-group">
    <label class="form-label">รูปภาพ</label>
    <div class="upload-zone" onclick="document.getElementById('img-input').click()">
      <p>แตะเพื่ออัปโหลดรูปภาพ</p>
    </div>
    <input type="file" id="img-input" name="manualFiles[]" accept="image/*" multiple hidden>
    <div class="upload-previews" id="img-previews"></div>
  </div>

  <!-- LINE Notification (optional) -->
  <div class="form-group">
    <div class="toggle-wrap">
      <div>
        <div class="toggle-label">LINE แจ้งเตือน</div>
        <div class="toggle-sub">ไม่บังคับ — เปิดเพื่อตั้งค่าการแจ้งเตือน</div>
      </div>
      <label class="toggle">
        <input type="checkbox" id="notify-toggle" name="line_notify_enabled" value="1"
          <?= !empty($task->line_notify_enabled) ? 'checked' : '' ?>>
        <span class="toggle-slider"></span>
      </label>
    </div>
    <div class="notify-section <?= !empty($task->line_notify_enabled) ? 'open' : '' ?>" id="notify-section">
      <div class="notify-row">
        <label>แจ้งเตือนก่อนเวลาเริ่ม</label>
        <input type="number" name="line_notify_before_start" min="0" max="1440"
          value="<?= esc($task->line_notify_before_start ?? '') ?>" placeholder="—">
        <span>นาที</span>
      </div>
      <div class="notify-row">
        <label>แจ้งเตือนก่อนเวลาสิ้นสุด</label>
        <input type="number" name="line_notify_before_end" min="0" max="1440"
          value="<?= esc($task->line_notify_before_end ?? '') ?>" placeholder="—">
        <span>นาที</span>
      </div>
      <div class="notify-row">
        <label>แจ้งหากไม่มีการอัปเดต</label>
        <input type="number" name="line_notify_no_update_hours" min="1" max="720"
          value="<?= esc($task->line_notify_no_update_hours ?? '') ?>" placeholder="—">
        <span>ชั่วโมง</span>
      </div>
      <p class="text-xs text-muted mt-8">ปล่อยว่างไว้ = ปิดการแจ้งเตือนนั้น</p>
    </div>
  </div>

  <div class="form-group">
    <div class="card" style="padding:12px">
      <div style="font-weight:600;margin-bottom:6px">ตัวอย่างเวลาที่ระบบจะส่ง (LIFF)</div>
      <div style="font-size:12px;color:#64748B" id="notify-preview-start">ก่อนเริ่ม: —</div>
      <div style="font-size:12px;color:#64748B" id="notify-preview-end">ก่อนสิ้นสุด: —</div>
      <div style="font-size:12px;color:#64748B" id="notify-preview-update">ไม่อัปเดต: —</div>
    </div>
  </div>

  <div class="form-group">
    <button type="button" class="btn btn-default btn-sm" onclick="testNotifyGroup()">
      ทดสอบส่งไปยังห้อง/กลุ่ม
    </button>
    <div id="notify-test-log" style="display:none;margin-top:8px;background:#0F172A;color:#E2E8F0;padding:10px;border-radius:8px;font-size:12px;white-space:pre-wrap;word-break:break-all"></div>
  </div>

  <button type="submit" class="btn btn-primary btn-block btn-lg" id="submit-btn">
    บันทึก
  </button>
</form>

<script>
// One shared handler: close all open custom dropdowns when clicking outside
document.addEventListener('click', (e) => {
  console.log('[DD] document click | target:', e.target, '| open before:', [...document.querySelectorAll('.custom-dropdown.open')].map(d=>d.id));
  document.querySelectorAll('.custom-dropdown.open')
    .forEach(d => d.classList.remove('open'));
});

function initTaskForm() {
  if (!window.LiffApp) {
    setTimeout(initTaskForm, 50);
    return;
  }

  LiffApp.initImageUpload('img-input', 'img-previews');
  LiffApp.initNotifyToggle('notify-toggle','notify-section');

  initDropdown('project-dd',  'project-trigger',  'project-menu',  'project_id');
  initDropdown('assignee-dd', 'assignee-trigger', 'assignee-menu', 'assigned_to');
  initDropdown('priority-dd', 'priority-trigger', 'priority-menu', 'priority_id');

  const notifyInputs = [
    'start_date','start_time','deadline','end_time',
    'line_notify_before_start','line_notify_before_end','line_notify_no_update_hours'
  ];
  notifyInputs.forEach(name => {
    const el = document.querySelector(`[name="${name}"]`);
    if (el) el.addEventListener('input', updateNotifyPreview);
  });
  updateNotifyPreview();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initTaskForm);
} else {
  initTaskForm();
}

async function submitTask(e) {
  e.preventDefault();
  const btn = document.getElementById('submit-btn');
  btn.textContent = 'กำลังบันทึก...';
  btn.disabled = true;

  const form = new FormData(e.target);
  const res = await LiffApp.api('liff/api/tasks/save', 'POST', form);
  if (res.success) {
    LiffApp.toast('บันทึกสำเร็จ', 'success');
    setTimeout(() => location.href = res.redirect, 800);
  } else {
    document.getElementById('form-alert').innerHTML =
      `<div class="alert alert-danger">${res.message}</div>`;
    btn.textContent = 'บันทึก';
    btn.disabled = false;
  }
}

function initDropdown(wrapId, triggerId, menuId, inputName) {
  const wrap    = document.getElementById(wrapId);
  if (!wrap) { console.warn('[DD] wrap not found:', wrapId); return; }
  const trigger = document.getElementById(triggerId);
  const menu    = document.getElementById(menuId);
  const input   = wrap.querySelector(`input[name="${inputName}"]`);
  console.log('[DD] initDropdown | wrapId:', wrapId, '| trigger:', trigger, '| menu:', menu, '| input:', input);

  trigger.addEventListener('click', (e) => {
    e.stopPropagation();
    const opening = !wrap.classList.contains('open');
    console.log('[DD] trigger click | wrapId:', wrapId, '| currently open:', !opening, '| will open:', opening);
    document.querySelectorAll('.custom-dropdown.open').forEach(d => d.classList.remove('open'));
    if (opening) wrap.classList.add('open');
    console.log('[DD] after toggle | wrap classes:', wrap.className);
  });

  menu.querySelectorAll('.dropdown-item').forEach(item => {
    item.addEventListener('click', (e) => {
      e.stopPropagation();
      input.value = item.dataset.value || '';
      trigger.querySelector('span').textContent = item.textContent.trim();
      menu.querySelectorAll('.dropdown-item').forEach(i => i.classList.remove('active'));
      item.classList.add('active');
      wrap.classList.remove('open');
    });
  });

  // Highlight current selection
  const current = input.value || '';
  menu.querySelectorAll('.dropdown-item').forEach(item => {
    if ((item.dataset.value || '') === current) item.classList.add('active');
  });
}

function updateNotifyPreview() {
  const startDate = document.querySelector('[name="start_date"]').value;
  const startTime = document.querySelector('[name="start_time"]').value;
  const endDate = document.querySelector('[name="deadline"]').value;
  const endTime = document.querySelector('[name="end_time"]').value;
  const beforeStart = parseInt(document.querySelector('[name="line_notify_before_start"]').value || '', 10);
  const beforeEnd = parseInt(document.querySelector('[name="line_notify_before_end"]').value || '', 10);
  const noUpdate = parseInt(document.querySelector('[name="line_notify_no_update_hours"]').value || '', 10);

  const startEl = document.getElementById('notify-preview-start');
  const endEl = document.getElementById('notify-preview-end');
  const updEl = document.getElementById('notify-preview-update');

  startEl.textContent = 'ก่อนเริ่ม: —';
  endEl.textContent = 'ก่อนสิ้นสุด: —';
  updEl.textContent = 'ไม่อัปเดต: —';

  if (startDate && startTime && !isNaN(beforeStart)) {
    const dt = new Date(`${startDate}T${startTime}`);
    dt.setMinutes(dt.getMinutes() - beforeStart);
    startEl.textContent = `ก่อนเริ่ม: ${formatDateTime(dt)}`;
  }

  if (endDate && endTime && !isNaN(beforeEnd)) {
    const dt = new Date(`${endDate}T${endTime}`);
    dt.setMinutes(dt.getMinutes() - beforeEnd);
    endEl.textContent = `ก่อนสิ้นสุด: ${formatDateTime(dt)}`;
  }

  if (!isNaN(noUpdate) && noUpdate > 0) {
    const now = new Date();
    const dt = new Date(now.getTime() + noUpdate * 60 * 60 * 1000);
    updEl.textContent = `ไม่อัปเดต: ${formatDateTime(dt)} (จากตอนนี้)`;
  }
}

function formatDateTime(dt) {
  const pad = n => String(n).padStart(2, '0');
  return `${pad(dt.getDate())}/${pad(dt.getMonth()+1)} ${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
}

async function testNotifyGroup() {
  const logEl = document.getElementById('notify-test-log');
  logEl.style.display = 'none';
  logEl.textContent = '';

  const data = {
    id: document.querySelector('[name="id"]').value,
    title: document.querySelector('[name="title"]').value,
    start_date: document.querySelector('[name="start_date"]').value,
    start_time: document.querySelector('[name="start_time"]').value,
    deadline: document.querySelector('[name="deadline"]').value,
    end_time: document.querySelector('[name="end_time"]').value
  };

  const res = await LiffApp.api('liff/api/tasks/test_notify', 'POST', data);
  if (res.success) {
    LiffApp.toast(res.message || 'ส่งทดสอบสำเร็จ', 'success');
  } else {
    LiffApp.toast(res.message || 'ส่งทดสอบไม่สำเร็จ', 'error');
  }
  logEl.textContent = JSON.stringify(res, null, 2);
  logEl.style.display = 'block';
}
</script>
