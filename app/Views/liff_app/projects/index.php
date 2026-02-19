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
  $sc    = $p->status_color ?? '#94A3B8';
?>
<a href="<?= get_uri('liff/app/projects/' . $p->id) ?>" style="text-decoration:none">
  <div class="card" style="margin-bottom:12px">
    <div class="d-flex" style="justify-content:space-between;align-items:flex-start;margin-bottom:10px">
      <div style="flex:1;min-width:0">
        <div style="font-size:15px;font-weight:700;color:#1E293B;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
          <?= esc($p->title) ?>
        </div>
        <?php $category = $p->project_category ?? ''; ?>
        <?php if ($category): ?>
        <div style="font-size:12px;color:#94A3B8;margin-top:2px"><?= esc($category) ?></div>
        <?php endif; ?>
      </div>
      <span class="chip" style="background:<?= esc($sc) ?>22;color:<?= esc($sc) ?>;flex-shrink:0;margin-left:8px">
        <?= esc($p->status_title ?? 'Active') ?>
      </span>
    </div>

    <!-- Progress bar -->
    <div style="background:#F1F5F9;border-radius:999px;height:6px;margin-bottom:8px;overflow:hidden">
      <div style="width:<?= $pct ?>%;height:100%;background:var(--blue);border-radius:999px;transition:width .3s"></div>
    </div>

    <div class="d-flex" style="justify-content:space-between;align-items:center">
      <div style="font-size:12px;color:#64748B">
        <?= $done ?>/<?= $total ?> งาน · <?= $pct ?>%
      </div>
      <div style="font-size:12px;color:#94A3B8">
        <?= (int)($p->member_count ?? 0) ?> คน
        <?php if (!empty($p->line_notify_enabled)): ?>
        · LINE แจ้งเตือน
        <?php endif; ?>
      </div>
    </div>

    <?php if ($p->deadline): ?>
    <div style="font-size:11px;color:#94A3B8;margin-top:6px">
      กำหนด <?= date('d M Y', strtotime($p->deadline)) ?>
    </div>
    <?php endif; ?>
  </div>
</a>
<?php endforeach; ?>
<?php endif; ?>
