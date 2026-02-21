<?php
$login_uid = (int)($login_user_id ?? 0);
$confirmed_ids = array_filter(array_map('trim', explode(',', (string)($event->confirmed_by ?? ''))));
$is_confirmed = $login_uid && in_array((string)$login_uid, $confirmed_ids, true);
$end_date = $event->end_date ?: $event->start_date;
$end_time = $event->end_time ?: '23:59:59';
$end_ts = $end_date ? strtotime($end_date . ' ' . $end_time) : null;
$is_past = $end_ts ? ($end_ts < time()) : false;
?>

<div class="page-header page-header-row">
  <h1 style="font-size:17px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0;flex:1"><?= esc($event->title) ?></h1>
  <a href="<?= get_uri('liff/app/events/' . $event->id . '/edit') ?>" class="btn btn-primary btn-sm edit-btn" style="flex-shrink:0;margin-left:10px">แก้ไข</a>
</div>

<div class="card" style="border-left:5px solid <?= esc($event->color ?? '#6C8EF5') ?>">
  <div class="card-body">
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

    <div style="margin-top:16px;padding-top:16px;border-top:1px solid #F1F5F9">
      <div style="font-size:12px;color:#94A3B8;font-weight:600;margin-bottom:8px">สถานะกิจกรรม</div>
      <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px">
        <span class="chip" style="background:#E2E8F0;color:#475569">
          <?= $is_past ? 'ผ่านแล้ว' : 'กำลังจะถึง' ?>
        </span>
        <span class="chip" style="background:<?= $is_confirmed ? '#DCFCE7' : '#F1F5F9' ?>;color:<?= $is_confirmed ? '#166534' : '#64748B' ?>">
          <?= $is_confirmed ? 'ยืนยันแล้ว' : 'ยังไม่ยืนยัน' ?>
        </span>
      </div>

    <?php if ($is_confirmed): ?>
      <button class="btn btn-success btn-block" disabled>ยืนยันแล้ว</button>
    <?php elseif ($is_past): ?>
      <button class="btn btn-success btn-block" onclick="confirmEventDone(<?= (int)$event->id ?>)">ยืนยันกิจกรรมเสร็จแล้ว</button>
    <?php else: ?>
      <button class="btn btn-secondary btn-block" disabled>ยังไม่ถึงเวลา</button>
    <?php endif; ?>
  </div>

    <div style="margin-top:16px;padding-top:16px;border-top:1px solid #F1F5F9;font-size:12px;color:#94A3B8">
      สร้างโดย <?= esc($event->creator_name ?? 'ไม่ระบุ') ?>
    </div>
  </div>
</div>

<?php
$files = [];
if (!empty($event->files)) {
  $files = @unserialize($event->files);
  if (!is_array($files)) { $files = []; }
}
?>
<?php if (!empty($files)): ?>
<div class="section-title">ไฟล์แนบ</div>
<div class="card">
  <div class="card-body">
    <div style="display:flex;flex-wrap:wrap;gap:8px">
      <?php foreach ($files as $file): ?>
        <?php
          $thumb = get_source_url_of_file($file, get_setting("timeline_file_path"), "thumbnail");
          $url   = get_source_url_of_file($file, get_setting("timeline_file_path"));
        ?>
      <a href="<?= esc($url) ?>" target="_blank">
        <img src="<?= esc($thumb) ?>" style="width:72px;height:72px;border-radius:10px;object-fit:cover" alt="">
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Comments -->
<div class="section-title">ความคิดเห็น</div>
<?php if (empty($comments)): ?>
<div class="card">
  <div class="card-body text-sm text-muted">ยังไม่มีความคิดเห็น</div>
</div>
<?php else: ?>
<div class="card">
  <div class="card-body">
    <div class="comment-list">
      <?php foreach ($comments as $c): ?>
        <?php
          $avatar = get_avatar($c->created_by_avatar ?? '');
          $cfiles = $c->files ? @unserialize($c->files) : [];
          if (!is_array($cfiles)) { $cfiles = []; }
        ?>
      <div class="comment-item">
        <img src="<?= esc($avatar) ?>" class="comment-avatar" alt="">
        <div class="comment-body">
          <div class="comment-meta">
            <span class="comment-name"><?= esc($c->created_by_user ?? 'User') ?></span>
            <span class="comment-time"><?= date('d M H:i', strtotime($c->created_at ?? 'now')) ?></span>
          </div>
          <?php if (!empty($c->description)): ?>
          <div class="comment-text"><?= nl2br(esc($c->description)) ?></div>
          <?php endif; ?>
          <?php if (!empty($cfiles)): ?>
          <div class="comment-attachments">
            <?php foreach ($cfiles as $file): ?>
              <?php
                if (!is_array($file)) { continue; }
                $thumb = get_source_url_of_file($file, get_setting("timeline_file_path"), "thumbnail");
                $url   = get_source_url_of_file($file, get_setting("timeline_file_path"));
              ?>
              <a href="<?= esc($url) ?>" target="_blank"><img src="<?= esc($thumb) ?>" alt=""></a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="card comment-form-card">
  <div class="card-body">
    <form id="event-comment-form" onsubmit="submitEventComment(event)">
      <textarea class="form-control" name="description" rows="3" placeholder="เขียนความคิดเห็น..."></textarea>
      <input type="hidden" name="event_id" value="<?= (int)$event->id ?>">
      <div class="comment-actions">
        <label class="comment-attach">
          แนบรูป
          <input type="file" id="event-comment-images" name="manualFiles[]" accept="image/*" multiple hidden>
        </label>
        <button type="submit" class="btn btn-primary btn-sm">ส่ง</button>
      </div>
      <div class="upload-previews" id="event-comment-previews"></div>
    </form>
  </div>
</div>

<div style="margin-top:16px;display:flex;flex-direction:column;gap:10px">
  <a href="<?= get_uri('liff/app/events/' . $event->id . '/edit') ?>" class="btn btn-primary btn-block">แก้ไข Event</a>
  <button class="btn btn-block" style="background:#FFF0F3;color:#C73060;border:none"
    onclick="deleteEvent(<?= $event->id ?>)">ลบ Event</button>
</div>

<script>
LiffApp.initImageUpload('event-comment-images', 'event-comment-previews');
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

async function submitEventComment(e) {
  e.preventDefault();
  const form = new FormData(e.target);
  const btn = e.target.querySelector('button[type="submit"]');
  btn.disabled = true;
  const res = await LiffApp.api('liff/api/events/comment_save', 'POST', form);
  if (res.success) {
    location.reload();
  } else {
    LiffApp.toast(res.message || 'เกิดข้อผิดพลาด', 'error');
    btn.disabled = false;
  }
}

async function confirmEventDone(id) {
  const res = await LiffApp.api('liff/api/events/confirm', 'POST', { event_id: id });
  if (res.success) {
    LiffApp.toast('ยืนยันแล้ว', 'success');
    setTimeout(() => location.reload(), 400);
  } else {
    LiffApp.toast(res.message || 'ยืนยันไม่สำเร็จ', 'danger');
  }
}
</script>
