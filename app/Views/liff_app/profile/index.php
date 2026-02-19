<?php
$user = $login_user;
$img  = get_avatar($user->image);
?>

<!-- Profile header -->
<div class="card profile-card">
  <img src="<?= esc($img) ?>" class="profile-avatar" alt="">
  <div class="profile-name"><?= esc(trim($user->first_name . ' ' . $user->last_name)) ?></div>
  <div class="profile-email"><?= esc($user->email ?? '') ?></div>

  <?php if (!empty($mapping)): ?>
  <div class="profile-pill profile-pill-success">เชื่อมต่อ LINE (LIFF) แล้ว</div>
  <?php else: ?>
  <div class="profile-pill profile-pill-warn">ยังไม่ได้เชื่อมต่อ LINE</div>
  <?php endif; ?>
</div>

<!-- Account info -->
<div class="card list-card">
  <div class="card-header"><h3>ข้อมูลบัญชี</h3></div>
  <div class="list-row">
    <span>ตำแหน่ง</span>
    <span class="list-val"><?= esc($user->job_title ?? '—') ?></span>
  </div>
  <div class="list-row">
    <span>แผนก</span>
    <span class="list-val"><?= esc($user->department ?? '—') ?></span>
  </div>
  <div class="list-row">
    <span>โทรศัพท์</span>
    <span class="list-val"><?= esc($user->phone ?? '—') ?></span>
  </div>
  <?php if (!empty($mapping)): ?>
  <div class="list-row">
    <span>LINE LIFF UID</span>
    <span class="list-val list-val-small"><?= esc($mapping->line_liff_user_id ?? '—') ?></span>
  </div>
  <?php endif; ?>
</div>

<!-- Actions -->
<div class="card list-card">
  <div class="card-header"><h3>การตั้งค่า</h3></div>
  <a href="<?= get_uri('profile') ?>" class="list-link">
    <span>แก้ไขโปรไฟล์</span>
    <span class="list-arrow">›</span>
  </a>
  <a href="<?= get_uri('profile/change_password') ?>" class="list-link">
    <span>เปลี่ยนรหัสผ่าน</span>
    <span class="list-arrow">›</span>
  </a>
  <a href="<?= get_uri('logout') ?>" class="list-link list-link-danger">
    <span>ออกจากระบบ</span>
    <span class="list-arrow">›</span>
  </a>
</div>
