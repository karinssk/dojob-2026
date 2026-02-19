<?php
$user = $login_user;
$img  = $user->image ? get_uri('files/' . $user->image) : get_uri('assets/images/user-avatar.jpg');
?>

<!-- Avatar + Name -->
<div class="card" style="text-align:center;padding:28px 20px;margin-bottom:12px">
  <img src="<?= esc($img) ?>" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid #E2E8F0;margin-bottom:12px">
  <div style="font-size:18px;font-weight:700;color:#1E293B">
    <?= esc(trim($user->first_name . ' ' . $user->last_name)) ?>
  </div>
  <div style="font-size:13px;color:#64748B;margin-top:4px"><?= esc($user->email ?? '') ?></div>

  <?php if (!empty($mapping)): ?>
  <div style="margin-top:12px;padding:8px 16px;background:#F0FDF4;border-radius:12px;display:inline-block">
    <span style="font-size:12px;color:#16A34A;font-weight:600">เชื่อมต่อ LINE แล้ว</span>
    <div style="font-size:11px;color:#64748B;margin-top:2px"><?= esc($mapping->line_display_name ?? '') ?></div>
  </div>
  <?php else: ?>
  <div style="margin-top:12px;padding:8px 16px;background:#FFF7ED;border-radius:12px;display:inline-block">
    <span style="font-size:12px;color:#EA580C;font-weight:600">ยังไม่ได้เชื่อมต่อ LINE</span>
  </div>
  <?php endif; ?>
</div>

<!-- Account info -->
<div class="card" style="margin-bottom:12px">
  <div style="font-size:13px;font-weight:600;color:#64748B;margin-bottom:12px">ข้อมูลบัญชี</div>
  <div class="info-row">
    <span class="info-label">ตำแหน่ง</span>
    <span class="info-val"><?= esc($user->job_title ?? '—') ?></span>
  </div>
  <div class="info-row">
    <span class="info-label">แผนก</span>
    <span class="info-val"><?= esc($user->department ?? '—') ?></span>
  </div>
  <div class="info-row">
    <span class="info-label">โทรศัพท์</span>
    <span class="info-val"><?= esc($user->phone ?? '—') ?></span>
  </div>
  <?php if (!empty($mapping)): ?>
  <div class="info-row">
    <span class="info-label">LINE UID</span>
    <span class="info-val" style="font-size:11px;word-break:break-all"><?= esc($mapping->line_user_id ?? '—') ?></span>
  </div>
  <?php endif; ?>
</div>

<!-- Actions -->
<div class="card" style="margin-bottom:12px">
  <div style="font-size:13px;font-weight:600;color:#64748B;margin-bottom:12px">การตั้งค่า</div>

  <a href="<?= get_uri('profile') ?>" class="list-action-row">
    <span>แก้ไขโปรไฟล์</span>
    <span style="color:#94A3B8">›</span>
  </a>
  <a href="<?= get_uri('profile/change_password') ?>" class="list-action-row">
    <span>เปลี่ยนรหัสผ่าน</span>
    <span style="color:#94A3B8">›</span>
  </a>
</div>

<!-- Logout -->
<a href="<?= get_uri('logout') ?>" class="btn btn-block" style="background:#FFF0F3;color:#C73060;border:none;font-weight:600">
  ออกจากระบบ
</a>

<style>
.info-row {
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:8px 0;
  border-bottom:1px solid #F1F5F9;
  font-size:13px;
}
.info-row:last-child { border-bottom:none; }
.info-label { color:#94A3B8; }
.info-val { color:#1E293B; font-weight:500; text-align:right; }
.list-action-row {
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:10px 0;
  border-bottom:1px solid #F1F5F9;
  text-decoration:none;
  color:#1E293B;
  font-size:14px;
}
.list-action-row:last-child { border-bottom:none; }
</style>
