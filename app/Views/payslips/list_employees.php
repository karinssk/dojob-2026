<div class="card">
    <div class="page-title clearfix">
        <h4>Employees</h4>
        <div class="title-button-group">
            <a href="<?= get_uri('payslips/createEmployees') ?>" class="btn btn-primary">
                <i data-feather="user-plus" class="icon-16"></i> Create New Employee
            </a>
            <a href="<?= get_uri('payslips') ?>" class="btn btn-default">
                <i data-feather="arrow-left" class="icon-16"></i> Back to Payslips
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

        <?php if (empty($employees)): ?>
            <div class="text-center p-4">
                <i data-feather="users" class="icon-48 text-muted mb-3"></i>
                <h5 class="text-muted">No employees found</h5>
                <p class="text-muted">Create your first employee to get started.</p>
                <a href="<?= get_uri('payslips/createEmployees') ?>" class="btn btn-primary">
                    <i data-feather="user-plus" class="icon-16"></i> Create Employee
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee Name</th>
                            <th>Position</th>
                            <th>Company</th>
                            <th>Bank Account</th>
                            <th>Current Loan</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td><?= $emp['id'] ?></td>
                                <td>
                                    <strong><?= $emp['name'] ?></strong>
                                    <?php if ($emp['full_name'] && $emp['full_name'] !== $emp['name']): ?>
                                        <br><small class="text-muted">(<?= $emp['full_name'] ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= $emp['position'] ?></td>
                                <td><?= $emp['company_name'] ?></td>
                                <td><?= $emp['bank_account'] ?: '<span class="text-muted">Not set</span>' ?></td>
                                <td>
                                    <?php if (isset($emp['loan_balance']) && $emp['loan_balance'] > 0): ?>
                                        <span class="badge badge-warning">฿<?= number_format($emp['loan_balance'], 2) ?></span>
                                        <br><small class="text-muted">Outstanding</small>
                                    <?php else: ?>
                                        <span class="text-muted">No active loan</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($emp['status'] === 'active'): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><?= ucfirst($emp['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="<?= get_uri('payslips/create?employee=' . $emp['id']) ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Create Payslip">
                                            <i data-feather="file-plus" class="icon-14"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-info" 
                                                onclick="editEmployeeLoan(<?= $emp['id'] ?>, '<?= addslashes($emp['name']) ?>', <?= $emp['loan_balance'] ?? 0 ?>, '<?= addslashes($emp['position'] ?? '') ?>', '<?= addslashes($emp['company_name'] ?? '') ?>', '<?= addslashes($emp['bank_account'] ?? '') ?>', <?= $emp['monthly_repayment_percentage'] ?? 10 ?>, '<?= addslashes($emp['loan_notes'] ?? '') ?>')" 
                                                title="Edit Employee Details & Loan">
                                            <i data-feather="edit" class="icon-14"></i>
                                        </button>
                                        <a href="<?= get_uri('payslips/deleteEmployee/' . $emp['id']) ?>" 
                                           class="btn btn-sm btn-outline-danger" title="Delete Employee"
                                           onclick="return confirm('Are you sure you want to delete this employee? This action cannot be undone.')">
                                            <i data-feather="trash-2" class="icon-14"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

           <div class="mt-3">
    <div class="alert alert-info">
        <h6>Quick Actions:</h6>
        <ul class="mb-0">
            <li><strong>สร้างสลิปเงินเดือน:</strong> คลิกไอคอน <i data-feather="file-plus" class="icon-14"></i> เพื่อสร้างสลิปเงินเดือนให้พนักงาน</li>
            <li><strong>แก้ไขข้อมูล & เงินกู้:</strong> คลิกไอคอน <i data-feather="edit" class="icon-14"></i> เพื่อแก้ไขข้อมูลพนักงานและจัดการเงินกู้</li>
            <li><strong>ลบพนักงาน:</strong> คลิกไอคอน <i data-feather="trash-2" class="icon-14"></i> เพื่อลบพนักงาน (สามารถลบได้เฉพาะในกรณีที่ยังไม่มีสลิปเงินเดือน)</li>
        </ul>
    </div>
</div>

        <?php endif; ?>
    </div>
</div>

<!-- Employee Loan Edit Modal -->
<div class="modal fade" id="employeeLoanModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Employee Details & Loan | แก้ไขข้อมูลพนักงานและเงินกู้</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="employeeLoanForm" method="post" action="<?= base_url('payslips/updateEmployeeLoan') ?>">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <input type="hidden" id="employee_id" name="employee_id">
                    
                    <h6 class="text-primary mb-3">Employee Information | ข้อมูลพนักงาน</h6>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Employee Name | ชื่อพนักงาน</label>
                                <input type="text" id="employee_name" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="position">Position | ตำแหน่ง</label>
                                <input type="text" name="position" id="position" class="form-control" 
                                       placeholder="Enter position">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="company_name">Company Name | ชื่อบริษัท</label>
                                <input type="text" name="company_name" id="company_name" class="form-control" 
                                       placeholder="Enter company name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="bank_account">Bank Account | บัญชีธนาคาร</label>
                                <input type="text" name="bank_account" id="bank_account" class="form-control" 
                                       placeholder="Enter bank account number">
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <h6 class="text-warning mb-3">Loan Information | ข้อมูลเงินกู้</h6>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="loan_amount">Current Loan Amount (฿)<br><small class="text-muted">จำนวนเงินกู้ปัจจุบัน</small></label>
                                <input type="number" name="loan_amount" id="modal_loan_amount" class="form-control" 
                                       step="0.01" min="0" placeholder="Enter loan amount">
                                <small class="form-text text-muted">Enter 0 to remove/complete the loan</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="repayment_percentage">Monthly Repayment Percentage (%)<br><small class="text-muted">เปอร์เซ็นต์การชำระคืนรายเดือน</small></label>
                                <input type="number" name="repayment_percentage" id="repayment_percentage" class="form-control" 
                                       step="0.01" min="0" max="100" value="10.00">
                                <small class="form-text text-muted">Default: 10% of total earnings</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="loan_notes">Loan Notes | หมายเหตุเงินกู้</label>
                        <textarea name="loan_notes" id="loan_notes" class="form-control" rows="3" 
                                  placeholder="Optional notes about the loan"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="$('#employeeLoanModal').modal('hide')">Cancel | ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">Save Changes | บันทึกการเปลี่ยนแปลง</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    function editEmployeeLoan(employeeId, employeeName, currentLoan, position, companyName, bankAccount, repaymentPercentage, loanNotes) {
        $('#employee_id').val(employeeId);
        $('#employee_name').val(employeeName);
        $('#position').val(position || '');
        $('#company_name').val(companyName || '');
        $('#bank_account').val(bankAccount || '');
        $('#modal_loan_amount').val(currentLoan || 0);
        $('#repayment_percentage').val(repaymentPercentage || 10);
        $('#loan_notes').val(loanNotes || '');
        $('#employeeLoanModal').modal('show');
    }
    
    $(document).ready(function () {
        feather.replace();
        
        // Ensure modal can be closed properly
        $('#employeeLoanModal').on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
        });
        
        // Handle close button clicks
        $('.close, [data-dismiss="modal"]').on('click', function() {
            $('#employeeLoanModal').modal('hide');
        });
        
        // Handle form submission
        $('#employeeLoanForm').on('submit', function(e) {
            e.preventDefault();
            
            var submitBtn = $(this).find('button[type="submit"]');
            var originalText = submitBtn.html();
            submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
            
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        $('#employeeLoanModal').modal('hide');
                        // Show success message
                        $('body').prepend('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                            response.message + 
                            '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>' +
                            '</div>');
                        // Reload after short delay
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        alert('Error: ' + (response.message || 'Failed to update employee details'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    alert('Error: Failed to update employee details. Please try again.');
                },
                complete: function() {
                    submitBtn.html(originalText).prop('disabled', false);
                }
            });
        });
    });
</script>