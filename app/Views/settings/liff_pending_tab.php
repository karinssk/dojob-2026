<?php
$pending_items = model('App\Models\Liff_pending_model')
    ->get_details(['status' => 'pending'])->getResult();
?>

<div id="pending-list">
<?php if (empty($pending_items)): ?>
<div style="text-align:center;padding:40px;color:#94A3B8">
  <div style="font-size:32px;margin-bottom:8px">✅</div>
  <p>ไม่มีคำขอรออนุมัติ</p>
</div>
<?php else: ?>
<table class="table table-hover" style="font-size:14px">
  <thead>
    <tr>
      <th>LINE UID</th>
      <th>ชื่อ LINE</th>
      <th>ต้องการเชื่อมกับ</th>
      <th>วันที่ขอ</th>
      <th>การดำเนินการ</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($pending_items as $p): ?>
    <tr id="row-<?= $p->id ?>">
      <td style="font-size:11px;word-break:break-all;max-width:120px"><?= esc($p->line_uid) ?></td>
      <td>
        <strong><?= esc($p->line_display_name) ?></strong>
      </td>
      <td><?= esc($p->rise_user_name ?: '—') ?></td>
      <td style="white-space:nowrap"><?= date('d/m/Y H:i', strtotime($p->created_at)) ?></td>
      <td>
        <button class="btn btn-success btn-sm" onclick="approveUser(<?= $p->id ?>)">✅ อนุมัติ</button>
        <button class="btn btn-danger btn-sm" onclick="rejectUser(<?= $p->id ?>)" style="margin-left:4px">❌ ปฏิเสธ</button>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
</div>

<script>
function approveUser(id) {
  if (!confirm('อนุมัติคำขอนี้?')) return;
  $.post('<?= get_uri('liff_settings/approve_line_liff_user') ?>', { id },
    function(r) {
      if (r.success) {
        $('#row-' + id).fadeOut(300, function(){ $(this).remove(); });
        appAlert.success(r.message);
        updatePendingBadge();
      } else {
        appAlert.error(r.message);
      }
    }
  );
}

function rejectUser(id) {
  var note = prompt('เหตุผลในการปฏิเสธ (ไม่บังคับ):') ?? '';
  $.post('<?= get_uri('liff_settings/reject_line_liff_user') ?>', { id, note },
    function(r) {
      if (r.success) {
        $('#row-' + id).fadeOut(300, function(){ $(this).remove(); });
        appAlert.success(r.message);
        updatePendingBadge();
      } else {
        appAlert.error(r.message);
      }
    }
  );
}

function updatePendingBadge() {
  $.get('<?= get_uri('liff_settings/liff_pending_count') ?>', function(r){
    if (r.count > 0) {
      $('.badge.bg-danger').text(r.count).show();
    } else {
      $('.badge.bg-danger').hide();
    }
  });
}
</script>
