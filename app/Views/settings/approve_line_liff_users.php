<div id="page-content" class="page-wrapper clearfix">
  <div class="card">
    <div class="page-title clearfix">
      <h1><i data-feather="smartphone" class="icon-16"></i> LINE LIFF Settings</h1>
      <div class="title-button-group">
        <?php if ($pending_count > 0): ?>
        <span class="badge bg-danger" style="font-size:14px;padding:6px 12px"><?= $pending_count ?> รอการอนุมัติ</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="card-body">

      <!-- Tab navigation -->
      <ul class="nav nav-tabs" id="liff-tabs" style="margin-bottom:20px">
        <li class="nav-item">
          <a class="nav-link <?= $current_tab === 'credentials' ? 'active' : '' ?>"
            href="<?= get_uri('settings/approve_line_liff_users?tab=credentials') ?>">
            🔑 Credentials
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $current_tab === 'pending' ? 'active' : '' ?>"
            href="<?= get_uri('settings/approve_line_liff_users?tab=pending') ?>">
            ⏳ รออนุมัติ
            <?php if ($pending_count > 0): ?>
            <span class="badge bg-danger ms-1"><?= $pending_count ?></span>
            <?php endif; ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $current_tab === 'approved' ? 'active' : '' ?>"
            href="<?= get_uri('settings/approve_line_liff_users?tab=approved') ?>">
            ✅ อนุมัติแล้ว
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $current_tab === 'rejected' ? 'active' : '' ?>"
            href="<?= get_uri('settings/approve_line_liff_users?tab=rejected') ?>">
            ❌ ปฏิเสธ
          </a>
        </li>
      </ul>

      <!-- Tab content -->
      <?php if ($current_tab === 'credentials'): ?>
        <?= view('settings/liff_credentials_tab', get_defined_vars()) ?>
      <?php elseif ($current_tab === 'pending'): ?>
        <?= view('settings/liff_pending_tab') ?>
      <?php elseif ($current_tab === 'approved'): ?>
        <?= view('settings/liff_approved_tab') ?>
      <?php elseif ($current_tab === 'rejected'): ?>
        <?= view('settings/liff_rejected_tab') ?>
      <?php endif; ?>

    </div>
  </div>
</div>
