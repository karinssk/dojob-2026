<div class="page-header">
  <h1><?= esc($page_title) ?></h1>
</div>

<div id="form-alert"></div>

<form id="event-form" onsubmit="submitEvent(event)">
  <?php $eid = $event->id ?? 0; ?>
  <input type="hidden" name="id" value="<?= $eid ?>">

  <div class="form-group">
    <label class="form-label">ชื่อ Event *</label>
    <input class="form-control" name="title" required placeholder="ชื่อ Event..." value="<?= esc($event->title ?? '') ?>">
  </div>

  <div class="form-group">
    <label class="form-label">รายละเอียด</label>
    <textarea class="form-control" name="description" rows="3" placeholder="รายละเอียด..."><?= esc($event->description ?? '') ?></textarea>
  </div>

  <div class="d-flex gap-8">
    <div class="form-group flex-1">
      <label class="form-label">วันเริ่ม *</label>
      <input class="form-control" type="date" name="start_date" required value="<?= esc(($event->start_date ?? '') ? date('Y-m-d', strtotime($event->start_date)) : date('Y-m-d')) ?>">
    </div>
    <div class="form-group flex-1">
      <label class="form-label">เวลาเริ่ม</label>
      <input class="form-control" type="time" name="start_time" value="<?= esc($event->start_time ?? '') ?>">
    </div>
  </div>

  <div class="d-flex gap-8">
    <div class="form-group flex-1">
      <label class="form-label">วันสิ้นสุด</label>
      <input class="form-control" type="date" name="end_date" value="<?= esc(($event->end_date ?? '') ? date('Y-m-d', strtotime($event->end_date)) : '') ?>">
    </div>
    <div class="form-group flex-1">
      <label class="form-label">เวลาสิ้นสุด</label>
      <input class="form-control" type="time" name="end_time" value="<?= esc($event->end_time ?? '') ?>">
    </div>
  </div>

  <div class="form-group">
    <label class="form-label">สี</label>
    <div class="d-flex gap-8" style="flex-wrap:wrap">
      <?php $colors = ['#6C8EF5','#6FCBA3','#FFA96A','#F97FA3','#A78BFA','#FBBF24']; ?>
      <?php foreach ($colors as $c): ?>
      <label style="cursor:pointer">
        <input type="radio" name="color" value="<?= $c ?>" style="display:none"
          <?= ($event->color ?? '#6C8EF5') === $c ? 'checked' : '' ?>>
        <div style="width:32px;height:32px;border-radius:50%;background:<?= $c ?>;
          border:3px solid <?= ($event->color ?? '#6C8EF5') === $c ? '#1E293B' : 'transparent' ?>"
          onclick="selectColor('<?= $c ?>', this)"></div>
      </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="form-group">
    <label class="form-label">แชร์กับ</label>
    <select class="form-control" name="share_with">
      <option value="only_me" <?= ($event->share_with ?? '') === 'only_me' ? 'selected' : '' ?>>เฉพาะฉัน</option>
      <option value="all_team_members" <?= ($event->share_with ?? '') === 'all_team_members' ? 'selected' : '' ?>>ทีมทั้งหมด</option>
    </select>
  </div>

  <!-- LINE Notification (optional) -->
  <div class="form-group">
    <div class="toggle-wrap">
      <div>
        <div class="toggle-label">LINE แจ้งเตือน</div>
        <div class="toggle-sub">ไม่บังคับ — ปล่อยว่างเพื่อปิด</div>
      </div>
      <label class="toggle">
        <input type="checkbox" id="notify-toggle" name="line_notify_enabled" value="1"
          <?= !empty($event->line_notify_enabled) ? 'checked' : '' ?>>
        <span class="toggle-slider"></span>
      </label>
    </div>
    <div class="notify-section <?= !empty($event->line_notify_enabled) ? 'open' : '' ?>" id="notify-section">
      <div class="notify-row">
        <label>แจ้งเตือนก่อนเวลาเริ่ม</label>
        <input type="number" name="line_notify_before_start" min="0" max="1440"
          value="<?= esc($event->line_notify_before_start ?? '') ?>" placeholder="—">
        <span>นาที</span>
      </div>
      <div class="notify-row">
        <label>แจ้งเตือนก่อนสิ้นสุด</label>
        <input type="number" name="line_notify_before_end" min="0" max="1440"
          value="<?= esc($event->line_notify_before_end ?? '') ?>" placeholder="—">
        <span>นาที</span>
      </div>
      <div class="notify-row">
        <label>แจ้งหากไม่มีการอัปเดต</label>
        <input type="number" name="line_notify_no_update_hours" min="1" max="720"
          value="<?= esc($event->line_notify_no_update_hours ?? '') ?>" placeholder="—">
        <span>ชั่วโมง</span>
      </div>
      <p class="text-xs text-muted mt-8">ปล่อยว่างไว้ = ปิดการแจ้งเตือนนั้น</p>
    </div>
  </div>

  <button type="submit" class="btn btn-primary btn-block btn-lg" id="submit-btn">บันทึก</button>
</form>

<script>
LiffApp.initNotifyToggle('notify-toggle', 'notify-section');

function selectColor(c, el) {
  document.querySelectorAll('[name=color]').forEach(r => r.checked = r.value === c);
  document.querySelectorAll('[onclick*=selectColor]').forEach(d => d.style.border = '3px solid transparent');
  el.style.border = '3px solid #1E293B';
}

async function submitEvent(e) {
  e.preventDefault();
  const btn = document.getElementById('submit-btn');
  btn.textContent = 'กำลังบันทึก...'; btn.disabled = true;
  const form = new FormData(e.target);
  const params = {};
  for (const [k, v] of form.entries()) params[k] = v;
  const res = await LiffApp.api('liff/api/events/save', 'POST', params);
  if (res.success) {
    LiffApp.toast('บันทึกสำเร็จ', 'success');
    setTimeout(() => location.href = res.redirect, 800);
  } else {
    document.getElementById('form-alert').innerHTML = `<div class="alert alert-danger">${res.message}</div>`;
    btn.textContent = 'บันทึก'; btn.disabled = false;
  }
}
</script>
