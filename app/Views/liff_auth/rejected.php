<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>DoJob — คำขอถูกปฏิเสธ</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= get_file_uri('assets/css/liff-ui.css') ?>?v=<?= time() ?>">
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Sarabun',sans-serif;background:var(--bg);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
  .card{background:var(--card);border-radius:var(--r-card);box-shadow:var(--shadow-card);padding:40px 28px;max-width:380px;width:100%;text-align:center}
  .icon{font-size:56px;margin-bottom:16px}
  h1{font-size:22px;font-weight:700;color:var(--text);margin-bottom:8px}
  .desc{font-size:14px;color:var(--label);line-height:1.7;margin-bottom:20px}
  .reason-box{background:var(--pink-lt);border:1.5px solid #F9A8C9;border-radius:14px;padding:14px 16px;margin-bottom:24px;text-align:left}
  .reason-label{font-size:12px;font-weight:600;color:#BE185D;margin-bottom:4px;text-transform:uppercase;letter-spacing:0.05em}
  .reason-text{font-size:14px;color:#881337;line-height:1.5}
  .btn{width:100%;padding:14px;border:none;border-radius:14px;font-family:'Sarabun',sans-serif;font-size:15px;font-weight:600;cursor:pointer;margin-bottom:10px;transition:all 0.2s}
  .btn-primary{background:var(--blue);color:#fff}
  .btn-secondary{background:#F1F5F9;color:#475569}
  .btn:active{transform:scale(0.98)}
</style>
</head>
<body>
<div class="card">
  <div class="icon">❌</div>
  <h1>คำขอถูกปฏิเสธ</h1>
  <p class="desc">ขออภัย คำขอเชื่อมต่อบัญชีของคุณ<br>ถูกปฏิเสธโดยแอดมิน</p>

  <?php if ($pending && $pending->rejection_note): ?>
  <div class="reason-box">
    <div class="reason-label">เหตุผล</div>
    <div class="reason-text"><?= esc($pending->rejection_note) ?></div>
  </div>
  <?php endif; ?>

  <a class="btn btn-primary" href="<?= get_uri('liff/select_user') ?>?uid=<?= urlencode($line_uid) ?>&name=<?= urlencode($pending->line_display_name ?? '') ?>">
    🔄 ลองเลือกบัญชีใหม่
  </a>
  <p style="font-size:13px;color:#94A3B8;margin-top:8px">หากมีข้อสงสัย กรุณาติดต่อแอดมิน</p>
</div>
</body>
</html>
