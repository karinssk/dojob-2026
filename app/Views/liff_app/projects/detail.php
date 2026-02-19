<div class="page-header">
  <h1 style="font-size:17px"><?= esc($project->title) ?></h1>
</div>

<!-- Progress card -->
<?php
  $sc = $project->status_color ?? 'var(--blue)';
  $status_label = $project->status_title ?? 'Active';
?>
<div class="card project-detail-card">
  <div class="project-detail-top">
    <span class="project-status-pill" style="background:<?= esc($sc) ?>22;color:<?= esc($sc) ?>">
      <?= esc($status_label) ?>
    </span>
    <div class="project-detail-percent"><?= $progress ?>%</div>
  </div>

  <div class="project-progress">
    <div class="project-progress-bar" style="width:<?= $progress ?>%;background:<?= esc($sc) ?>"></div>
  </div>

  <div class="project-detail-meta">
    <span><?= count($tasks) ?> งานทั้งหมด</span>
    <?php if ($project->deadline): ?>
    <span>กำหนด <?= date('d M Y', strtotime($project->deadline)) ?></span>
    <?php else: ?>
    <span>ไม่กำหนดวันสิ้นสุด</span>
    <?php endif; ?>
  </div>

  <?php if ($project->description): ?>
  <p class="project-detail-desc"><?= nl2br(esc($project->description)) ?></p>
  <?php endif; ?>
</div>

<!-- Members -->
<?php if (!empty($members)): ?>
<div class="card member-card">
  <div class="member-card-title">สมาชิก (<?= count($members) ?> คน)</div>
  <div class="member-grid">
    <?php foreach ($members as $m): ?>
    <div class="member-item">
      <?php $img = get_avatar($m->image); ?>
      <img src="<?= esc($img) ?>" class="member-avatar" alt="">
      <div class="member-name"><?= esc(explode(' ', $m->name)[0]) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Tabs: Tasks / Activity -->
<div class="tabs" id="proj-tabs">
  <button class="tab-btn active" data-tab="tasks">งาน (<?= count($tasks) ?>)</button>
  <button class="tab-btn" data-tab="activity">กิจกรรม</button>
</div>

<!-- Tasks tab -->
<div class="tab-panel active" id="tab-tasks">
  <?php if (empty($tasks)): ?>
  <div style="text-align:center;padding:24px;color:#94A3B8;font-size:14px">ยังไม่มีงาน</div>
  <?php else: ?>
  <?php foreach ($tasks as $t): ?>
  <?php $tc = $t->status_color ?? '#94A3B8'; ?>
  <a class="project-task-link" href="<?= get_uri('liff/app/tasks/' . $t->id) ?>">
    <div class="project-task-row">
      <div class="project-task-main">
        <div class="project-task-title"><?= esc($t->title) ?></div>
        <?php if ($t->assigned_name): ?>
        <div class="project-task-sub"><?= esc($t->assigned_name) ?></div>
        <?php endif; ?>
      </div>
      <span class="project-task-status" style="background:<?= esc($tc) ?>22;color:<?= esc($tc) ?>">
        <?= esc($t->status_title) ?>
      </span>
    </div>
    <?php if ($t->deadline): ?>
    <?php $late = strtotime($t->deadline) < time() && ($t->status_key_name ?? '') !== 'closed'; ?>
    <div class="project-task-deadline <?= $late ? 'late' : '' ?>">
      กำหนด <?= date('d M', strtotime($t->deadline)) ?>
    </div>
    <?php endif; ?>
  </a>
  <?php endforeach; ?>
  <?php endif; ?>

  <a href="<?= get_uri('liff/app/projects/' . $project->id . '/task/create') ?>" class="btn btn-primary btn-block" style="margin-top:12px">+ เพิ่มงาน</a>
</div>

<!-- Activity tab -->
<div class="tab-panel" id="tab-activity">
  <?php if (empty($activity)): ?>
  <div style="text-align:center;padding:24px;color:#94A3B8;font-size:14px">ยังไม่มีกิจกรรม</div>
  <?php else: ?>
  <div class="activity-feed">
    <?php foreach ($activity as $a): ?>
    <div class="activity-item">
      <div class="activity-dot"></div>
      <div class="activity-body">
        <strong><?= esc($a->user_name ?? 'ระบบ') ?></strong>
        <span style="color:#64748B"> <?= esc($a->note ?? '') ?></span>
        <div class="activity-time"><?= date('d M H:i', strtotime($a->created_at ?? 'now')) ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<script>
LiffApp.initTabs('#proj-tabs');
</script>
