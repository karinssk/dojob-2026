<?php
$approved_items = model('App\Models\Liff_pending_model')
    ->get_details(['status' => 'approved'])->getResult();
?>

<?php if (empty($approved_items)): ?>
<div style="text-align:center;padding:40px;color:#94A3B8">
  <div style="font-size:32px;margin-bottom:8px">📋</div>
  <p>ยังไม่มีผู้ใช้ที่ได้รับอนุมัติ</p>
</div>
<?php else: ?>
<table class="table table-hover" style="font-size:14px">
  <thead>
    <tr>
      <th>LINE UID</th>
      <th>ชื่อ LINE</th>
      <th>บัญชีระบบ</th>
      <th>อนุมัติโดย</th>
      <th>วันที่อนุมัติ</th>
      <th>การดำเนินการ</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($approved_items as $p): ?>
    <tr id="row-<?= $p->id ?>">
      <td style="font-size:11px;word-break:break-all;max-width:120px"><?= esc($p->line_uid) ?></td>
      <td><?= esc($p->line_display_name) ?></td>
      <td><?= esc($p->rise_user_name ?: '—') ?></td>
      <td><?= esc($p->approver_name ?: '—') ?></td>
      <td style="white-space:nowrap">
        <?= $p->approved_at ? date('d/m/Y H:i', strtotime($p->approved_at)) : '—' ?>
      </td>
      <td>
        <button class="btn btn-warning btn-sm" onclick="revokeUser(<?= $p->id ?>)">🔒 ถอนสิทธิ์</button>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<script>
function revokeUser(id) {
  if (!confirm('ถอนสิทธิ์การเข้าถึง LIFF ของผู้ใช้นี้?')) return;
  $.post('<?= get_uri('liff_settings/revoke_line_liff_user') ?>', { id },
    function(r) {
      if (r.success) {
        app_show_success_message(r.message);
        setTimeout(() => location.reload(), 1000);
      } else {
        app_show_failure_message(r.message);
      }
    }
  );
}
</script>
