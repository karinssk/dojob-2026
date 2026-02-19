<?php echo form_open(get_uri('liff_settings/save_liff_credentials'), ['id' => 'liff-cred-form', 'class' => 'general-form', 'role' => 'form']); ?>

<!-- ── Messaging API (LIFF) ──────────────────────────────────────── -->
<h5 style="margin-bottom:12px;color:#64748B;font-size:13px;text-transform:uppercase;letter-spacing:.05em">
  Messaging API (LIFF Bot)
</h5>
<div class="row">
  <div class="col-md-6">
    <div class="form-group">
      <label class="control-label">Channel Access Token</label>
      <input class="form-control" type="text" name="liff_line_channel_access_token" autocomplete="off"
        value="<?= esc($liff_line_channel_access_token ?? '') ?>"
        placeholder="Channel Access Token จาก LINE Developers Console">
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-group">
      <label class="control-label">Channel Secret</label>
      <input class="form-control" type="text" name="liff_line_channel_secret" autocomplete="off"
        value="<?= esc($liff_line_channel_secret ?? '') ?>"
        placeholder="Channel Secret จาก LINE Developers Console">
    </div>
  </div>
</div>
<div class="row">
  <div class="col-md-12">
    <button type="button" class="btn btn-default btn-sm" onclick="testMessagingApi()">
      ทดสอบการเชื่อมต่อ Messaging API
    </button>
    <span id="msg-api-result" style="margin-left:10px;font-size:13px"></span>
    <div id="msg-api-log" style="display:none;margin-top:8px;background:#0F172A;color:#E2E8F0;padding:10px;border-radius:8px;font-size:12px;white-space:pre-wrap;word-break:break-all"></div>
  </div>
</div>

<hr style="margin:20px 0">

<!-- ── LIFF Webhook (Rooms) ───────────────────────────────────────── -->
<h5 style="margin-bottom:12px;color:#64748B;font-size:13px;text-transform:uppercase;letter-spacing:.05em">
  LIFF Webhook สำหรับดึงชื่อห้อง
</h5>
<div class="row">
  <div class="col-md-12">
    <div class="form-group">
      <label class="control-label">Webhook URL</label>
      <input class="form-control" type="text" readonly
        value="<?= get_uri('liff/line_webhook') ?>">
      <p class="help-block">
        เพิ่ม URL นี้ใน LINE Developers (LIFF Bot) แล้วให้บอทเข้าห้อง/กลุ่ม จากนั้นส่งข้อความ 1 ครั้ง เพื่อให้ระบบดึงชื่อห้อง
      </p>
    </div>
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
      <input class="form-control" type="text" name="line_login_channel_secret" autocomplete="off"
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
        ตรวจสอบค่า LIFF
      </button>
      <span id="liff-test-result" style="margin-left:10px;font-size:13px"></span>
      <div id="liff-test-log" style="display:none;margin-top:8px;background:#0F172A;color:#E2E8F0;padding:10px;border-radius:8px;font-size:12px;white-space:pre-wrap;word-break:break-all"></div>
      <div style="margin-top:8px;font-size:12px;color:#94A3B8">
        LIFF URL: <code><?= get_uri('liff') ?></code>
      </div>
    </div>
  </div>
</div>

<hr style="margin:20px 0">

<!-- ── LIFF Notification Mode & Rooms ───────────────────────────── -->
<h5 style="margin-bottom:12px;color:#64748B;font-size:13px;text-transform:uppercase;letter-spacing:.05em">
  LIFF Notification Mode
</h5>
<div class="row">
  <div class="col-md-12">
    <div class="form-group">
      <label class="control-label">โหมดการแจ้งเตือน</label>
      <div class="radio">
        <label>
          <input type="radio" name="liff_notify_mode" value="user" <?= ($liff_notify_mode ?? 'user') === 'user' ? 'checked' : '' ?>>
          ส่งตรงถึงผู้ใช้ (ควบคุมได้ในแท็บ “อนุมัติแล้ว”)
        </label>
      </div>
      <div class="radio">
        <label>
          <input type="radio" name="liff_notify_mode" value="room" <?= ($liff_notify_mode ?? '') === 'room' ? 'checked' : '' ?>>
          ส่งไปยังห้อง/กลุ่ม (เลือกด้านล่าง)
        </label>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="form-group">
      <label class="control-label">ห้อง/กลุ่มที่ใช้แจ้งเตือน (Global)</label>
      <?php $rooms = $liff_line_rooms ?? []; $selected_rooms = $liff_notify_rooms ?? []; ?>
      <?php if (empty($rooms)): ?>
        <div class="alert alert-warning" style="margin-top:6px">
          ยังไม่พบห้อง/กลุ่มจาก Webhook (ตรวจสอบการตั้งค่า Webhook และส่งข้อความในห้องเพื่อดึงชื่อ)
        </div>
      <?php else: ?>
        <div style="display:flex;flex-wrap:wrap;gap:10px">
          <?php foreach ($rooms as $r): ?>
            <?php $rid = $r['id'] ?? ''; $rname = $r['name'] ?? $rid; $rtype = $r['type'] ?? 'room'; ?>
            <label class="checkbox-inline" style="margin-right:12px">
              <input type="checkbox" name="liff_notify_rooms[]" value="<?= esc($rid) ?>"
                <?= in_array($rid, $selected_rooms ?? [], true) ? 'checked' : '' ?>>
              <?= esc($rname) ?> <small style="color:#94A3B8">(<?= esc($rtype) ?>)</small>
            </label>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
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
    <button type="submit" class="btn btn-primary">บันทึกการตั้งค่า</button>
  </div>
</div>

<?php echo form_close(); ?>

<script>
$(document).ready(function(){
  $('#liff-cred-form').on('submit', function(e){
    e.preventDefault();
    var formData = $(this).serialize();
    var formObj  = {};
    $(this).serializeArray().forEach(function(f){ formObj[f.name] = f.value; });
    console.log('[LIFF Cred] POST URL:', $(this).attr('action'));
    console.log('[LIFF Cred] Form data object:', formObj);
    console.log('[LIFF Cred] Serialized:', formData);
    $.ajax({
      url: $(this).attr('action'),
      method: 'POST',
      data: formData,
      success: function(r){
        console.log('[LIFF Cred] Response:', r);
        if (r.success) {
          appAlert.success(r.message);
        } else {
          appAlert.error(r.message);
        }
      },
      error: function(xhr){ console.error('[LIFF Cred] AJAX error:', xhr.status, xhr.responseText); appAlert.error('เกิดข้อผิดพลาด'); }
    });
  });
});

function testMessagingApi() {
  $('#msg-api-result').html('<i>กำลังทดสอบ...</i>');
  $('#msg-api-log').hide().text('');
  $.ajax({
    url: '<?= get_uri('liff_settings/test_line_messaging_api') ?>',
    method: 'POST',
    dataType: 'json',
    success: function(r){
      $('#msg-api-result').html(r.message);
      renderDebugLog('#msg-api-log', r.debug);
    },
    error: function(xhr){
      $('#msg-api-result').html('❌ AJAX error');
      renderDebugLog('#msg-api-log', { status: xhr.status, response: xhr.responseText });
    }
  });
}

function testLoginChannel() {
  $('#liff-test-result').html('<i>กำลังตรวจสอบ...</i>');
  $('#liff-test-log').hide().text('');
  $.ajax({
    url: '<?= get_uri('liff_settings/test_line_login_channel') ?>',
    method: 'POST',
    dataType: 'json',
    success: function(r){
      $('#liff-test-result').html(r.message);
      renderDebugLog('#liff-test-log', r.debug);
    },
    error: function(xhr){
      $('#liff-test-result').html('❌ AJAX error');
      renderDebugLog('#liff-test-log', { status: xhr.status, response: xhr.responseText });
    }
  });
}

function renderDebugLog(target, data) {
  if (!data) { return; }
  var text = JSON.stringify(data, null, 2);
  $(target).text(text).show();
}
</script>
