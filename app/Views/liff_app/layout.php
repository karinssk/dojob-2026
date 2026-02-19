<!DOCTYPE html>
<html lang="th" data-base="<?= get_uri() ?>"
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
<link rel="stylesheet" href="<?= get_uri('assets/css/liff-ui.css') ?>?v=<?= time() ?>">
</head>
<body>

<!-- Page content -->
<div class="liff-page" id="liff-page-content">
  <?= $content ?? '' ?>
</div>

<!-- Floating Action Button (page-specific, injected by view) -->
<?php if (!empty($fab_url)): ?>
<a class="fab" href="<?= esc($fab_url) ?>" title="สร้างใหม่">＋</a>
<?php endif; ?>

<!-- Bottom Tab Bar -->
<nav class="bottom-tabs">
  <a class="bottom-tab <?= ($active_tab ?? '') === 'todo'     ? 'active' : '' ?>" href="<?= get_uri('liff/app/todo') ?>">
    <span class="bottom-tab-icon">✅</span>To-Do
  </a>
  <a class="bottom-tab <?= ($active_tab ?? '') === 'tasks'    ? 'active' : '' ?>" href="<?= get_uri('liff/app/tasks') ?>">
    <span class="bottom-tab-icon">📋</span>Tasks
  </a>
  <a class="bottom-tab <?= ($active_tab ?? '') === 'projects' ? 'active' : '' ?>" href="<?= get_uri('liff/app/projects') ?>">
    <span class="bottom-tab-icon">📁</span>Projects
  </a>
  <a class="bottom-tab <?= ($active_tab ?? '') === 'events'   ? 'active' : '' ?>" href="<?= get_uri('liff/app/events') ?>">
    <span class="bottom-tab-icon">📅</span>Events
  </a>
  <a class="bottom-tab <?= ($active_tab ?? '') === 'profile'  ? 'active' : '' ?>" href="<?= get_uri('liff/app/profile') ?>">
    <span class="bottom-tab-icon">👤</span>Profile
  </a>
</nav>

<script src="<?= get_uri('assets/js/liff-app.js') ?>"></script>
<?php if (!empty($extra_js)): ?>
<script><?= $extra_js ?></script>
<?php endif; ?>
</body>
</html>
