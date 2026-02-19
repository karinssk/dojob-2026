<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>DoJob — LINE Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
<script charset="utf-8" src="https://static.line-scdn.net/liff/edge/versions/2.22.3/sdk.js"></script>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Sarabun',sans-serif;background:#F4F6FB;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
  .card{background:#fff;border-radius:24px;box-shadow:0 8px 32px rgba(0,0,0,0.08);padding:40px 32px;max-width:380px;width:100%;text-align:center}
  .logo{width:64px;height:64px;background:linear-gradient(135deg,#6C8EF5,#A78BFA);border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:28px}
  h1{font-size:22px;font-weight:700;color:#1E293B;margin-bottom:8px}
  p{font-size:14px;color:#64748B;line-height:1.6;margin-bottom:24px}
  .spinner{width:40px;height:40px;border:3px solid #E2E8F0;border-top-color:#6C8EF5;border-radius:50%;animation:spin 0.8s linear infinite;margin:20px auto}
  @keyframes spin{to{transform:rotate(360deg)}}
  .status{font-size:13px;color:#94A3B8;margin-top:12px}
  .error-box{background:#FFF0F3;border:1px solid #F97FA3;border-radius:12px;padding:16px;margin-top:20px;color:#C73060;font-size:14px;display:none}
  .btn{display:block;width:100%;padding:14px;background:#6C8EF5;color:#fff;border:none;border-radius:14px;font-family:'Sarabun',sans-serif;font-size:16px;font-weight:600;cursor:pointer;text-decoration:none;margin-top:16px}
  .btn:active{transform:scale(0.98)}
</style>
</head>
<body>
<div class="card">
  <div class="logo">📋</div>
  <h1>DoJob</h1>
  <p>กำลังยืนยันตัวตน LINE ของคุณ<br>กรุณารอสักครู่...</p>
  <div class="spinner" id="spinner"></div>
  <p class="status" id="status-text">กำลังเชื่อมต่อ...</p>
  <div class="error-box" id="error-box"></div>
</div>

<script>
const LIFF_ID = '<?= esc($liff_id) ?>';
const BASE_URL = '<?= get_uri() ?>';

async function init() {
  try {
    await liff.init({ liffId: LIFF_ID });
    setStatus('ยืนยันตัวตน LINE...');

    if (!liff.isLoggedIn()) {
      liff.login({ redirectUri: window.location.href });
      return;
    }

    const profile  = await liff.getProfile();
    const idToken  = liff.getIDToken();

    setStatus('กำลังตรวจสอบบัญชี...');

    const res  = await fetch(BASE_URL + 'liff/verify', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: new URLSearchParams({
        id_token:     idToken,
        line_uid:     profile.userId,
        display_name: profile.displayName,
      })
    });
    const data = await res.json();

    if (!data.success) {
      showError(data.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่');
      return;
    }

    if (data.action === 'login') {
      setStatus('เข้าสู่ระบบสำเร็จ! กำลังโหลด...');
      window.location.href = data.redirect;
      return;
    }

    if (data.action === 'pending' || data.action === 'rejected') {
      window.location.href = data.redirect;
      return;
    }

    if (data.action === 'select_user') {
      // Save to sessionStorage and redirect to select_user page
      sessionStorage.setItem('liff_uid',  data.line_uid);
      sessionStorage.setItem('liff_name', data.display_name);
      sessionStorage.setItem('liff_users', JSON.stringify(data.users));
      window.location.href = BASE_URL + 'liff/select_user'
        + '?uid='  + encodeURIComponent(data.line_uid)
        + '&name=' + encodeURIComponent(data.display_name);
    }

  } catch (e) {
    console.error(e);
    showError('ไม่สามารถเชื่อมต่อ LINE ได้: ' + e.message);
  }
}

function setStatus(txt) {
  document.getElementById('status-text').textContent = txt;
}

function showError(msg) {
  document.getElementById('spinner').style.display = 'none';
  const box = document.getElementById('error-box');
  box.textContent = msg;
  box.style.display = 'block';
}

init();
</script>
</body>
</html>
