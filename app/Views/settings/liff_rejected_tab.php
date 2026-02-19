<?php
$rejected_items = model('App\Models\Liff_pending_model')
    ->get_details(['status' => 'rejected'])->getResult();
?>

<?php if (empty($rejected_items)): ?>
<div style="text-align:center;padding:40px;color:#94A3B8">
  <div style="font-size:32px;margin-bottom:8px">📋</div>
  <p>ยังไม่มีคำขอที่ถูกปฏิเสธ</p>
</div>
<?php else: ?>
<table class="table table-hover" style="font-size:14px">
  <thead>
    <tr>
      <th>LINE UID</th>
      <th>ชื่อ LINE</th>
      <th>ต้องการเชื่อมกับ</th>
      <th>เหตุผล</th>
      <th>วันที่</th>
      <th>การดำเนินการ</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rejected_items as $p): ?>
    <tr id="row-<?= $p->id ?>">
      <td style="font-size:11px;word-break:break-all;max-width:120px"><?= esc($p->line_uid) ?></td>
      <td><?= esc($p->line_display_name) ?></td>
      <td><?= esc($p->rise_user_name ?: '—') ?></td>
      <td style="color:#C73060"><?= esc($p->rejection_note ?: '—') ?></td>
      <td style="white-space:nowrap"><?= date('d/m/Y H:i', strtotime($p->created_at)) ?></td>
      <td>
        <button class="btn btn-default btn-sm" onclick="reopenRequest(<?= $p->id ?>)">🔄 เปิดใหม่</button>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<script>
function reopenRequest(id) {
  $.post('<?= get_uri('liff_settings/reopen_line_liff_request') ?>', { id },
    function(r) {
      if (r.success) {
        appAlert.success(r.message);
        setTimeout(() => location.reload(), 1000);
      } else {
        appAlert.error(r.message);
      }
    }
  );
}
</script>
