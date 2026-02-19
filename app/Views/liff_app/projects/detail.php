<div class="page-header">
  <h1 style="font-size:17px"><?= esc($project->title) ?></h1>
</div>

<!-- Progress card -->
<div class="card" style="margin-bottom:12px">
  <div class="d-flex" style="justify-content:space-between;align-items:center;margin-bottom:12px">
    <div>
      <?php $sc = $project->status_color ?? '#94A3B8'; ?>
      <span class="chip" style="background:<?= esc($sc) ?>22;color:<?= esc($sc) ?>">
        <?= esc($project->status_title ?? 'Active') ?>
      </span>
    </div>
    <div style="font-size:20px;font-weight:700;color:#1E293B"><?= $progress ?>%</div>
  </div>

  <div style="background:#F1F5F9;border-radius:999px;height:8px;margin-bottom:12px;overflow:hidden">
    <div style="width:<?= $progress ?>%;height:100%;background:var(--blue);border-radius:999px"></div>
  </div>

  <div class="d-flex" style="justify-content:space-between;font-size:13px;color:#64748B">
    <span><?= count($tasks) ?> งานทั้งหมด</span>
    <?php if ($project->deadline): ?>
    <span>กำหนด <?= date('d M Y', strtotime($project->deadline)) ?></span>
    <?php endif; ?>
  </div>

  <?php if ($project->description): ?>
  <p style="font-size:13px;color:#475569;margin-top:12px;line-height:1.6"><?= nl2br(esc($project->description)) ?></p>
  <?php endif; ?>
</div>

<!-- Members -->
<?php if (!empty($members)): ?>
<div class="card" style="margin-bottom:12px">
  <div style="font-size:13px;font-weight:600;color:#64748B;margin-bottom:10px">สมาชิก (<?= count($members) ?> คน)</div>
  <div class="d-flex" style="flex-wrap:wrap;gap:8px">
    <?php foreach ($members as $m): ?>
    <div style="text-align:center;width:52px">
      <?php $img = get_avatar($m->image); ?>
      <img src="<?= esc($img) ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid #E2E8F0">
      <div style="font-size:10px;color:#64748B;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
        <?= esc(explode(' ', $m->name)[0]) ?>
      </div>
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
  <a href="<?= get_uri('liff/app/tasks/' . $t->id) ?>" style="text-decoration:none">
    <div class="card" style="margin-bottom:8px;padding:12px 14px">
      <div class="d-flex" style="justify-content:space-between;align-items:center">
        <div style="flex:1;min-width:0">
          <div style="font-size:14px;font-weight:600;color:#1E293B;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            <?= esc($t->title) ?>
          </div>
          <?php if ($t->assigned_name): ?>
          <div style="font-size:12px;color:#94A3B8;margin-top:2px"><?= esc($t->assigned_name) ?></div>
          <?php endif; ?>
        </div>
        <span class="chip" style="background:<?= esc($tc) ?>22;color:<?= esc($tc) ?>;margin-left:8px;flex-shrink:0;font-size:11px">
          <?= esc($t->status_title) ?>
        </span>
      </div>
      <?php if ($t->deadline): ?>
      <div style="font-size:11px;color:#94A3B8;margin-top:6px">
        <?php $late = strtotime($t->deadline) < time() && ($t->status_key_name ?? '') !== 'closed'; ?>
        <?= $late ? '<span style="color:#F97FA3">' : '' ?>กำหนด <?= date('d M', strtotime($t->deadline)) ?><?= $late ? '</span>' : '' ?>
      </div>
      <?php endif; ?>
    </div>
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
LiffApp.initTabs('proj-tabs');
</script>
