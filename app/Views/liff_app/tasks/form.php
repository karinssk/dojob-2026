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
    <select class="form-control" name="project_id">
      <option value="">— ไม่ระบุโปรเจกต์ —</option>
      <?php foreach ($projects as $p): ?>
      <option value="<?= $p->id ?>" <?= ($task->project_id ?? 0) == $p->id ? 'selected' : '' ?>><?= esc($p->title) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-group">
    <label class="form-label">มอบหมายให้</label>
    <select class="form-control" name="assigned_to">
      <?php foreach ($users as $u): ?>
      <option value="<?= $u->id ?>" <?= ($task->assigned_to ?? $login_user->id) == $u->id ? 'selected' : '' ?>>
        <?= esc(trim($u->first_name . ' ' . $u->last_name)) ?>
      </option>
      <?php endforeach; ?>
    </select>
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
      <select class="form-control" name="priority_id">
        <option value="">— เลือก —</option>
        <?php foreach ($priorities as $p): ?>
        <option value="<?= $p->id ?>" <?= ($task->priority_id ?? 0) == $p->id ? 'selected' : '' ?>><?= esc($p->title) ?></option>
        <?php endforeach; ?>
      </select>
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
      <div style="font-size:28px">📷</div>
      <p>แตะเพื่ออัปโหลดรูปภาพ</p>
    </div>
    <input type="file" id="img-input" name="images[]" accept="image/*" multiple hidden>
    <div class="upload-previews" id="img-previews"></div>
  </div>

  <!-- LINE Notification (optional) -->
  <div class="form-group">
    <div class="toggle-wrap">
      <div>
        <div class="toggle-label">🔔 LINE แจ้งเตือน</div>
        <div class="toggle-sub">ไม่บังคับ — เปิดเพื่อตั้งค่าการแจ้งเตือน</div>
      </div>
      <label class="toggle">
        <input type="checkbox" id="notify-toggle" name="line_notify_enabled" value="1"
          <?= !empty($task->line_notify_enabled) ? 'checked' : '' ?> onchange="LiffApp.initNotifyToggle('notify-toggle','notify-section')">
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

  <button type="submit" class="btn btn-primary btn-block btn-lg" id="submit-btn">
    💾 บันทึก
  </button>
</form>

<script>
LiffApp.initImageUpload('img-input', 'img-previews');
// init notify section if already checked
if (document.getElementById('notify-toggle').checked) {
  document.getElementById('notify-section').classList.add('open');
}

async function submitTask(e) {
  e.preventDefault();
  const btn = document.getElementById('submit-btn');
  btn.textContent = '⏳ กำลังบันทึก...';
  btn.disabled = true;

  const form = new FormData(e.target);
  // Convert FormData to URLSearchParams (files handled separately)
  const params = {};
  for (const [k, v] of form.entries()) {
    if (!(v instanceof File)) params[k] = v;
  }

  const res = await LiffApp.api('liff/api/tasks/save', 'POST', params);
  if (res.success) {
    LiffApp.toast('บันทึกสำเร็จ', 'success');
    setTimeout(() => location.href = res.redirect, 800);
  } else {
    document.getElementById('form-alert').innerHTML =
      `<div class="alert alert-danger">${res.message}</div>`;
    btn.textContent = '💾 บันทึก';
    btn.disabled = false;
  }
}
</script>
