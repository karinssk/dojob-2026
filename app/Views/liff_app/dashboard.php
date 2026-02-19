<div class="page-header">
  <h1>สวัสดี, <?= esc($login_user->first_name) ?></h1>
  <p>ภาพรวมวันนี้</p>
</div>

<!-- Summary cards -->
<div class="d-flex gap-8 mb-12" style="flex-wrap:wrap">
  <div class="card flex-1" style="min-width:140px">
    <div class="card-body text-center">
      <div style="font-size:28px;font-weight:700;color:var(--blue)"><?= $tasks_due_today ?></div>
      <div class="text-sm text-muted mt-4">Tasks วันนี้</div>
    </div>
  </div>
  <div class="card flex-1" style="min-width:140px">
    <div class="card-body text-center">
      <div style="font-size:28px;font-weight:700;color:var(--purple)"><?= $events_today ?></div>
      <div class="text-sm text-muted mt-4">Events วันนี้</div>
    </div>
  </div>
  <div class="card flex-1" style="min-width:140px">
    <div class="card-body text-center">
      <div style="font-size:28px;font-weight:700;color:var(--green)"><?= $todos_pending ?></div>
      <div class="text-sm text-muted mt-4">To-Do ค้างอยู่</div>
    </div>
  </div>
  <?php if ($overdue_tasks > 0): ?>
  <div class="card flex-1" style="min-width:140px;border:1.5px solid var(--pink-lt)">
    <div class="card-body text-center">
      <div style="font-size:28px;font-weight:700;color:var(--pink)"><?= $overdue_tasks ?></div>
      <div class="text-sm" style="color:var(--pink);margin-top:4px">เกินกำหนด</div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Recent tasks -->
<div class="section-title">งานที่รับผิดชอบ</div>

<?php if (empty($recent_tasks)): ?>
<div class="empty-state">
  <p>ยังไม่มีงาน</p>
</div>
<?php else: ?>
<?php foreach ($recent_tasks as $t): ?>
<?php
  $color = $t->status_color ?: '#6C8EF5';
  $bg    = $color . '22';
  $is_overdue = $t->deadline && strtotime($t->deadline) < time();
?>
<div class="task-card" onclick="location.href='<?= get_uri('liff/app/tasks/' . $t->id) ?>'">
  <div class="task-card-top">
    <?php if ($t->priority_title): ?>
    <span class="chip chip-orange"><?= esc($t->priority_title) ?></span>
    <?php endif; ?>
    <span class="chip" style="background:<?= $bg ?>;color:<?= $color ?>"><?= esc($t->status_title) ?></span>
    <?php if ($is_overdue): ?><span class="chip chip-pink">เกินกำหนด</span><?php endif; ?>
  </div>
  <div class="task-title"><?= esc($t->title) ?></div>
  <div class="task-meta">
    <?php if ($t->deadline): ?>
    <span class="task-meta-item"><?= date('j M', strtotime($t->deadline)) ?>
      <?php if ($t->end_time): ?> <?= date('H:i', strtotime($t->end_time)) ?><?php endif; ?>
    </span>
    <?php endif; ?>
    <?php if ($t->project_title): ?>
    <span class="task-meta-item"><?= esc($t->project_title) ?></span>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
<a class="btn btn-secondary btn-block mt-8" href="<?= get_uri('liff/app/tasks') ?>">ดู Tasks ทั้งหมด</a>
<?php endif; ?>
