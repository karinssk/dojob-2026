<?php
$color = $task->status_color ?: '#6C8EF5';
$bg    = $color . '22';
$is_overdue = $task->deadline && strtotime($task->deadline) < time();
?>
<div class="page-header page-header-row">
  <div>
    <h1 style="font-size:16px;line-height:1.4"><?= esc($task->title) ?></h1>
    <p><?= esc($task->project_title ?? 'ไม่ระบุโปรเจกต์') ?></p>
  </div>
  <a href="<?= get_uri('liff/app/tasks/' . $task->id . '/edit') ?>" class="btn btn-primary btn-sm edit-btn">แก้ไข</a>
</div>

<!-- Status quick-update -->
<div class="card">
  <div class="card-body">
    <div class="d-flex align-center justify-between mb-8">
      <span class="text-sm fw-600 text-muted">สถานะ</span>
      <span class="chip" style="background:<?= $bg ?>;color:<?= $color ?>" id="status-chip">
        <?= esc($task->status_title) ?>
      </span>
    </div>
    <div class="d-flex gap-8" style="flex-wrap:wrap">
      <?php foreach ($statuses as $s):
        $sc = $s->color ?: '#6C8EF5'; ?>
      <button class="chip" style="background:<?= $sc ?>22;color:<?= $sc ?>;cursor:pointer;border:none"
        onclick="LiffApp.updateTaskStatus(<?= $task->id ?>, <?= $s->id ?>, document.getElementById('status-chip'))">
        <?= esc($s->title) ?>
      </button>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Task info -->
<div class="card">
  <div class="card-body">
    <?php if ($task->description): ?>
    <p class="text-sm" style="color:var(--label);line-height:1.6;margin-bottom:12px"><?= nl2br(esc($task->description)) ?></p>
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
          - <?= date('j M', strtotime($task->deadline)) ?>
          <?php if ($task->end_time): ?> <?= date('H:i', strtotime($task->end_time)) ?><?php endif; ?>
          <?php if ($is_overdue): ?> <span class="chip chip-pink">เกินกำหนด</span><?php endif; ?>
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

<!-- Images -->
<?php if (!empty($comment_files)): ?>
<div class="section-title">รูปภาพ</div>
<div class="card">
  <div class="card-body">
    <div style="display:flex;flex-wrap:wrap;gap:8px">
      <?php foreach ($comment_files as $file): ?>
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
          $files = $c->files ? @unserialize($c->files) : [];
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
            <?php foreach ($files as $file): ?>
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
    <form id="task-comment-form" onsubmit="submitTaskComment(event)">
      <textarea class="form-control" name="description" rows="3" placeholder="เขียนความคิดเห็น..."></textarea>
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

<a class="btn btn-primary btn-block" href="<?= get_uri('liff/app/tasks/' . $task->id . '/edit') ?>">แก้ไขงาน</a>

<script>
LiffApp.initImageUpload('task-comment-images', 'task-comment-previews');
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
