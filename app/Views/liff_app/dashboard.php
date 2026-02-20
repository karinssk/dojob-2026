<?php
$today  = date('j M Y');
$avatar = get_avatar($login_user->image);
?>

<!-- ── Hero gradient header ── -->
<div class="dash-hero">
  <div class="dash-hero-top">
    <a href="<?= get_uri('liff/app/profile') ?>" class="dash-hero-user">
      <?php if ($login_user->image): ?>
      <img src="<?= esc($avatar) ?>" alt="" class="dash-hero-avatar">
      <?php else: ?>
      <div class="dash-hero-avatar dash-hero-initials"><?= mb_substr($login_user->first_name, 0, 1) ?></div>
      <?php endif; ?>
      <div>
        <div class="dash-hero-greeting">สวัสดี, <?= esc($login_user->first_name) ?> 👋</div>
        <div class="dash-hero-date"><?= $today ?></div>
      </div>
    </a>
  </div>

  <div class="dash-hero-main">
    <div class="dash-hero-num"><?= $pending_tasks ?></div>
    <div class="dash-hero-label">งานที่ยังไม่เสร็จ</div>
  </div>
</div>

<!-- ── White body overlapping hero ── -->
<div class="dash-body">

  <!-- Quick stat cards -->
  <div class="dash-stats-scroll">
    <div class="dash-stat-card">
      <div class="dash-stat-icon" style="background:var(--blue-lt);color:var(--blue)">
        <svg viewBox="0 0 24 24" fill="none" width="18" height="18"><path d="M9 12l2 2 4-4M7 4h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="dash-stat-num" style="color:var(--blue)"><?= $tasks_due_today ?></div>
      <div class="dash-stat-label">Tasks วันนี้</div>
    </div>
    <div class="dash-stat-card">
      <div class="dash-stat-icon" style="background:var(--purple-lt);color:var(--purple)">
        <svg viewBox="0 0 24 24" fill="none" width="18" height="18"><path d="M8 3v4M16 3v4M3 10h18M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="dash-stat-num" style="color:var(--purple)"><?= $events_today ?></div>
      <div class="dash-stat-label">Events วันนี้</div>
    </div>
    <div class="dash-stat-card">
      <div class="dash-stat-icon" style="background:var(--green-lt);color:var(--green)">
        <svg viewBox="0 0 24 24" fill="none" width="18" height="18"><path d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </div>
      <div class="dash-stat-num" style="color:var(--green)"><?= $todos_pending ?></div>
      <div class="dash-stat-label">To-Do ค้างอยู่</div>
    </div>
    <?php if ($overdue_tasks > 0): ?>
    <div class="dash-stat-card dash-stat-card-danger">
      <div class="dash-stat-icon" style="background:var(--pink-lt);color:var(--pink)">
        <svg viewBox="0 0 24 24" fill="none" width="18" height="18"><path d="M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="dash-stat-num" style="color:var(--pink)"><?= $overdue_tasks ?></div>
      <div class="dash-stat-label" style="color:var(--pink)">เกินกำหนด</div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Recent tasks -->
  <div class="dash-section">
    <div class="dash-section-header">
      <div class="dash-section-title">งานที่รับผิดชอบ</div>
      <a href="<?= get_uri('liff/app/tasks') ?>" class="dash-section-link">ดูทั้งหมด ›</a>
    </div>

    <?php if (empty($recent_tasks)): ?>
    <div class="empty-state" style="padding:24px 0">
      <p>ยังไม่มีงานค้างอยู่ 🎉</p>
    </div>
    <?php else: ?>
    <?php foreach ($recent_tasks as $t):
      $color = $t->status_color ?: '#4F7DF3';
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
    <?php endif; ?>
  </div>

</div>
