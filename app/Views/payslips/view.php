<div class="card">
    <div class="page-title clearfix">
        <h4><?php echo app_lang('view_payslip'); ?> #<?= $payslip['id'] ?> | ดูใบจ่ายเงินเดือน</h4>
        <div class="title-button-group">
            <a href="<?= get_uri('payslips') ?>" class="btn btn-default">
                <i data-feather="arrow-left" class="icon-16"></i> <?php echo app_lang('back'); ?> | กลับ
            </a>
            <button onclick="printPayslip()" class="btn btn-success">
                <i data-feather="printer" class="icon-16"></i> <?php echo app_lang('print_payslip'); ?> | พิมพ์
            </button>
        </div>
    </div>
    
    <div class="card-body">
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger">
                <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <h5><?php echo app_lang('payslip'); ?> Information | ข้อมูลใบจ่ายเงินเดือน</h5>
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Payslip ID | รหัสใบจ่าย:</strong></td>
                        <td><?= $payslip['id'] ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo app_lang('employee_name'); ?> | ชื่อพนักงาน:</strong></td>
                        <td><?= $payslip['employee_name'] ?? 'N/A' ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo app_lang('salary_period'); ?> | ระยะเวลาเงินเดือน:</strong></td>
                        <td><?= $payslip['salary_period'] ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo app_lang('payment_date'); ?> | วันที่จ่าย:</strong></td>
                        <td><?= date('M d, Y', strtotime($payslip['payment_date'])) ?> | <?= date('d/m/Y', strtotime($payslip['payment_date'])) ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo app_lang('year'); ?> | ปี:</strong></td>
                        <td><?= $payslip['year'] ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo app_lang('net_pay'); ?> | เงินสุทธิ:</strong></td>
                        <td class="text-success"><strong>฿<?= number_format($payslip['netpay'], 2) ?></strong></td>
                    </tr>
                    <?php if ($payslip['employee_signature']): ?>
                    <tr>
                        <td><strong>Employee Signature | ลายเซ็นพนักงาน:</strong></td>
                        <td><?= $payslip['employee_signature'] ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payslip['remark']): ?>
                    <tr>
                        <td><strong>Remark | หมายเหตุ:</strong></td>
                        <td><?= nl2br(htmlspecialchars($payslip['remark'])) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            
            <div class="col-md-6">
                <h5>Employee Details | รายละเอียดพนักงาน</h5>
                <table class="table table-borderless">
                    <?php if (isset($payslip['position']) && $payslip['position']): ?>
                    <tr>
                        <td><strong>Position | ตำแหน่ง:</strong></td>
                        <td><?= $payslip['position'] ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (isset($payslip['company_name']) && $payslip['company_name']): ?>
                    <tr>
                        <td><strong>Company | บริษัท:</strong></td>
                        <td><?= $payslip['company_name'] ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (isset($payslip['bank_account']) && $payslip['bank_account']): ?>
                    <tr>
                        <td><strong>Bank Account | บัญชีธนาคาร:</strong></td>
                        <td><?= $payslip['bank_account'] ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td><strong>Created | สร้างเมื่อ:</strong></td>
                        <td><?= date('M d, Y H:i', strtotime($payslip['created_at'])) ?><br><small class="text-muted"><?= date('d/m/Y H:i', strtotime($payslip['created_at'])) ?> น.</small></td>
                    </tr>
                    <tr>
                        <td><strong>Updated | อัปเดตเมื่อ:</strong></td>
                        <td><?= date('M d, Y H:i', strtotime($payslip['updated_at'])) ?><br><small class="text-muted"><?= date('d/m/Y H:i', strtotime($payslip['updated_at'])) ?> น.</small></td>
                    </tr>
                    <?php if (isset($loan_info) && $loan_info): ?>
                    <tr>
                        <td><strong>Current Loan | เงินกู้ปัจจุบัน:</strong></td>
                        <td>
                            <?php if ($loan_info['remaining_balance'] > 0): ?>
                                <span class="badge badge-warning">฿<?= number_format($loan_info['remaining_balance'], 2) ?></span>
                                <br><small class="text-muted">Repayment: <?= $loan_info['monthly_repayment_percentage'] ?>% | ชำระคืน: <?= $loan_info['monthly_repayment_percentage'] ?>%</small>
                            <?php else: ?>
                                <span class="text-muted">No active loan | ไม่มีเงินกู้</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col-md-6">
                <h5 class="text-success"><?php echo app_lang('earnings'); ?> | รายได้</h5>
                <?php if ($earnings): ?>
                    <table class="table table-striped">
                        <?php if ($earnings['salary'] > 0): ?>
                        <tr>
                            <td><?php echo app_lang('salary'); ?> | เงินเดือน</td>
                            <td class="text-right">฿<?= number_format($earnings['salary'], 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($earnings['overtime'] > 0): ?>
                        <tr>
                            <td>Overtime | ค่าล่วงเวลา</td>
                            <td class="text-right">฿<?= number_format($earnings['overtime'], 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($earnings['commission'] > 0): ?>
                        <tr>
                            <td>Commission | คอมมิชชั่น</td>
                            <td class="text-right">฿<?= number_format($earnings['commission'], 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($earnings['allowance'] > 0): ?>
                        <tr>
                            <td>Allowance | ค่าเบี้ยเลี้ยง</td>
                            <td class="text-right">฿<?= number_format($earnings['allowance'], 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($earnings['bonus'] > 0): ?>
                        <tr>
                            <td>Bonus | โบนัส</td>
                            <td class="text-right">฿<?= number_format($earnings['bonus'], 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($earnings['earning_other'] > 0): ?>
                        <tr>
                            <td>Other Earnings | รายได้อื่นๆ</td>
                            <td class="text-right">฿<?= number_format($earnings['earning_other'], 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="table-success">
                            <td><strong><?php echo app_lang('total_earning'); ?> | รวมรายได้</strong></td>
                            <td class="text-right"><strong>฿<?= number_format($earnings['total_earning'], 2) ?></strong></td>
                        </tr>
                        <?php if (isset($earnings['ytd_earning']) && $earnings['ytd_earning'] > 0): ?>
                        <tr class="table-info">
                            <td>YTD Earnings | เงินได้สะสม</td>
                            <td class="text-right">฿<?= number_format($earnings['ytd_earning'], 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (isset($earnings['ytd-withholding-tax']) && $earnings['ytd-withholding-tax'] > 0): ?>
                        <tr class="table-info">
                            <td>YTD Withholding Tax | ภาษีหัก ณ ที่จ่ายสะสม</td>
                            <td class="text-right">฿<?= number_format($earnings['ytd-withholding-tax'], 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (isset($earnings['accumulated-ssf']) && $earnings['accumulated-ssf'] > 0): ?>
                        <tr class="table-info">
                            <td>Accumulated SSF | ประกันสังคมสะสม</td>
                            <td class="text-right">฿<?= number_format($earnings['accumulated-ssf'], 2) ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                <?php else: ?>
                    <p class="text-muted">No earnings data found. | ไม่พบข้อมูลรายได้</p>
                <?php endif; ?>
            </div>
            
            <div class="col-md-6">
                <h5 class="text-danger"><?php echo app_lang('deductions'); ?> | รายการหัก</h5>
                <?php if ($deductions): ?>
                    <table class="table table-striped">
                        <?php if ($deductions['tax'] > 0): ?>
                        <tr>
                            <td>Tax | ภาษีหัก ณ ที่จ่าย</td>
                            <td class="text-right">฿<?= number_format($deductions['tax'], 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($deductions['social_security'] > 0): ?>
                        <tr>
                            <td>Social Security | ประกันสังคม</td>
                            <td class="text-right">฿<?= number_format($deductions['social_security'], 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (isset($deductions['provident_fund']) && $deductions['provident_fund'] > 0): ?>
                        <tr>
                            <td>Provident Fund | กองทุนสำรองเลี้ยงชีพ</td>
                            <td class="text-right">฿<?= number_format($deductions['provident_fund'], 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (isset($deductions['employee-welfare-fund']) && $deductions['employee-welfare-fund'] > 0): ?>
                        <tr>
                            <td>Employee Welfare Fund | กองทุนสวัสดิการพนักงาน</td>
                            <td class="text-right">฿<?= number_format($deductions['employee-welfare-fund'], 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (isset($deductions['advance-deduction']) && $deductions['advance-deduction'] > 0): ?>
                        <tr>
                            <td>Advance Deduction | หักเงินเบิกล่วงหน้า</td>
                            <td class="text-right">฿<?= number_format($deductions['advance-deduction'], 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (isset($deductions['loan_repayment']) && $deductions['loan_repayment'] > 0): ?>
                        <tr>
                            <td>Loan Repayment | ชำระคืนเงินกู้บริษัท</td>
                            <td class="text-right">฿<?= number_format($deductions['loan_repayment'], 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($deductions['deduction_other'] > 0): ?>
                        <tr>
                            <td>Other Deductions | รายการหักอื่นๆ</td>
                            <td class="text-right">฿<?= number_format($deductions['deduction_other'], 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="table-danger">
                            <td><strong><?php echo app_lang('total_deduction'); ?> | รวมรายการหัก</strong></td>
                            <td class="text-right"><strong>฿<?= number_format($deductions['total_deduction'], 2) ?></strong></td>
                        </tr>
                    </table>
                <?php else: ?>
                    <p class="text-muted">No deductions data found. | ไม่พบข้อมูลรายการหัก</p>
                <?php endif; ?>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-info">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Total Earnings | รวมรายได้:</strong><br>
                            <span class="h5 text-success">฿<?= number_format($earnings['total_earning'] ?? 0, 2) ?></span>
                        </div>
                        <div class="col-md-4">
                            <strong>Total Deductions | รวมรายการหัก:</strong><br>
                            <span class="h5 text-danger">฿<?= number_format($deductions['total_deduction'] ?? 0, 2) ?></span>
                        </div>
                        <div class="col-md-4">
                            <strong><?php echo app_lang('net_pay'); ?> | เงินสุทธิ:</strong><br>
                            <span class="h4 text-primary"><strong>฿<?= number_format($payslip['netpay'], 2) ?></strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-12">
                <div class="btn-group" role="group">
                    <a href="<?= get_uri('payslips') ?>" class="btn btn-default">
                        <i data-feather="list" class="icon-16"></i> Back to List | กลับไปรายการ
                    </a>
                    <button onclick="printPayslip()" class="btn btn-success">
                        <i data-feather="printer" class="icon-16"></i> Print | พิมพ์
                    </button>
                    <a href="<?= get_uri('payslips/pdf/' . $payslip['id']) ?>" class="btn btn-info" onclick="downloadPDF(event)">
                        <i data-feather="download" class="icon-16"></i> Download PDF | ดาวน์โหลด PDF
                    </a>
                    <a href="<?= get_uri('payslips/delete/' . $payslip['id']) ?>" 
                       class="btn btn-danger"
                       onclick="return confirm('Are you sure you want to delete this payslip? | คุณแน่ใจหรือไม่ที่จะลบใบจ่ายเงินเดือนนี้?')">
                        <i data-feather="trash-2" class="icon-16"></i> Delete | ลบ
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function printPayslip() {
        // Open print page in new window and trigger print dialog
        var printWindow = window.open('<?= get_uri('payslips/print/' . $payslip['id']) ?>', '_blank');
        printWindow.onload = function() {
            printWindow.print();
        };
    }
    
    function downloadPDF(event) {
        event.preventDefault();
        
        // Show loading message
        var btn = event.target.closest('a');
        var originalText = btn.innerHTML;
        btn.innerHTML = '<i data-feather="loader" class="icon-16"></i> Generating PDF... | กำลังสร้าง PDF...';
        btn.disabled = true;
        
        // Create a temporary link to download PDF
        var link = document.createElement('a');
        link.href = '<?= get_uri('payslips/pdf/' . $payslip['id']) ?>';
        link.download = 'payslip_<?= $payslip['id'] ?>.pdf';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Reset button after 2 seconds
        setTimeout(function() {
            btn.innerHTML = originalText;
            btn.disabled = false;
            feather.replace();
        }, 2000);
    }
    
    $(document).ready(function () {
        feather.replace();
    });
</script>