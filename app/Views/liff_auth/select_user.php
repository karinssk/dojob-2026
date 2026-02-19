<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>DoJob — เลือกบัญชี</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Sarabun',sans-serif;background:#F4F6FB;min-height:100vh;padding:0 0 env(safe-area-inset-bottom)}
  .header{background:#fff;padding:20px 20px 16px;box-shadow:0 2px 12px rgba(0,0,0,0.05);position:sticky;top:0;z-index:10}
  .header h1{font-size:18px;font-weight:700;color:#1E293B}
  .header p{font-size:13px;color:#64748B;margin-top:4px}
  .line-badge{display:inline-flex;align-items:center;gap:6px;background:#00C300;color:#fff;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:600;margin-top:8px}
  .search-wrap{padding:16px 20px 8px}
  .search-wrap input{width:100%;padding:12px 16px;border:1.5px solid #E2E8F0;border-radius:12px;font-family:'Sarabun',sans-serif;font-size:15px;outline:none;background:#fff}
  .search-wrap input:focus{border-color:#6C8EF5}
  .list{padding:0 20px 24px}
  .user-card{background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,0.05);padding:14px 16px;margin-bottom:10px;display:flex;align-items:center;gap:12px;cursor:pointer;border:2px solid transparent;transition:all 0.15s}
  .user-card:active{transform:scale(0.98)}
  .user-card.selected{border-color:#6C8EF5;background:#F0F4FF}
  .avatar{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#6C8EF5,#A78BFA);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:16px;flex-shrink:0;overflow:hidden}
  .avatar img{width:100%;height:100%;object-fit:cover}
  .user-info{flex:1}
  .user-name{font-size:15px;font-weight:600;color:#1E293B}
  .check-icon{color:#6C8EF5;font-size:20px;display:none}
  .user-card.selected .check-icon{display:block}
  .footer{position:fixed;bottom:0;left:0;right:0;background:#fff;padding:16px 20px;padding-bottom:calc(16px + env(safe-area-inset-bottom));box-shadow:0 -4px 20px rgba(0,0,0,0.06)}
  .btn{width:100%;padding:16px;background:#6C8EF5;color:#fff;border:none;border-radius:14px;font-family:'Sarabun',sans-serif;font-size:16px;font-weight:600;cursor:pointer;opacity:0.5;pointer-events:none;transition:all 0.2s}
  .btn.active{opacity:1;pointer-events:all}
  .btn:active{transform:scale(0.98)}
  .spinner{width:20px;height:20px;border:2px solid rgba(255,255,255,0.4);border-top-color:#fff;border-radius:50%;animation:spin 0.7s linear infinite;display:none;margin:0 auto}
  @keyframes spin{to{transform:rotate(360deg)}}
  .alert{margin:0 20px 10px;padding:12px 16px;border-radius:12px;font-size:14px}
  .alert-danger{background:#FFF0F3;color:#C73060;border:1px solid #F9A8C9}
  .alert-warn{background:#FFFBEB;color:#92400E;border:1px solid #FCD34D}
  .section-title{font-size:12px;font-weight:600;color:#94A3B8;text-transform:uppercase;letter-spacing:0.05em;padding:8px 20px 4px}
  .empty{text-align:center;padding:40px 20px;color:#94A3B8;font-size:14px}
</style>
</head>
<body>

<div class="header">
  <h1>เลือกบัญชีผู้ใช้</h1>
  <p>เลือกบัญชีที่ตรงกับชื่อของคุณเพื่อเชื่อมต่อกับ LINE</p>
  <?php if ($display_name): ?>
  <span class="line-badge">🟢 <?= esc($display_name) ?></span>
  <?php endif; ?>
</div>

<div id="alert-wrap"></div>

<div class="search-wrap">
  <input type="text" id="search-input" placeholder="🔍 ค้นหาชื่อ..." oninput="filterUsers()">
</div>

<div class="section-title">พนักงานทั้งหมด</div>

<div class="list" id="user-list">
  <?php foreach ($users as $u): ?>
  <div class="user-card" data-id="<?= $u['id'] ?>" data-name="<?= esc($u['name']) ?>" onclick="selectUser(this)">
    <div class="avatar">
      <?php if (!empty($u['image'])): ?>
        <img src="<?= get_uri('files/thumbnails/' . $u['image']) ?>" alt="">
      <?php else: ?>
        <?= mb_substr($u['name'], 0, 1) ?>
      <?php endif; ?>
    </div>
    <div class="user-info">
      <div class="user-name"><?= esc($u['name']) ?></div>
    </div>
    <div class="check-icon">✓</div>
  </div>
  <?php endforeach; ?>
  <?php if (empty($users)): ?>
  <div class="empty">ไม่พบรายชื่อพนักงาน</div>
  <?php endif; ?>
</div>

<div class="footer">
  <button class="btn" id="confirm-btn" onclick="submitLink()">ยืนยันการเชื่อมต่อ</button>
</div>

<script>
const LINE_UID     = '<?= esc($line_uid) ?>';
const DISPLAY_NAME = '<?= esc($display_name) ?>';
const BASE_URL     = '<?= get_uri() ?>';
let selectedId     = null;
let selectedName   = null;

function selectUser(el) {
  document.querySelectorAll('.user-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  selectedId   = el.dataset.id;
  selectedName = el.dataset.name;
  document.getElementById('confirm-btn').classList.add('active');
}

function filterUsers() {
  const q = document.getElementById('search-input').value.toLowerCase();
  document.querySelectorAll('.user-card').forEach(card => {
    const name = card.dataset.name.toLowerCase();
    card.style.display = name.includes(q) ? '' : 'none';
  });
}

async function submitLink() {
  if (!selectedId) return;
  const btn = document.getElementById('confirm-btn');
  btn.innerHTML = '<div class="spinner" style="display:block"></div>';
  btn.disabled  = true;

  try {
    const res  = await fetch(BASE_URL + 'index.php/liff/request_link', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: new URLSearchParams({
        line_uid:          LINE_UID,
        line_display_name: DISPLAY_NAME,
        rise_user_id:      selectedId,
      })
    });
    const data = await res.json();

    if (data.success) {
      window.location.href = data.redirect;
    } else {
      showAlert(data.message, 'danger');
      btn.innerHTML = 'ยืนยันการเชื่อมต่อ';
      btn.disabled  = false;
    }
  } catch (e) {
    showAlert('เกิดข้อผิดพลาด กรุณาลองใหม่', 'danger');
    btn.innerHTML = 'ยืนยันการเชื่อมต่อ';
    btn.disabled  = false;
  }
}

function showAlert(msg, type) {
  document.getElementById('alert-wrap').innerHTML =
    `<div class="alert alert-${type}">${msg}</div>`;
}
</script>
</body>
</html>
