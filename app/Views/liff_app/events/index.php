<div class="page-header">
  <h1>📅 Events</h1>
</div>

<div class="tabs" data-tabs>
  <button class="tab-btn <?= $view==='today' ? 'active':'' ?>" onclick="location.href='<?= get_uri('liff/app/events?view=today') ?>'">วันนี้</button>
  <button class="tab-btn <?= $view==='week'  ? 'active':'' ?>" onclick="location.href='<?= get_uri('liff/app/events?view=week') ?>'">สัปดาห์นี้</button>
  <button class="tab-btn <?= $view==='all'   ? 'active':'' ?>" onclick="location.href='<?= get_uri('liff/app/events?view=all') ?>'">ทั้งหมด</button>
</div>

<?php if (empty($events)): ?>
<div class="empty-state">
  <div class="empty-icon">📅</div>
  <p>ไม่มี Event<?= $view === 'today' ? 'วันนี้' : '' ?></p>
</div>
<?php else: ?>
<?php foreach ($events as $e): ?>
<div class="task-card" onclick="location.href='<?= get_uri('liff/app/events/' . $e->id) ?>'">
  <div class="d-flex align-center gap-8 mb-8">
    <div style="width:4px;height:40px;border-radius:4px;background:<?= esc($e->color ?: '#6C8EF5') ?>;flex-shrink:0"></div>
    <div class="flex-1">
      <div class="task-title"><?= esc($e->title) ?></div>
      <div class="task-meta">
        <span class="task-meta-item">
          📅 <?= date('j M', strtotime($e->start_date)) ?>
          <?php if ($e->start_time): ?> ⏰ <?= date('H:i', strtotime($e->start_time)) ?><?php endif; ?>
          <?php if ($e->end_time): ?> – <?= date('H:i', strtotime($e->end_time)) ?><?php endif; ?>
        </span>
        <?php if ($e->line_notify_enabled): ?><span class="chip chip-blue" style="font-size:10px">🔔</span><?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
