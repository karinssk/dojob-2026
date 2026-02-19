<div class="page-header">
  <h1>Tasks</h1>
</div>

<div class="tabs" data-tabs style="margin-top:0">
  <button class="tab-btn <?= $filter==='mine' ? 'active':'' ?>" data-tab="mine" onclick="location.href='<?= get_uri('liff/app/tasks?filter=mine') ?>'">งานของฉัน</button>
  <button class="tab-btn <?= $filter==='assigned_by_me' ? 'active':'' ?>" data-tab="assigned_by_me" onclick="location.href='<?= get_uri('liff/app/tasks?filter=assigned_by_me') ?>'">มอบหมาย</button>
</div>

<?php if (empty($tasks)): ?>
<div class="empty-state">
  <p>ยังไม่มีงาน<br>กด + เพื่อสร้างงานใหม่</p>
</div>
<?php else: ?>
<?php foreach ($tasks as $t):
  $color = $t->status_color ?: '#6C8EF5';
  $bg    = $color . '22';
  $is_overdue = $t->deadline && strtotime($t->deadline) < time();
?>
<div class="task-card" onclick="location.href='<?= get_uri('liff/app/tasks/' . $t->id) ?>'">
  <div class="task-card-top">
    <?php if ($t->priority_title): ?><span class="chip chip-orange"><?= esc($t->priority_title) ?></span><?php endif; ?>
    <span class="chip" style="background:<?= $bg ?>;color:<?= $color ?>"><?= esc($t->status_title) ?></span>
    <?php if ($is_overdue): ?><span class="chip chip-pink">เกินกำหนด</span><?php endif; ?>
    <?php if ($t->line_notify_enabled): ?><span class="chip chip-blue">LINE แจ้งเตือน</span><?php endif; ?>
  </div>
  <div class="task-title"><?= esc($t->title) ?></div>
  <div class="task-meta">
    <?php if ($t->start_date): ?>
    <span class="task-meta-item">
      <?= date('j M', strtotime($t->start_date)) ?>
      <?php if ($t->start_time): ?><?= date('H:i', strtotime($t->start_time)) ?><?php endif; ?>
      <?php if ($t->deadline): ?> - <?= date('j M', strtotime($t->deadline)) ?>
        <?php if ($t->end_time): ?> <?= date('H:i', strtotime($t->end_time)) ?><?php endif; ?>
      <?php endif; ?>
    </span>
    <?php endif; ?>
    <?php if ($t->assigned_name && $filter === 'assigned_by_me'): ?>
    <span class="task-meta-item"><?= esc($t->assigned_name) ?></span>
    <?php endif; ?>
    <?php if ($t->project_title): ?>
    <span class="task-meta-item"><?= esc($t->project_title) ?></span>
    <?php endif; ?>
  </div>
  <?php if (!empty($t->all_comment_files_array)): ?>
  <div class="task-images">
    <?php foreach (array_slice($t->all_comment_files_array, 0, 3) as $file): ?>
      <?php $thumb = get_source_url_of_file($file, get_setting("timeline_file_path"), "thumbnail"); ?>
      <img class="task-thumb" src="<?= esc($thumb) ?>" alt="" onerror="this.style.display='none'">
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>
