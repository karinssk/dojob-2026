<div class="page-header d-flex" style="justify-content:space-between;align-items:center">
  <h1><?= esc($event->title) ?></h1>
  <a href="<?= get_uri('liff/app/events/' . $event->id . '/edit') ?>" class="btn btn-sm" style="padding:6px 14px">แก้ไข</a>
</div>

<div class="card" style="border-left:5px solid <?= esc($event->color ?? '#6C8EF5') ?>">
  <div class="d-flex gap-8" style="align-items:center;margin-bottom:12px">
    <div style="width:14px;height:14px;border-radius:50%;background:<?= esc($event->color ?? '#6C8EF5') ?>;flex-shrink:0"></div>
    <span class="chip" style="background:<?= esc($event->color ?? '#6C8EF5') ?>22;color:<?= esc($event->color ?? '#6C8EF5') ?>">
      <?= $event->share_with === 'all_team_members' ? 'ทีม' : 'เฉพาะฉัน' ?>
    </span>
  </div>

  <?php if ($event->description): ?>
  <p style="color:#475569;font-size:14px;line-height:1.6;margin-bottom:16px"><?= nl2br(esc($event->description)) ?></p>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
    <div>
      <div style="font-size:11px;color:#94A3B8;font-weight:600;text-transform:uppercase;margin-bottom:4px">เริ่ม</div>
      <div style="font-size:14px;font-weight:600;color:#1E293B">
        <?= $event->start_date ? date('d M Y', strtotime($event->start_date)) : '—' ?>
      </div>
      <?php if ($event->start_time): ?>
      <div style="font-size:13px;color:#64748B"><?= date('H:i', strtotime($event->start_time)) ?></div>
      <?php endif; ?>
    </div>
    <div>
      <div style="font-size:11px;color:#94A3B8;font-weight:600;text-transform:uppercase;margin-bottom:4px">สิ้นสุด</div>
      <div style="font-size:14px;font-weight:600;color:#1E293B">
        <?= $event->end_date ? date('d M Y', strtotime($event->end_date)) : '—' ?>
      </div>
      <?php if ($event->end_time): ?>
      <div style="font-size:13px;color:#64748B"><?= date('H:i', strtotime($event->end_time)) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($event->line_notify_enabled)): ?>
  <div style="margin-top:16px;padding-top:16px;border-top:1px solid #F1F5F9">
    <div style="font-size:12px;color:#94A3B8;font-weight:600;margin-bottom:8px">LINE แจ้งเตือน</div>
    <div style="display:flex;flex-wrap:wrap;gap:6px">
      <?php if ($event->line_notify_before_start !== null): ?>
      <span class="chip chip-blue">ก่อนเริ่ม <?= (int)$event->line_notify_before_start ?> นาที</span>
      <?php endif; ?>
      <?php if ($event->line_notify_before_end !== null): ?>
      <span class="chip chip-green">ก่อนสิ้นสุด <?= (int)$event->line_notify_before_end ?> นาที</span>
      <?php endif; ?>
      <?php if ($event->line_notify_no_update_hours !== null): ?>
      <span class="chip chip-orange">ไม่อัปเดต <?= (int)$event->line_notify_no_update_hours ?> ชม.</span>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <div style="margin-top:16px;padding-top:16px;border-top:1px solid #F1F5F9;font-size:12px;color:#94A3B8">
    สร้างโดย <?= esc($event->creator_name ?? 'ไม่ระบุ') ?>
  </div>
</div>

<div style="margin-top:16px">
  <a href="<?= get_uri('liff/app/events/' . $event->id . '/edit') ?>" class="btn btn-primary btn-block">แก้ไข Event</a>
</div>

<div style="margin-top:12px">
  <button class="btn btn-block" style="background:#FFF0F3;color:#C73060;border:none"
    onclick="deleteEvent(<?= $event->id ?>)">ลบ Event</button>
</div>

<script>
async function deleteEvent(id) {
  if (!confirm('ลบ Event นี้?')) return;
  const res = await LiffApp.api('liff/api/events/delete', 'POST', { id });
  if (res.success) {
    LiffApp.toast('ลบสำเร็จ', 'success');
    setTimeout(() => location.href = '<?= get_uri('liff/app/events') ?>', 500);
  } else {
    LiffApp.toast(res.message || 'เกิดข้อผิดพลาด', 'danger');
  }
}
</script>
