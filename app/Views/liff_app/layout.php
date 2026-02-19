<!DOCTYPE html>
<html lang="th" data-base="<?= get_file_uri('') ?>"
  data-default-start="<?= get_setting('line_default_notify_before_start') ?: 30 ?>"
  data-default-end="<?= get_setting('line_default_notify_before_end') ?: 60 ?>"
  data-default-update="<?= get_setting('line_default_no_update_hours') ?: 24 ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#6C8EF5">
<title>DoJob — <?= esc($page_title ?? 'App') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= get_file_uri('assets/css/liff-ui.css') ?>?v=<?= time() ?>">
</head>
<body>

<!-- Page content -->
<div class="liff-page" id="liff-page-content">
  <?= $content ?? '' ?>
</div>
<div id="liff-debug-log" class="liff-debug-log" style="display:none"></div>

<!-- Floating Action Button (page-specific, injected by view) -->
<?php if (!empty($fab_url)): ?>
<a class="fab" href="<?= esc($fab_url) ?>" title="สร้างใหม่">+</a>
<?php endif; ?>

<!-- Bottom Tab Bar -->
<nav class="bottom-tabs">
  <a class="bottom-tab <?= ($active_tab ?? '') === 'todo'     ? 'active' : '' ?>" href="<?= get_uri('liff/app/todo') ?>">
    <span class="bottom-tab-icon">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </span>
    <span class="bottom-tab-label">To-Do</span>
  </a>
  <a class="bottom-tab <?= ($active_tab ?? '') === 'tasks'    ? 'active' : '' ?>" href="<?= get_uri('liff/app/tasks') ?>">
    <span class="bottom-tab-icon">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 12l2 2 4-4M7 4h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </span>
    <span class="bottom-tab-label">Tasks</span>
  </a>
  <a class="bottom-tab <?= ($active_tab ?? '') === 'projects' ? 'active' : '' ?>" href="<?= get_uri('liff/app/projects') ?>">
    <span class="bottom-tab-icon">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </span>
    <span class="bottom-tab-label">Projects</span>
  </a>
  <a class="bottom-tab <?= ($active_tab ?? '') === 'events'   ? 'active' : '' ?>" href="<?= get_uri('liff/app/events') ?>">
    <span class="bottom-tab-icon">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3v4M16 3v4M3 10h18M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </span>
    <span class="bottom-tab-label">Events</span>
  </a>
  <a class="bottom-tab <?= ($active_tab ?? '') === 'profile'  ? 'active' : '' ?>" href="<?= get_uri('liff/app/profile') ?>">
    <span class="bottom-tab-icon">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </span>
    <span class="bottom-tab-label">Profile</span>
  </a>
</nav>

<script src="<?= get_file_uri('assets/js/liff-app.js') ?>"></script>
<?php if (!empty($extra_js)): ?>
<script><?= $extra_js ?></script>
<?php endif; ?>
</body>
</html>
