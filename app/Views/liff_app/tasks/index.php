<div class="page-header">
  <h1>Tasks</h1>
</div>

<!-- Mine / Assigned tabs -->
<div class="tabs" data-tabs style="margin-top:0">
  <button class="tab-btn <?= $filter==='mine' ? 'active':'' ?>" onclick="location.href='<?= get_uri('liff/app/tasks?filter=mine') ?>'">งานของฉัน</button>
  <button class="tab-btn <?= $filter==='assigned_by_me' ? 'active':'' ?>" onclick="location.href='<?= get_uri('liff/app/tasks?filter=assigned_by_me') ?>'">มอบหมาย</button>
</div>

<?php if ($filter === 'assigned_by_me' && !empty($staff_users)): ?>
<?php $d = $quick_assign_defaults; ?>
<!-- ── Quick Assign Panel ─────────────────────────────────────── -->
<div class="qa-card">

  <!-- Step 1: pick a person -->
  <div class="qa-section-label">1 · เลือกคนที่จะมอบหมายงาน</div>
  <div class="qa-avatar-grid" id="qa-avatar-grid">
    <?php foreach ($staff_users as $u):
      $name  = trim($u->first_name . ' ' . $u->last_name);
      $short = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
      $img   = $u->image ? get_avatar($u->image) : '';
    ?>
    <div class="qa-avatar-item" data-uid="<?= $u->id ?>" data-name="<?= esc($name) ?>"
         onclick="qaSelectUser(this)">
      <?php if ($img): ?>
        <img src="<?= esc($img) ?>" class="qa-avatar-img"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
        <div class="qa-avatar-fallback" style="display:none"><?= esc($short) ?></div>
      <?php else: ?>
        <div class="qa-avatar-fallback"><?= esc($short) ?></div>
      <?php endif; ?>
      <div class="qa-avatar-name"><?= esc(mb_substr($name, 0, 9, 'UTF-8')) ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Step 2: task title -->
  <div class="qa-section-label" id="qa-step2-label" style="opacity:.35">2 · ชื่องาน</div>
  <div class="qa-input-row" id="qa-input-row">
    <textarea id="qa-title" class="qa-title-input" rows="3"
              placeholder="ระบุชื่องาน..." disabled oninput="qaCheckReady(); qaAutoResize(this)"></textarea>
    <button class="qa-send-btn" id="qa-send-btn" onclick="qaSubmit()" disabled>
      ส่งงาน ›
    </button>
  </div>

  <!-- Defaults summary pills -->
  <div class="qa-defaults-row">
    <span class="qa-default-chip">📅 <?= date('d/m', strtotime($d['start_date'])) ?></span>
    <span class="qa-default-chip"> <?= $d['start_time'] ?> – <?= $d['end_time'] ?></span>
    <span class="qa-default-chip"> <?= esc(mb_substr($d['project_name'], 0, 18, 'UTF-8')) ?><?= mb_strlen($d['project_name'], 'UTF-8') > 18 ? '…' : '' ?></span>
    <span class="qa-default-chip">🏷 To Do</span>
  </div>

  <div id="qa-submitting" style="display:none;text-align:center;padding:10px 0 4px;color:var(--muted);font-size:13px">
    กำลังส่งงาน...
  </div>
</div>

<script>
(function () {
  var _uid        = null;
  var _name       = null;
  var _submitting = false;   // guard against double-tap
  var _defs = <?= json_encode($d) ?>;
  var _api  = 'liff/api/tasks/quick_assign';

  window.qaAutoResize = function (el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 200) + 'px';
  };

  window.qaSelectUser = function (el) {
    document.querySelectorAll('.qa-avatar-item').forEach(function(a){
      a.classList.remove('qa-selected');
    });
    el.classList.add('qa-selected');
    _uid  = el.dataset.uid;
    _name = el.dataset.name;

    var inp = document.getElementById('qa-title');
    inp.disabled = false;
    inp.placeholder = _name + ' — ชื่องาน...';
    inp.focus();

    document.getElementById('qa-step2-label').style.opacity = '1';
    document.getElementById('qa-input-row').classList.add('qa-active');
    qaCheckReady();
  };

  window.qaCheckReady = function () {
    var ok = _uid && document.getElementById('qa-title').value.trim().length > 0;
    document.getElementById('qa-send-btn').disabled = !ok;
  };

  window.qaSubmit = async function () {
    if (_submitting) return;                          // block double-tap
    var title = document.getElementById('qa-title').value.trim();
    if (!title || !_uid) return;

    _submitting = true;
    var btn = document.getElementById('qa-send-btn');
    btn.disabled  = true;
    btn.textContent = 'กำลังส่ง...';
    document.getElementById('qa-submitting').style.display = 'block';

    try {
      var payload = {
        title:       title,
        assigned_to: _uid,
        project_id:  _defs.project_id,
        start_date:  _defs.start_date,
        start_time:  _defs.start_time,
        deadline:    _defs.deadline,
        end_time:    _defs.end_time,
        status_id:   _defs.status_id,
        context:     'general'
      };
      var res = await LiffApp.api(_api, 'POST', payload);

      if (res && res.success) {
        LiffApp.toast('ส่งงานให้ ' + _name + ' แล้ว', 'success');
        // Reset panel
        var ta = document.getElementById('qa-title');
        ta.value        = '';
        ta.disabled     = true;
        ta.placeholder  = 'ระบุชื่องาน...';
        ta.style.height = 'auto';
        document.querySelectorAll('.qa-avatar-item').forEach(function(a){
          a.classList.remove('qa-selected');
        });
        _uid = null; _name = null;
        document.getElementById('qa-step2-label').style.opacity = '.35';
        document.getElementById('qa-input-row').classList.remove('qa-active');
        btn.disabled    = true;
        btn.textContent = 'ส่งงาน ›';
        setTimeout(function(){ location.reload(); }, 900);
      } else {
        LiffApp.toast((res && res.message) ? res.message : 'เกิดข้อผิดพลาด', 'error');
        btn.disabled    = false;
        btn.textContent = 'ส่งงาน ›';
        _submitting     = false;
      }
    } catch(e) {
      LiffApp.toast('เกิดข้อผิดพลาด', 'error');
      btn.disabled    = false;
      btn.textContent = 'ส่งงาน ›';
      _submitting     = false;
    }
    document.getElementById('qa-submitting').style.display = 'none';
  };
})();
</script>
<?php endif; ?>

<!-- Filter chips -->
<?php
$base = get_uri('liff/app/tasks') . '?filter=' . $filter;
$noFilter  = !$status_id && !$overdue;
?>
<div class="filter-scroll">
  <button class="chip <?= $noFilter ? 'chip-active' : '' ?>" onclick="location.href='<?= $base ?>'">ยังไม่เสร็จ</button>
  <button class="chip <?= $overdue ? 'chip-active' : '' ?>" onclick="location.href='<?= $base . '&overdue=1' ?>'">⚠ เกินกำหนด</button>
  <div class="filter-divider"></div>
  <?php foreach ($statuses as $s):
    $sc = $s->color ?: '#6C8EF5';
    $isActive = $status_id == $s->id;
  ?>
  <button class="chip" style="background:<?= $isActive ? $sc : $sc.'22' ?>;color:<?= $isActive ? '#fff' : $sc ?>"
    onclick="location.href='<?= $base . '&status_id=' . $s->id ?>'">
    <?= esc($s->title) ?>
  </button>
  <?php endforeach; ?>
</div>

<?php if (empty($tasks)): ?>
<div class="empty-state">
  <p>ยังไม่มีงาน<br>กด + เพื่อสร้างงานใหม่</p>
</div>
<?php else: ?>
<?php foreach ($tasks as $t):
  $color = $t->status_color ?: '#6C8EF5';
  $bg    = $color . '22';
  $is_overdue = $t->deadline && strtotime($t->deadline) < time() && ($t->status_key ?? '') !== 'done';
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
      <?php if ($t->deadline): ?> – <?= date('j M', strtotime($t->deadline)) ?>
        <?php if ($t->end_time): ?> <?= date('H:i', strtotime($t->end_time)) ?><?php endif; ?>
      <?php endif; ?>
    </span>
    <?php endif; ?>
    <?php if ($t->assigned_name): ?>
    <span class="task-meta-item"> <?= esc($t->assigned_name) ?></span>
    <?php endif; ?>
    <?php if ($t->project_title): ?>
    <span class="task-meta-item"> <?= esc($t->project_title) ?></span>
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
