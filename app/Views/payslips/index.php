<div class="card">
    <div class="page-title clearfix">
        <h4><?php echo app_lang('payslips'); ?></h4>
        <div class="title-button-group">
            <a href="<?= get_uri('payslips/listEmployees') ?>" class="btn btn-default">
                <i data-feather="users" class="icon-16"></i> Manage Employees
            </a>
            <a href="<?= get_uri('payslips/create') ?>" class="btn btn-primary">
                <i data-feather="plus-circle" class="icon-16"></i> <?php echo app_lang('create_payslip'); ?>
            </a>
        </div>
    </div>
    
    <div class="card-body">
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success">
                <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger">
                <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <?php if (empty($payslips)): ?>
            <div class="text-center p-4">
                <i data-feather="file-text" class="icon-48 text-muted mb-3"></i>
                <h5 class="text-muted">No payslips found</h5>
                <p class="text-muted">Create your first payslip to get started.</p>
                <a href="<?= get_uri('payslips/create') ?>" class="btn btn-primary">
                    <i data-feather="plus-circle" class="icon-16"></i> Create Payslip
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?php echo app_lang('employee_name'); ?></th>
                            <th><?php echo app_lang('salary_period'); ?></th>
                            <th><?php echo app_lang('net_pay'); ?></th>
                            <th><?php echo app_lang('payment_date'); ?></th>
                            <th>Remark | หมายเหตุ</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payslips as $p): ?>
                            <tr>
                                <td><?= $p['id'] ?></td>
                                <td><?= $p['employee_name'] ?? 'N/A' ?></td>
                                <td><?= $p['salary_period'] ?></td>
                                <td>฿<?= number_format($p['netpay'], 2) ?></td>
                                <td><?= date('M d, Y', strtotime($p['payment_date'])) ?></td>
                                <td>
                                    <?php if ($p['remark']): ?>
                                        <span class="badge badge-info" title="<?= htmlspecialchars($p['remark']) ?>">
                                            <?= strlen($p['remark']) > 30 ? substr($p['remark'], 0, 30) . '...' : $p['remark'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="<?= get_uri('payslips/view/' . $p['id']) ?>" 
                                           class="btn btn-sm btn-outline-primary" title="View">
                                            <i data-feather="eye" class="icon-14"></i>
                                        </a>
                                        <a href="<?= get_uri('payslips/print/' . $p['id']) ?>" 
                                           class="btn btn-sm btn-outline-success" title="Print" target="_blank">
                                            <i data-feather="printer" class="icon-14"></i>
                                        </a>
                                        <a href="<?= get_uri('payslips/delete/' . $p['id']) ?>" 
                                           class="btn btn-sm btn-outline-danger" title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this payslip?')">
                                            <i data-feather="trash-2" class="icon-14"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        feather.replace();
    });
</script>
