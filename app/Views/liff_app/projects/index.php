<div class="page-header">
  <h1><?= esc($page_title) ?></h1>
</div>

<?php if (empty($projects)): ?>
<div class="card" style="text-align:center;padding:40px 20px;color:#94A3B8">
  <p>ยังไม่มีโปรเจกต์ที่คุณเกี่ยวข้อง</p>
</div>
<?php else: ?>
<?php foreach ($projects as $p): ?>
<?php
  $done  = (int)($p->done_count ?? 0);
  $total = (int)($p->task_count ?? 0);
  $pct   = $total > 0 ? round($done / $total * 100) : 0;
  $status_label = $p->status_title ?? ($pct >= 100 ? 'Completed' : 'Open');
  $accent       = $pct >= 100 ? 'var(--green)' : 'var(--blue)';
  $accent_bg    = $pct >= 100 ? 'var(--green-lt)' : 'var(--blue-lt)';
?>
<a class="project-link" href="<?= get_uri('liff/app/projects/' . $p->id) ?>">
  <div class="card project-card">
    <div class="project-top">
      <div class="project-title"><?= esc($p->title) ?></div>
      <span class="project-status" style="background:<?= $accent_bg ?>;color:<?= $accent ?>">
        <?= esc($status_label) ?>
      </span>
    </div>

    <?php $category = $p->project_category ?? ''; ?>
    <?php if ($category): ?>
    <div class="project-category"><?= esc($category) ?></div>
    <?php endif; ?>

    <div class="project-progress">
      <div class="project-progress-bar" style="width:<?= $pct ?>%;background:<?= $accent ?>"></div>
    </div>

    <div class="project-meta">
      <div><?= $done ?>/<?= $total ?> งาน · <?= $pct ?>%</div>
      <div>
        <?= (int)($p->member_count ?? 0) ?> คน
        <?php if (!empty($p->line_notify_enabled)): ?>
        · LINE
        <?php endif; ?>
      </div>
    </div>

    <?php if ($p->deadline): ?>
    <div class="project-deadline">กำหนด <?= date('d M Y', strtotime($p->deadline)) ?></div>
    <?php endif; ?>
  </div>
</a>
<?php endforeach; ?>
<?php endif; ?>
