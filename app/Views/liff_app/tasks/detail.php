<?php
$color = $task->status_color ?: '#6C8EF5';
$bg    = $color . '22';
$is_overdue = $task->deadline && strtotime($task->deadline) < time();

// Pre-build full-size URLs for the image modal (comment attachments only)
$modalImgs = [];
foreach ($comments as $c) {
    $cfiles = $c->files ? @unserialize($c->files) : [];
    if (is_array($cfiles)) {
        foreach ($cfiles as $f) {
            if (is_array($f)) $modalImgs[] = get_source_url_of_file($f, get_setting('timeline_file_path'));
        }
    }
}
$modalIdx = 0;
?>
<div class="page-header page-header-row">
  <div>
    <h1 style="font-size:16px;line-height:1.4"><?= esc($task->title) ?></h1>
    <p><?= esc($task->project_title ?? 'ไม่ระบุโปรเจกต์') ?></p>
  </div>
  <a href="<?= get_uri('liff/app/tasks/' . $task->id . '/edit') ?>" class="btn btn-primary btn-sm edit-btn">อัพเดตงาน</a>
</div>

<!-- Status: current chip + horizontal scroll options -->
<div class="card">
  <div class="card-body">
    <div class="d-flex align-center justify-between" style="margin-bottom:10px">
      <span class="text-sm fw-600 text-muted">สถานะ</span>
      <span class="chip" style="background:<?= $bg ?>;color:<?= $color ?>" id="status-chip">
        <?= esc($task->status_title) ?>
      </span>
    </div>
    <div class="status-scroll">
      <?php foreach ($statuses as $s):
        $sc = $s->color ?: '#6C8EF5'; ?>
      <button class="chip" style="background:<?= $sc ?>22;color:<?= $sc ?>"
        onclick="LiffApp.updateTaskStatus(<?= $task->id ?>, <?= $s->id ?>, document.getElementById('status-chip'))">
        <?= esc($s->title) ?>
      </button>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Task detail + Images — single merged card -->
<div class="card">
  <div class="card-body">

    <div class="fw-700" style="font-size:16px;line-height:1.4;margin-bottom:<?= $task->description ? '10px' : '0' ?>"><?= esc($task->title) ?></div>

    <?php if ($task->description): ?>
    <p class="text-sm" style="color:var(--label);line-height:1.6"><?= nl2br(esc($task->description)) ?></p>
    <div class="divider"></div>
    <?php endif; ?>

    <div class="d-flex gap-12" style="flex-wrap:wrap">
      <?php if ($task->assigned_name): ?>
      <div>
        <div class="text-xs text-muted">ผู้รับผิดชอบ</div>
        <div class="d-flex align-center gap-8 mt-4">
          <div class="avatar avatar-sm">
            <?php if ($task->assigned_img): ?>
            <img src="<?= esc(get_avatar($task->assigned_img)) ?>" alt="">
            <?php else: ?>
            <?= mb_substr($task->assigned_name, 0, 1) ?>
            <?php endif; ?>
          </div>
          <span class="text-sm fw-600"><?= esc($task->assigned_name) ?></span>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($task->start_date || $task->deadline): ?>
      <div>
        <div class="text-xs text-muted">ช่วงเวลา</div>
        <div class="text-sm fw-600 mt-4">
          <?php if ($task->start_date): ?>
          <?= date('j M', strtotime($task->start_date)) ?>
          <?php if ($task->start_time): ?> <?= date('H:i', strtotime($task->start_time)) ?><?php endif; ?>
          <?php endif; ?>
          <?php if ($task->deadline): ?>
          – <?= date('j M', strtotime($task->deadline)) ?>
          <?php if ($task->end_time): ?> <?= date('H:i', strtotime($task->end_time)) ?><?php endif; ?>
          <?php if ($is_overdue): ?> <span class="chip chip-pink" style="font-size:10px">เกินกำหนด</span><?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($task->priority_title): ?>
    <div class="mt-8">
      <span class="text-xs text-muted">Priority: </span>
      <span class="chip chip-orange"><?= esc($task->priority_title) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($task->line_notify_enabled): ?>
    <div class="mt-8" style="background:var(--blue-lt);border-radius:10px;padding:10px 12px">
      <span style="font-size:12px;color:var(--blue)">
        LINE แจ้งเตือน: เปิด
        <?php if ($task->line_notify_before_start): ?> | ก่อนเริ่ม <?= $task->line_notify_before_start ?> นาที<?php endif; ?>
        <?php if ($task->line_notify_before_end): ?> | ก่อนสิ้นสุด <?= $task->line_notify_before_end ?> นาที<?php endif; ?>
        <?php if ($task->line_notify_no_update_hours): ?> | ไม่มีอัปเดต <?= $task->line_notify_no_update_hours ?> ชม.<?php endif; ?>
      </span>
    </div>
    <?php endif; ?>

  </div>
</div>

<!-- Comments + form — single card -->
<div class="card">
  <div class="card-header">
    <h3>ความคิดเห็น</h3>
    <?php if (!empty($comments)): ?>
    <span class="chip chip-gray"><?= count($comments) ?></span>
    <?php endif; ?>
  </div>

  <?php if (!empty($comments)): ?>
  <div class="card-body" style="padding-bottom:0">
    <div class="comment-list">
      <?php foreach ($comments as $c):
        $avatar = get_avatar($c->created_by_avatar ?? '');
        $files  = $c->files ? @unserialize($c->files) : [];
        if (!is_array($files)) { $files = []; }
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
          <?php if (!empty($files)): ?>
          <div class="comment-attachments">
            <?php foreach ($files as $file):
              if (!is_array($file)) { continue; }
              $thumb = get_source_url_of_file($file, get_setting("timeline_file_path"), "thumbnail");
            ?>
            <img src="<?= esc($thumb) ?>" alt="" onclick="openImgModal(<?= $modalIdx++ ?>)">
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="divider" style="margin:0 16px"></div>
  <?php endif; ?>

  <div class="card-body" style="padding-top:12px">
    <?php if (empty($comments)): ?>
    <p class="text-sm text-muted" style="margin-bottom:12px">ยังไม่มีความคิดเห็น เป็นคนแรกที่แสดงความเห็น</p>
    <?php endif; ?>
    <form id="task-comment-form" onsubmit="submitTaskComment(event)">
      <textarea class="form-control" name="description" rows="2" placeholder="เขียนความคิดเห็น..." style="margin-bottom:8px"></textarea>
      <input type="hidden" name="task_id" value="<?= (int)$task->id ?>">
      <input type="hidden" name="project_id" value="<?= (int)($task->project_id ?? 0) ?>">
      <div class="comment-actions">
        <label class="comment-attach">
          แนบรูป
          <input type="file" id="task-comment-images" name="manualFiles[]" accept="image/*" multiple hidden>
        </label>
        <button type="submit" class="btn btn-primary btn-sm">ส่ง</button>
      </div>
      <div class="upload-previews" id="task-comment-previews"></div>
    </form>
  </div>
</div>

<!-- Activity feed -->
<?php if (!empty($activity)): ?>
<div class="section-title">ประวัติการอัปเดต</div>
<div class="card">
  <div class="card-body">
    <?php foreach ($activity as $a): ?>
    <div class="activity-item">
      <div class="activity-dot"></div>
      <div class="activity-content">
        <div class="activity-text">
          <strong><?= esc($a->user_name ?? 'System') ?></strong>
          <?= esc($a->log_type ?? '') ?>
        </div>
        <div class="activity-time"><?= date('j M Y H:i', strtotime($a->created_at ?? 'now')) ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<a class="btn btn-primary btn-block" href="<?= get_uri('liff/app/tasks/' . $task->id . '/edit') ?>">อัพเดตงาน</a>

<!-- Image modal -->
<div id="img-modal" class="img-modal" onclick="closeImgModal()">
  <button class="img-modal-close" onclick="closeImgModal()">✕</button>
  <button class="img-modal-prev" id="img-modal-prev" onclick="event.stopPropagation();imgModalNav(-1)">‹</button>
  <img id="img-modal-img" class="img-modal-img" src="" alt="" onclick="event.stopPropagation()">
  <button class="img-modal-next" id="img-modal-next" onclick="event.stopPropagation();imgModalNav(1)">›</button>
  <div class="img-modal-counter" id="img-modal-counter"></div>
</div>

<script>
/* ── Image modal ── */
const _imgs = <?= json_encode($modalImgs) ?>;
let _imgIdx = 0;

function openImgModal(idx) {
  _imgIdx = idx;
  _updateModal();
  document.getElementById('img-modal').classList.add('open');
}
function closeImgModal() {
  document.getElementById('img-modal').classList.remove('open');
}
function imgModalNav(dir) {
  _imgIdx = (_imgIdx + dir + _imgs.length) % _imgs.length;
  _updateModal();
}
function _updateModal() {
  document.getElementById('img-modal-img').src = _imgs[_imgIdx];
  document.getElementById('img-modal-counter').textContent = _imgs.length > 1 ? `${_imgIdx + 1} / ${_imgs.length}` : '';
  document.getElementById('img-modal-prev').hidden = _imgs.length < 2;
  document.getElementById('img-modal-next').hidden = _imgs.length < 2;
}

// Swipe left/right to navigate
(function() {
  let tx = 0;
  const el = document.getElementById('img-modal');
  el.addEventListener('touchstart', e => { tx = e.touches[0].clientX; }, { passive: true });
  el.addEventListener('touchend', e => {
    const dx = e.changedTouches[0].clientX - tx;
    if (Math.abs(dx) > 50) imgModalNav(dx < 0 ? 1 : -1);
  });
})();

/* ── Init image upload (wait for LiffApp) ── */
(function waitForLiffApp() {
  if (!window.LiffApp) { setTimeout(waitForLiffApp, 50); return; }
  LiffApp.initImageUpload('task-comment-images', 'task-comment-previews');
})();

/* ── Comment submit ── */
async function submitTaskComment(e) {
  e.preventDefault();
  const form = new FormData(e.target);
  const btn = e.target.querySelector('button[type="submit"]');
  btn.disabled = true;
  const res = await LiffApp.api('liff/api/tasks/comment_save', 'POST', form);
  if (res.success) {
    location.reload();
  } else {
    LiffApp.toast(res.message || 'เกิดข้อผิดพลาด', 'error');
    btn.disabled = false;
  }
}
</script>
