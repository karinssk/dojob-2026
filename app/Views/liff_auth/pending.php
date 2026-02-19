<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>DoJob — รอการอนุมัติ</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= get_file_uri('assets/css/liff-ui.css') ?>?v=<?= time() ?>">
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Sarabun',sans-serif;background:var(--bg);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
  .card{background:var(--card);border-radius:var(--r-card);box-shadow:var(--shadow-card);padding:40px 28px;max-width:380px;width:100%;text-align:center}
  .icon{font-size:56px;margin-bottom:16px;animation:pulse 2s ease-in-out infinite}
  @keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.08)}}
  h1{font-size:22px;font-weight:700;color:var(--text);margin-bottom:8px}
  .desc{font-size:14px;color:var(--label);line-height:1.7;margin-bottom:24px}
  .info-box{background:var(--surface);border:1.5px solid #DBEAFE;border-radius:14px;padding:16px;margin-bottom:24px;text-align:left}
  .info-row{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #EEF2FF;font-size:14px}
  .info-row:last-child{border-bottom:none}
  .info-label{color:var(--label);font-weight:500}
  .info-value{color:var(--text);font-weight:600;text-align:right;max-width:60%;word-break:break-all}
  .chip{display:inline-block;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600}
  .chip-yellow{background:var(--yellow-lt);color:#92400E;border:1px solid #FCD34D}
  .btn{width:100%;padding:14px;border:none;border-radius:14px;font-family:'Sarabun',sans-serif;font-size:15px;font-weight:600;cursor:pointer;margin-bottom:10px;transition:all 0.2s}
  .btn-primary{background:var(--blue);color:#fff}
  .btn-secondary{background:#F1F5F9;color:#475569}
  .btn:active{transform:scale(0.98)}
  .status-text{font-size:13px;color:var(--muted);margin-top:8px}
</style>
</head>
<body>
<div class="card">
  <div class="icon">⏳</div>
  <h1>รอการอนุมัติ</h1>
  <p class="desc">คำขอของคุณถูกส่งเรียบร้อยแล้ว<br>กรุณารอแอดมินอนุมัติการเชื่อมต่อ<br>คุณจะได้รับการแจ้งเตือนทาง LINE เมื่ออนุมัติแล้ว</p>

  <?php if ($pending): ?>
  <div class="info-box">
    <div class="info-row">
      <span class="info-label">LINE ของคุณ</span>
      <span class="info-value"><?= esc($pending->line_display_name) ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">เชื่อมกับบัญชี</span>
      <span class="info-value"><?= esc($pending->rise_user_name) ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">ส่งคำขอ</span>
      <span class="info-value"><?= date('j M Y H:i', strtotime($pending->created_at)) ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">สถานะ</span>
      <span class="info-value"><span class="chip chip-yellow">รอการอนุมัติ</span></span>
    </div>
  </div>
  <?php endif; ?>

  <button class="btn btn-primary" id="check-btn" onclick="checkStatus()">🔄 ตรวจสอบสถานะ</button>
  <p class="status-text" id="status-msg">กดปุ่มเพื่อตรวจสอบว่าได้รับการอนุมัติหรือยัง</p>
</div>

<script>
const LINE_UID = '<?= esc($line_uid) ?>';
const BASE_URL = '<?= get_uri() ?>';
let checking = false;

async function checkStatus() {
  if (checking) return;
  checking = true;
  const btn = document.getElementById('check-btn');
  const msg = document.getElementById('status-msg');
  btn.textContent = '⏳ กำลังตรวจสอบ...';
  btn.style.opacity = '0.7';

  try {
    const res  = await fetch(BASE_URL + 'liff/check_status?uid=' + encodeURIComponent(LINE_UID), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const data = await res.json();

    if (data.status === 'approved') {
      msg.textContent = '✅ ได้รับการอนุมัติแล้ว! กำลังเข้าสู่ระบบ...';
      window.location.href = data.redirect;
      return;
    }
    if (data.status === 'rejected') {
      window.location.href = BASE_URL + 'liff/rejected?uid=' + encodeURIComponent(LINE_UID);
      return;
    }
    msg.textContent = '⏳ ยังรอการอนุมัติอยู่ กรุณารอสักครู่';
  } catch (e) {
    msg.textContent = 'เกิดข้อผิดพลาด กรุณาลองใหม่';
  }

  btn.textContent = '🔄 ตรวจสอบสถานะ';
  btn.style.opacity = '1';
  checking = false;
}

// Auto-check every 30 seconds
setInterval(checkStatus, 30000);
</script>
</body>
</html>
