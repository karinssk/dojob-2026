<?php
// Settings loaded by controller
$r_enabled  = ($liff_reminder_enabled  ?? '0') === '1';
$r_times    = json_decode(get_setting('liff_reminder_times')  ?: '["09:00","15:00"]', true) ?: ['09:00', '15:00'];
$r_repeat   = ($liff_reminder_repeat   ?? '1') === '1';
$r_days     = json_decode(get_setting('liff_reminder_days')   ?: '[1,2,3,4,5]', true) ?: [1,2,3,4,5];

$s_enabled  = ($liff_summary_enabled   ?? '0') === '1';
$s_time     = $liff_summary_time       ?? '08:00';
$s_days     = json_decode(get_setting('liff_summary_days')    ?: '[1,2,3,4,5]', true) ?: [1,2,3,4,5];

$day_labels = [1=>'จ.',2=>'อ.',3=>'พ.',4=>'พฤ.',5=>'ศ.',6=>'ส.',7=>'อา.'];
?>

<!-- ═══════════════════════════════════════════════════════════
     LINE QUOTA BADGE (top-right)
═══════════════════════════════════════════════════════════ -->
<div style="display:flex;justify-content:flex-end;margin-bottom:16px">
  <div id="lnf-quota-box"
    style="display:inline-flex;align-items:center;gap:10px;
           background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;
           padding:10px 16px;font-size:13px;color:#334155;min-width:260px">
    <span style="font-size:18px">📨</span>
    <div id="lnf-quota-inner" style="flex:1">
      <div style="font-weight:600;color:#64748B;font-size:11px;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px">
        LINE Push Message Quota
      </div>
      <div id="lnf-quota-text" style="color:#94A3B8;font-size:12px">กำลังโหลด...</div>
    </div>
    <button type="button" onclick="loadQuota()"
      title="รีเฟรช"
      style="background:none;border:none;cursor:pointer;color:#94A3B8;font-size:16px;padding:0;line-height:1">
      ↻
    </button>
  </div>
</div>

<form id="liff-notify-form">

<!-- ═══════════════════════════════════════════════════════════
     SECTION 1 — แจ้งเตือนงานค้าง
═══════════════════════════════════════════════════════════ -->
<div class="panel panel-default" style="border:1px solid #E2E8F0;border-radius:10px;margin-bottom:24px">
  <div class="panel-heading" style="background:#F8FAFC;border-radius:10px 10px 0 0;padding:14px 18px;border-bottom:1px solid #E2E8F0">
    <h5 style="margin:0;font-size:14px;font-weight:600;color:#1E293B">
      🔔 แจ้งเตือนงานที่ยังไม่เสร็จ
    </h5>
    <p style="margin:4px 0 0;font-size:12px;color:#64748B">
      ส่งสรุปงานค้างแบ่งตามชื่อผู้รับผิดชอบ (ข้ามผู้ใช้ที่ไม่มีงานค้าง)
    </p>
  </div>
  <div class="panel-body" style="padding:18px">

    <!-- Enable toggle -->
    <div class="form-group" style="margin-bottom:16px">
      <label style="font-size:13px;font-weight:600;color:#374151">เปิดใช้งาน</label>
      <div>
        <label class="lnf-toggle">
          <input type="checkbox" name="liff_reminder_enabled" value="1" id="reminderEnabled"
            <?= $r_enabled ? 'checked' : '' ?>>
          <span class="lnf-slider"></span>
        </label>
      </div>
    </div>

    <!-- Time slots -->
    <div class="form-group" style="margin-bottom:16px">
      <label style="font-size:13px;font-weight:600;color:#374151">เวลาที่ส่ง</label>
      <div id="reminderTimeList" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:8px">
        <?php foreach ($r_times as $i => $t): ?>
        <div class="lnf-time-chip">
          <input type="time" name="liff_reminder_times[]" value="<?= esc($t) ?>"
            class="lnf-time-input">
          <button type="button" class="lnf-chip-rm" onclick="rmChip(this)">×</button>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn btn-xs btn-default" onclick="addTimeChip('reminderTimeList','liff_reminder_times[]')">
        + เพิ่มเวลา
      </button>
    </div>

    <!-- Repeat days -->
    <div class="form-group" style="margin-bottom:16px">
      <label style="font-size:13px;font-weight:600;color:#374151">
        <input type="checkbox" name="liff_reminder_repeat" value="1" id="reminderRepeat"
          <?= $r_repeat ? 'checked' : '' ?>>
        ส่งซ้ำทุกสัปดาห์ (เลือกวัน)
      </label>
      <div id="reminderDays" style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap">
        <?php foreach ($day_labels as $num => $label): ?>
        <label class="lnf-day-btn">
          <input type="checkbox" name="liff_reminder_days[]" value="<?= $num ?>"
            <?= in_array($num, $r_days) ? 'checked' : '' ?>>
          <span><?= $label ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Test button -->
    <div style="margin-top:8px">
      <button type="button" class="btn btn-sm btn-default" onclick="testNotify('reminder')">
        ส่งทดสอบแจ้งเตือนงานค้าง
      </button>
      <span id="test-reminder-result" style="margin-left:10px;font-size:13px"></span>
    </div>

  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     SECTION 2 — สรุปงานที่เสร็จ (รายสัปดาห์)
═══════════════════════════════════════════════════════════ -->
<div class="panel panel-default" style="border:1px solid #E2E8F0;border-radius:10px;margin-bottom:24px">
  <div class="panel-heading" style="background:#F8FAFC;border-radius:10px 10px 0 0;padding:14px 18px;border-bottom:1px solid #E2E8F0">
    <h5 style="margin:0;font-size:14px;font-weight:600;color:#1E293B">
      📊 รายงานสรุปงานเสร็จ (7 วันย้อนหลัง)
    </h5>
    <p style="margin:4px 0 0;font-size:12px;color:#64748B">
      นับงานที่เสร็จในช่วง 7 วันที่ผ่านมา — ไม่ส่งถ้าไม่มีงานเสร็จเลย
    </p>
  </div>
  <div class="panel-body" style="padding:18px">

    <!-- Enable toggle -->
    <div class="form-group" style="margin-bottom:16px">
      <label style="font-size:13px;font-weight:600;color:#374151">เปิดใช้งาน</label>
      <div>
        <label class="lnf-toggle">
          <input type="checkbox" name="liff_summary_enabled" value="1" id="summaryEnabled"
            <?= $s_enabled ? 'checked' : '' ?>>
          <span class="lnf-slider"></span>
        </label>
      </div>
    </div>

    <!-- Time -->
    <div class="form-group" style="margin-bottom:16px">
      <label style="font-size:13px;font-weight:600;color:#374151">เวลาที่ส่ง</label>
      <div>
        <input type="time" name="liff_summary_time" value="<?= esc($s_time) ?>"
          class="lnf-time-input" style="border:1px solid #CBD5E1">
      </div>
    </div>

    <!-- Repeat days -->
    <div class="form-group" style="margin-bottom:16px">
      <label style="font-size:13px;font-weight:600;color:#374151">ส่งทุกวัน</label>
      <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap">
        <?php foreach ($day_labels as $num => $label): ?>
        <label class="lnf-day-btn">
          <input type="checkbox" name="liff_summary_days[]" value="<?= $num ?>"
            <?= in_array($num, $s_days) ? 'checked' : '' ?>>
          <span><?= $label ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Test button -->
    <div style="margin-top:8px">
      <button type="button" class="btn btn-sm btn-default" onclick="testNotify('summary')">
        ส่งทดสอบรายงานสรุป
      </button>
      <span id="test-summary-result" style="margin-left:10px;font-size:13px"></span>
    </div>

  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     SECTION 3 — Fallback LINE Bot
═══════════════════════════════════════════════════════════ -->
<div class="panel panel-default" style="border:1px solid #FDE68A;border-radius:10px;margin-bottom:24px">
  <div class="panel-heading" style="background:#FFFBEB;border-radius:10px 10px 0 0;padding:14px 18px;border-bottom:1px solid #FDE68A">
    <h5 style="margin:0;font-size:14px;font-weight:600;color:#92400E">
      🔄 Fallback LINE Bot (สำรอง)
    </h5>
    <p style="margin:4px 0 0;font-size:12px;color:#B45309">
      ใช้เมื่อ LIFF Bot หลักส่งไม่สำเร็จ (เช่น Quota เต็ม / Token ผิด) — ส่งข้อความธรรมดาไปยังห้องที่กำหนด
    </p>
  </div>
  <div class="panel-body" style="padding:18px">

    <div class="row">
      <div class="col-md-6">
        <div class="form-group" style="margin-bottom:14px">
          <label style="font-size:13px;font-weight:600;color:#374151">
            Fallback Channel Access Token
          </label>
          <input type="text" name="liff_fallback_token"
            class="form-control" autocomplete="off"
            value="<?= esc(get_setting('liff_fallback_token') ?: get_setting('line_channel_access_token') ?: '') ?>"
            placeholder="Token ของ LINE Bot สำรอง (ถ้าว่างจะใช้ line_channel_access_token)">
          <p class="help-block" style="font-size:11px">ถ้าว่าง จะใช้ <code>line_channel_access_token</code> อัตโนมัติ</p>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group" style="margin-bottom:14px">
          <label style="font-size:13px;font-weight:600;color:#374151">
            Fallback Room / Group ID
          </label>
          <input type="text" name="liff_fallback_room_id"
            class="form-control" autocomplete="off"
            value="<?= esc(get_setting('liff_fallback_room_id') ?: get_setting('line_default_room_id') ?: '') ?>"
            placeholder="Room ID หรือ Group ID เช่น C7a110af...">
          <p class="help-block" style="font-size:11px">ถ้าว่าง จะใช้ <code>line_default_room_id</code> อัตโนมัติ</p>
        </div>
      </div>
    </div>

    <!-- Test fallback -->
    <div style="margin-top:4px">
      <button type="button" class="btn btn-sm btn-warning" onclick="testFallback()">
        ทดสอบ Fallback Bot
      </button>
      <span id="test-fallback-result" style="margin-left:10px;font-size:13px"></span>
    </div>

  </div>
</div>

<!-- Save -->
<div style="margin-top:4px">
  <button type="submit" class="btn btn-primary">บันทึกการตั้งค่า</button>
  <span id="lnf-save-result" style="margin-left:12px;font-size:13px"></span>
</div>

</form>

<!-- ─── Styles ───────────────────────────────────────────────────── -->
<style>
/* Toggle switch */
.lnf-toggle { position:relative;display:inline-block;width:44px;height:24px;cursor:pointer }
.lnf-toggle input { opacity:0;width:0;height:0 }
.lnf-slider {
  position:absolute;inset:0;background:#CBD5E1;border-radius:999px;transition:.2s;
}
.lnf-toggle input:checked + .lnf-slider { background:#4F7DF3; }
.lnf-slider:before {
  content:'';position:absolute;height:18px;width:18px;left:3px;bottom:3px;
  background:#fff;border-radius:50%;transition:.2s;
}
.lnf-toggle input:checked + .lnf-slider:before { transform:translateX(20px); }

/* Time chip */
.lnf-time-chip {
  display:inline-flex;align-items:center;gap:4px;
  background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:4px 8px;
}
.lnf-time-input {
  border:none;background:transparent;font-size:14px;color:#1E3A8A;width:90px;outline:none;
}
.lnf-chip-rm {
  background:none;border:none;color:#94A3B8;font-size:16px;line-height:1;cursor:pointer;padding:0 2px;
}
.lnf-chip-rm:hover { color:#EF4444; }

/* Day pill buttons */
.lnf-day-btn { cursor:pointer;margin:0 }
.lnf-day-btn input { display:none }
.lnf-day-btn span {
  display:inline-block;min-width:36px;padding:4px 8px;border-radius:6px;font-size:12px;
  text-align:center;border:1px solid #CBD5E1;background:#F8FAFC;color:#64748B;
  transition:.15s;
}
.lnf-day-btn input:checked + span {
  background:#4F7DF3;border-color:#4F7DF3;color:#fff;font-weight:600;
}
</style>

<!-- ─── JS ───────────────────────────────────────────────────────── -->
<script>
(function(){
  // Save form
  document.querySelector('#liff-notify-form').addEventListener('submit', function(e){
    e.preventDefault();
    var fd = new FormData(this);
    var data = {};
    fd.forEach(function(v,k){
      if (k.endsWith('[]')) {
        var key = k.slice(0,-2);
        if (!data[key]) data[key] = [];
        data[key].push(v);
      } else {
        data[k] = v;
      }
    });
    // Convert arrays to JSON for storage
    ['liff_reminder_times','liff_reminder_days','liff_summary_days'].forEach(function(k){
      data[k] = JSON.stringify(data[k] || []);
    });
    // Ensure checkboxes that are unchecked send 0
    ['liff_reminder_enabled','liff_reminder_repeat','liff_summary_enabled'].forEach(function(k){
      if (!data[k]) data[k] = '0';
    });

    var resultEl = document.getElementById('lnf-save-result');
    resultEl.innerHTML = '<i>กำลังบันทึก...</i>';

    $.ajax({
      url: '<?= get_uri('liff_settings/save_liff_notification_settings') ?>',
      method: 'POST',
      data: data,
      dataType: 'json',
      success: function(r){
        resultEl.innerHTML = r.success ? '✅ ' + r.message : '❌ ' + r.message;
        setTimeout(function(){ resultEl.innerHTML = ''; }, 4000);
      },
      error: function(){ resultEl.innerHTML = '❌ เกิดข้อผิดพลาด'; }
    });
  });
})();

function addTimeChip(listId, fieldName) {
  var container = document.getElementById(listId);
  var chip = document.createElement('div');
  chip.className = 'lnf-time-chip';
  chip.innerHTML =
    '<input type="time" name="' + fieldName + '" value="12:00" class="lnf-time-input">' +
    '<button type="button" class="lnf-chip-rm" onclick="rmChip(this)">×</button>';
  container.appendChild(chip);
}

function rmChip(btn) {
  var chip = btn.closest('.lnf-time-chip');
  var list = chip.parentElement;
  if (list.querySelectorAll('.lnf-time-chip').length > 1) {
    chip.remove();
  } else {
    appAlert.error('ต้องมีเวลาอย่างน้อย 1 รายการ');
  }
}

function testNotify(type) {
  var resultEl = document.getElementById('test-' + type + '-result');
  resultEl.innerHTML = '<i>กำลังส่ง...</i>';
  $.ajax({
    url: '<?= get_uri('liff_settings/test_liff_notification') ?>',
    method: 'POST',
    data: { type: type },
    dataType: 'json',
    success: function(r){
      resultEl.innerHTML = (r.success ? '✅ ' : '❌ ') + r.message;
      setTimeout(function(){ resultEl.innerHTML = ''; }, 6000);
      // Refresh quota after sending test
      setTimeout(loadQuota, 1500);
    },
    error: function(xhr){
      resultEl.innerHTML = '❌ AJAX error (' + xhr.status + ')';
    }
  });
}

// ── LINE Push Quota ──────────────────────────────────────────────
function loadQuota() {
  var el = document.getElementById('lnf-quota-text');
  var box = document.getElementById('lnf-quota-box');
  el.style.color = '#94A3B8';
  el.innerHTML = 'กำลังโหลด...';

  $.ajax({
    url: '<?= get_uri('liff_settings/get_push_quota') ?>',
    method: 'GET',
    dataType: 'json',
    success: function(r) {
      if (!r.success) {
        el.innerHTML = '<span style="color:#EF4444">❌ ' + r.message + '</span>';
        box.style.borderColor = '#FECACA';
        return;
      }
      var d = r.data;
      // d.type: 'limited' | 'unlimited'
      if (d.type === 'unlimited') {
        el.innerHTML = '♾️ ไม่จำกัด (Verified account)';
        el.style.color = '#00B393';
        box.style.borderColor = '#99F6E4';
      } else {
        var used     = d.totalUsage;
        var limit    = d.value;
        var remain   = limit - used;
        var pct      = limit > 0 ? Math.round(used / limit * 100) : 0;
        var barColor = pct >= 90 ? '#EF4444' : pct >= 70 ? '#F97316' : '#4F7DF3';
        var textColor= pct >= 90 ? '#EF4444' : pct >= 70 ? '#F97316' : '#334155';

        // Border color by usage
        box.style.borderColor = pct >= 90 ? '#FECACA' : pct >= 70 ? '#FED7AA' : '#BFDBFE';

        el.style.color = textColor;
        el.innerHTML =
          '<span style="font-size:14px;font-weight:700">' + used.toLocaleString() + '</span>' +
          '<span style="color:#94A3B8"> / ' + limit.toLocaleString() + ' messages</span>' +
          '<span style="color:' + barColor + ';font-weight:600;margin-left:6px">(' + pct + '%)</span>' +
          '<div style="margin-top:5px;height:5px;background:#E2E8F0;border-radius:3px;overflow:hidden">' +
            '<div style="width:' + Math.min(pct,100) + '%;height:100%;background:' + barColor + ';border-radius:3px;transition:width .4s"></div>' +
          '</div>' +
          '<div style="font-size:11px;color:#64748B;margin-top:3px">เหลือ ' + remain.toLocaleString() + ' messages · รีเซ็ตทุกต้นเดือน</div>';
      }
    },
    error: function(xhr) {
      el.innerHTML = '<span style="color:#EF4444">❌ ดึงข้อมูลไม่สำเร็จ (' + xhr.status + ')</span>';
    }
  });
}

// ── Fallback bot test ────────────────────────────────────────────
function testFallback() {
  var resultEl = document.getElementById('test-fallback-result');
  resultEl.innerHTML = '<i>กำลังส่ง...</i>';
  $.ajax({
    url: '<?= get_uri('liff_settings/test_liff_fallback') ?>',
    method: 'POST',
    dataType: 'json',
    success: function(r){
      resultEl.innerHTML = (r.success ? '✅ ' : '❌ ') + r.message;
      setTimeout(function(){ resultEl.innerHTML = ''; }, 6000);
    },
    error: function(xhr){
      resultEl.innerHTML = '❌ AJAX error (' + xhr.status + ')';
    }
  });
}

// Auto-load on tab open
$(document).ready(function(){ loadQuota(); });
</script>
