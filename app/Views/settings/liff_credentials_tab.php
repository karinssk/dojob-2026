<?php echo form_open(get_uri('liff_settings/save_liff_credentials'), ['id' => 'liff-cred-form', 'class' => 'general-form', 'role' => 'form']); ?>

<!-- ── Messaging API ─────────────────────────────────────────────── -->
<h5 style="margin-bottom:12px;color:#64748B;font-size:13px;text-transform:uppercase;letter-spacing:.05em">
  Messaging API (Bot)
</h5>
<div class="row">
  <div class="col-md-6">
    <div class="form-group">
      <label class="control-label">Channel Access Token</label>
      <input class="form-control" type="password" name="line_channel_access_token" autocomplete="off"
        value="<?= esc($line_channel_access_token ?? '') ?>"
        placeholder="Channel Access Token จาก LINE Developers Console">
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-group">
      <label class="control-label">Channel Secret</label>
      <input class="form-control" type="password" name="line_channel_secret" autocomplete="off"
        value="<?= esc($line_channel_secret ?? '') ?>"
        placeholder="Channel Secret จาก LINE Developers Console">
    </div>
  </div>
</div>
<div class="row">
  <div class="col-md-12">
    <button type="button" class="btn btn-default btn-sm" onclick="testMessagingApi()">
      🔌 ทดสอบการเชื่อมต่อ Messaging API
    </button>
    <span id="msg-api-result" style="margin-left:10px;font-size:13px"></span>
  </div>
</div>

<hr style="margin:20px 0">

<!-- ── LINE Login / LIFF ──────────────────────────────────────────── -->
<h5 style="margin-bottom:12px;color:#64748B;font-size:13px;text-transform:uppercase;letter-spacing:.05em">
  LINE Login Channel &amp; LIFF
</h5>
<div class="row">
  <div class="col-md-6">
    <div class="form-group">
      <label class="control-label">Login Channel ID</label>
      <input class="form-control" type="text" name="line_login_channel_id"
        value="<?= esc($line_login_channel_id ?? '') ?>"
        placeholder="เช่น 2007654321">
      <p class="help-block">Channel ID จาก LINE Login channel (ไม่ใช่ Messaging API)</p>
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-group">
      <label class="control-label">Login Channel Secret</label>
      <input class="form-control" type="password" name="line_login_channel_secret" autocomplete="off"
        value="<?= esc($line_login_channel_secret ?? '') ?>"
        placeholder="Channel Secret ของ LINE Login">
    </div>
  </div>
</div>
<div class="row">
  <div class="col-md-6">
    <div class="form-group">
      <label class="control-label">LIFF ID</label>
      <input class="form-control" type="text" name="line_liff_id"
        value="<?= esc($line_liff_id ?? '') ?>"
        placeholder="เช่น 2007654321-abcdefgh">
      <p class="help-block">LIFF ID จาก LINE Developers → LIFF apps</p>
    </div>
  </div>
  <div class="col-md-6" style="display:flex;align-items:flex-end;padding-bottom:16px">
    <div>
      <button type="button" class="btn btn-default btn-sm" onclick="testLoginChannel()">
        🔌 ตรวจสอบค่า LIFF
      </button>
      <span id="liff-test-result" style="margin-left:10px;font-size:13px"></span>
      <div style="margin-top:8px;font-size:12px;color:#94A3B8">
        LIFF URL: <code><?= get_uri('liff') ?></code>
      </div>
    </div>
  </div>
</div>

<hr style="margin:20px 0">

<!-- ── Admin Notify UIDs ─────────────────────────────────────────── -->
<h5 style="margin-bottom:12px;color:#64748B;font-size:13px;text-transform:uppercase;letter-spacing:.05em">
  Admin Notification UIDs
</h5>
<div class="row">
  <div class="col-md-12">
    <div class="form-group">
      <label class="control-label">LINE User IDs ของ Admin (สำหรับรับแจ้งเตือนคำขอใหม่)</label>
      <textarea class="form-control" name="line_admin_notify_uids" rows="3"
        placeholder="1 UID ต่อบรรทัด หรือ JSON array เช่น [&quot;Uxxxxxx&quot;,&quot;Uxxxxxx&quot;]"><?= esc($line_admin_notify_uids ?? '') ?></textarea>
      <p class="help-block">Admin เหล่านี้จะได้รับ LINE push message เมื่อมีผู้ใช้ใหม่ขอเชื่อมต่อ</p>
    </div>
  </div>
</div>

<hr style="margin:20px 0">

<!-- ── Default Notification Minutes ──────────────────────────────── -->
<h5 style="margin-bottom:12px;color:#64748B;font-size:13px;text-transform:uppercase;letter-spacing:.05em">
  ค่าเริ่มต้นการแจ้งเตือน (pre-fill เมื่อเปิด toggle)
</h5>
<div class="row">
  <div class="col-md-4">
    <div class="form-group">
      <label class="control-label">แจ้งก่อนเริ่ม (นาที)</label>
      <input class="form-control" type="number" name="liff_notify_default_start" min="0" max="1440"
        value="<?= esc($liff_notify_default_start ?? '') ?>" placeholder="เช่น 30">
    </div>
  </div>
  <div class="col-md-4">
    <div class="form-group">
      <label class="control-label">แจ้งก่อนสิ้นสุด (นาที)</label>
      <input class="form-control" type="number" name="liff_notify_default_end" min="0" max="1440"
        value="<?= esc($liff_notify_default_end ?? '') ?>" placeholder="เช่น 15">
    </div>
  </div>
  <div class="col-md-4">
    <div class="form-group">
      <label class="control-label">แจ้งเมื่อไม่อัปเดต (ชั่วโมง)</label>
      <input class="form-control" type="number" name="liff_notify_default_update" min="1" max="720"
        value="<?= esc($liff_notify_default_update ?? '') ?>" placeholder="เช่น 24">
    </div>
  </div>
</div>

<!-- Save button -->
<div class="row" style="margin-top:10px">
  <div class="col-md-12">
    <button type="submit" class="btn btn-primary">💾 บันทึกการตั้งค่า</button>
  </div>
</div>

<?php echo form_close(); ?>

<script>
$(document).ready(function(){
  $('#liff-cred-form').on('submit', function(e){
    e.preventDefault();
    $.ajax({
      url: $(this).attr('action'),
      method: 'POST',
      data: $(this).serialize(),
      success: function(r){
        if (r.success) {
          app_show_success_message(r.message);
        } else {
          app_show_failure_message(r.message);
        }
      },
      error: function(){ app_show_failure_message('เกิดข้อผิดพลาด'); }
    });
  });
});

function testMessagingApi() {
  $('#msg-api-result').html('<i>กำลังทดสอบ...</i>');
  $.post('<?= get_uri('liff_settings/test_line_messaging_api') ?>', function(r){
    $('#msg-api-result').html(r.message);
  });
}

function testLoginChannel() {
  $('#liff-test-result').html('<i>กำลังตรวจสอบ...</i>');
  $.post('<?= get_uri('liff_settings/test_line_login_channel') ?>', function(r){
    $('#liff-test-result').html(r.message);
  });
}
</script>
