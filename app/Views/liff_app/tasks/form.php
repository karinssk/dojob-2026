<div class="page-header">
  <h1><?= esc($page_title) ?></h1>
</div>

<div id="form-alert"></div>

<form id="task-form" onsubmit="submitTask(event)">
  <?php
    $tid = $task->id ?? 0; // used throughout this form

    // ── New task defaults (only applied when creating, not editing) ──
    $today = date('Y-m-d');
    $default_start_date = '09:00';   // time strings used below
    $default_end_time   = '17:30';

    // Resolve values: editing → saved value | new → default
    $val_start_date = $tid ? (($task->start_date ?? '') ? date('Y-m-d', strtotime($task->start_date)) : '') : $today;
    $val_start_time = $tid ? esc($task->start_time ?? '')  : $default_start_date;
    $val_deadline   = $tid ? (($task->deadline   ?? '') ? date('Y-m-d', strtotime($task->deadline))   : '') : $today;
    $val_end_time   = $tid ? esc($task->end_time  ?? '')  : $default_end_time;

    // "To Do" status: find id where key_name='to_do'; fall back to first status
    $default_status_id = 0;
    foreach ($statuses as $s) {
        if (($s->key_name ?? '') === 'to_do') { $default_status_id = $s->id; break; }
    }
    if (!$default_status_id && !empty($statuses)) { $default_status_id = $statuses[0]->id; }
    $val_status_id = $tid ? ($task->status_id ?? $default_status_id) : $default_status_id;
  ?>
  <input type="hidden" name="id" value="<?= $tid ?>">

  <div class="form-group">
    <label class="form-label">ชื่องาน *</label>
    <input class="form-control" name="title" required placeholder="ระบุชื่องาน..." value="<?= esc($task->title ?? '') ?>">
  </div>

  <div class="form-group">
    <label class="form-label">รายละเอียด</label>
    <textarea class="form-control" name="description" rows="3" placeholder="รายละเอียดเพิ่มเติม..."><?= esc($task->description ?? '') ?></textarea>
  </div>

  <?php
    // Determine which project_id to pre-select:
    // - editing: use saved project_id
    // - new task: use default_project_id (current month's งานรายวัน)
    $preselect_project = $tid
        ? ($task->project_id ?? 0)
        : ($default_project_id ?? 0);
  ?>
  <div class="form-group">
    <label class="form-label">โปรเจกต์</label>
    <?php if (!empty($default_project_id) && !$tid): ?>
    <div class="default-project-hint">
      📌 ค่าเริ่มต้น: โปรเจกต์งานรายวันของเดือนนี้
    </div>
    <?php endif; ?>
    <div class="custom-dropdown">
      <div class="dropdown-trigger">
        <span id="project-label">
          <?php
            $lbl = '— ไม่ระบุโปรเจกต์ —';
            foreach ($projects as $p) {
                if ($preselect_project && $preselect_project == $p->id) {
                    $lbl = esc($p->title); break;
                }
            }
            echo $lbl;
          ?>
        </span>
        <span class="chev">▾</span>
        <select name="project_id" class="dropdown-native-select" id="project-select" onchange="document.getElementById('project-label').textContent=this.options[this.selectedIndex].text">
          <option value="">— ไม่ระบุโปรเจกต์ —</option>
          <?php foreach ($projects as $p): ?>
          <option value="<?= $p->id ?>" <?= ($preselect_project == $p->id) ? 'selected' : '' ?>><?= esc($p->title) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <div class="form-group">
    <label class="form-label">มอบหมายให้</label>
    <div class="custom-dropdown">
      <div class="dropdown-trigger">
        <span id="assignee-label">
          <?php
            $lbl = '—';
            foreach ($users as $u) { if (($task->assigned_to ?? $login_user->id) == $u->id) { $lbl = esc(trim($u->first_name . ' ' . $u->last_name)); break; } }
            echo $lbl;
          ?>
        </span>
        <span class="chev">▾</span>
        <select name="assigned_to" class="dropdown-native-select" id="assignee-select" onchange="document.getElementById('assignee-label').textContent=this.options[this.selectedIndex].text">
          <?php foreach ($users as $u): ?>
          <option value="<?= $u->id ?>" <?= ($task->assigned_to ?? $login_user->id) == $u->id ? 'selected' : '' ?>><?= esc(trim($u->first_name . ' ' . $u->last_name)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <div class="d-flex gap-8">
    <div class="form-group flex-1">
      <label class="form-label">วันเริ่ม</label>
      <input class="form-control" type="date" name="start_date" value="<?= esc($val_start_date) ?>" oninput="updateNotifyPreview()">
    </div>
    <div class="form-group flex-1">
      <label class="form-label">เวลาเริ่ม</label>
      <input class="form-control" type="time" name="start_time" value="<?= esc($val_start_time) ?>" oninput="updateNotifyPreview()">
    </div>
  </div>

  <div class="d-flex gap-8">
    <div class="form-group flex-1">
      <label class="form-label">วันสิ้นสุด</label>
      <input class="form-control" type="date" name="deadline" value="<?= esc($val_deadline) ?>" oninput="updateNotifyPreview()">
    </div>
    <div class="form-group flex-1">
      <label class="form-label">เวลาสิ้นสุด</label>
      <input class="form-control" type="time" name="end_time" value="<?= esc($val_end_time) ?>" oninput="updateNotifyPreview()">
    </div>
  </div>

  <div class="d-flex gap-8">
    <div class="form-group flex-1">
      <label class="form-label">ลำดับความสำคัญ</label>
      <div class="custom-dropdown">
        <div class="dropdown-trigger">
          <span id="priority-label">
            <?php
              $lbl = '— เลือก —';
              foreach ($priorities as $p) { if (($task->priority_id ?? 0) == $p->id) { $lbl = esc($p->title); break; } }
              echo $lbl;
            ?>
          </span>
          <span class="chev">▾</span>
          <select name="priority_id" class="dropdown-native-select" id="priority-select" onchange="document.getElementById('priority-label').textContent=this.options[this.selectedIndex].text">
            <option value="">— เลือก —</option>
            <?php foreach ($priorities as $p): ?>
            <option value="<?= $p->id ?>" <?= ($task->priority_id ?? 0) == $p->id ? 'selected' : '' ?>><?= esc($p->title) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
    <div class="form-group flex-1">
      <label class="form-label">สถานะ</label>
      <select class="form-control" name="status_id">
        <?php foreach ($statuses as $s): ?>
        <option value="<?= $s->id ?>" <?= $val_status_id == $s->id ? 'selected' : '' ?>><?= esc($s->title) ?></option>
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
          <?= !empty($task->line_notify_enabled) ? 'checked' : '' ?>
          onchange="onNotifyToggle(this)">
        <span class="toggle-slider"></span>
      </label>
    </div>
    <div class="notify-section <?= !empty($task->line_notify_enabled) ? 'open' : '' ?>" id="notify-section">
      <div class="notify-row">
        <label>แจ้งเตือนก่อนเวลาเริ่ม</label>
        <input type="number" name="line_notify_before_start" min="0" max="1440"
          value="<?= esc($task->line_notify_before_start ?? '') ?>" placeholder="—" oninput="updateNotifyPreview()">
        <span>นาที</span>
      </div>
      <div class="notify-row">
        <label>แจ้งเตือนก่อนเวลาสิ้นสุด</label>
        <input type="number" name="line_notify_before_end" min="0" max="1440"
          value="<?= esc($task->line_notify_before_end ?? '') ?>" placeholder="—" oninput="updateNotifyPreview()">
        <span>นาที</span>
      </div>
      <div class="notify-row">
        <label>แจ้งหากไม่มีการอัปเดต</label>
        <input type="number" name="line_notify_no_update_hours" min="1" max="720"
          value="<?= esc($task->line_notify_no_update_hours ?? '') ?>" placeholder="—" oninput="updateNotifyPreview()">
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
/* Toggle LINE notify section open/closed */
function onNotifyToggle(cb) {
  const section = document.getElementById('notify-section');
  if (cb.checked) {
    section.classList.add('open');
    // Pre-fill system defaults if fields are empty
    const html  = document.documentElement;
    const defs  = {
      before_start: parseInt(html.dataset.defaultStart  || '30'),
      before_end:   parseInt(html.dataset.defaultEnd    || '60'),
      no_update:    parseInt(html.dataset.defaultUpdate || '24'),
    };
    const f1 = document.querySelector('[name="line_notify_before_start"]');
    const f2 = document.querySelector('[name="line_notify_before_end"]');
    const f3 = document.querySelector('[name="line_notify_no_update_hours"]');
    if (f1 && !f1.value) f1.value = defs.before_start;
    if (f2 && !f2.value) f2.value = defs.before_end;
    if (f3 && !f3.value) f3.value = defs.no_update;
    updateNotifyPreview();
  } else {
    section.classList.remove('open');
  }
}

function initTaskForm() {
  if (!window.LiffApp) {
    setTimeout(initTaskForm, 50);
    return;
  }
  LiffApp.initImageUpload('img-input', 'img-previews');
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
