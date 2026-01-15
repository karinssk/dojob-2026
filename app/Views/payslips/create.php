<div class="card">
    <div class="page-title clearfix">
        <h4><?php echo app_lang('create_payslip'); ?></h4>
        <div class="title-button-group">
            <a href="<?= base_url('payslips') ?>" class="btn btn-default">
                <i data-feather="arrow-left" class="icon-16"></i> <?php echo app_lang('back'); ?>
            </a>
        </div>
    </div>
    
    <div class="card-body">
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger">
                <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach (session()->getFlashdata('errors') as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('payslips/store') ?>" method="post" id="payslip-form"><?= csrf_field() ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="employee_id"><?php echo app_lang('employee_name'); ?> *</label>
                    <select name="employee_id" id="employee_id" class="form-control" required>
                        <option value="">-- <?php echo app_lang('select'); ?> --</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['emp_id'] ?>" <?= old('employee_id') == $emp['emp_id'] ? 'selected' : '' ?>>
                                <?= $emp['first_name'] . ' ' . $emp['last_name'] ?>
                                <?php if ($emp['position']): ?>
                                    (<?= $emp['position'] ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label for="salary_period"><?php echo app_lang('salary_period'); ?> *</label>
                    <div class="input-group">
                        <input type="text" name="salary_period" id="salary_period" class="form-control" 
                               value="<?= old('salary_period') ?>" placeholder="Select date range" required readonly>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary" id="salary_period_btn">
                                <i data-feather="calendar" class="icon-16"></i>
                            </button>
                        </div>
                    </div>
                    <small class="form-text text-muted">Default shows previous month range. Click calendar to change.</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="payment_date"><?php echo app_lang('payment_date'); ?> *</label>
                    <input type="date" name="payment_date" id="payment_date" class="form-control" 
                           value="<?= old('payment_date') ?>" required>
                    <small class="form-text text-muted">Default: First day of current month</small>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label for="year"><?php echo app_lang('year'); ?> *</label>
                    <input type="number" name="year" id="year" class="form-control" 
                           value="<?= old('year', date('Y')) ?>" min="2020" max="2030" required>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label for="netpay"><?php echo app_lang('net_pay'); ?> *</label>
                    <input type="number" name="netpay" id="netpay" class="form-control" 
                           value="<?= old('netpay') ?>" step="0.01" min="0" required>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="employee_signature"><?php echo app_lang('employee_signature'); ?></label>
                    <input type="text" name="employee_signature" id="employee_signature" class="form-control" 
                           value="<?= old('employee_signature') ?>" placeholder="Optional">
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label for="remark">Remark<br><small class="text-muted">หมายเหตุ</small></label>
                    <textarea name="remark" id="remark" class="form-control" rows="3" 
                              placeholder="Optional remarks or notes | หมายเหตุหรือข้อความเพิ่มเติม (ไม่บังคับ)"><?= old('remark') ?></textarea>
                </div>
            </div>
        </div>

        <hr>
        <h5><?php echo app_lang('earnings'); ?></h5>
        
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="salary"><?php echo app_lang('salary'); ?><br><small class="text-muted">เงินเดือน</small></label>
                    <input type="number" name="salary" id="salary" class="form-control earning-input" 
                           value="<?= old('salary', '0') ?>" step="0.01" min="0">
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label for="overtime">Overtime<br><small class="text-muted">ค่าล่วงเวลา</small></label>
                    <input type="number" name="overtime" id="overtime" class="form-control earning-input" 
                           value="<?= old('overtime', '0') ?>" step="0.01" min="0">
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label for="commission">Commission <small>(Tax will be auto-calculated at 3%)</small><br><small class="text-muted">คอมมิชชั่น</small></label>
                    <input type="number" name="commission" id="commission" class="form-control earning-input" 
                           value="<?= old('commission', '0') ?>" step="0.01" min="0">
                    <small class="form-text text-muted">Shows full amount in earnings, tax deducted separately</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="allowance">Allowance <small>(Tax will be auto-calculated at 3%)</small><br><small class="text-muted">ค่าเบี้ยเลี้ยง</small></label>
                    <input type="number" name="allowance" id="allowance" class="form-control earning-input" 
                           value="<?= old('allowance', '0') ?>" step="0.01" min="0">
                    <small class="form-text text-muted">Shows full amount in earnings, tax deducted separately</small>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label for="bonus">Bonus<br><small class="text-muted">โบนัส</small></label>
                    <input type="number" name="bonus" id="bonus" class="form-control earning-input" 
                           value="<?= old('bonus', '0') ?>" step="0.01" min="0">
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label for="earning_other">Other Earnings<br><small class="text-muted">รายได้อื่นๆ</small></label>
                    <input type="number" name="earning_other" id="earning_other" class="form-control earning-input" 
                           value="<?= old('earning_other', '0') ?>" step="0.01" min="0">
                </div>
            </div>
        </div>

        <hr>
        <h5><?php echo app_lang('deductions'); ?></h5>
        
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="tax">Tax <small>(Auto-calculated: 3% of commission + allowance)</small><br><small class="text-muted">ภาษีหัก ณ ที่จ่าย</small></label>
                    <input type="number" name="tax" id="tax" class="form-control deduction-input" 
                           value="<?= old('tax', '0') ?>" step="0.01" min="0" readonly>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label for="social_security">Social Security <small>(Default: 5% of total earnings, max ฿750)</small><br><small class="text-muted">ประกันสังคม</small></label>
                    <input type="number" name="social_security" id="social_security" class="form-control deduction-input" 
                           value="<?= old('social_security', '0') ?>" step="0.01" min="0">
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label for="provident_fund">Provident Fund<br><small class="text-muted">กองทุนสำรองเลี้ยงชีพ</small></label>
                    <input type="number" name="provident_fund" id="provident_fund" class="form-control deduction-input" 
                           value="<?= old('provident_fund', '0') ?>" step="0.01" min="0">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="advance_deduction">Advance Deduction<br><small class="text-muted">หักเงินเบิกล่วงหน้า</small></label>
                    <input type="number" name="advance_deduction" id="advance_deduction" class="form-control deduction-input" 
                           value="<?= old('advance_deduction', '0') ?>" step="0.01" min="0">
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label for="loan_repayment">
                        Loan Repayment <small>(Default: 10% of total earnings)</small><br>
                        <small class="text-muted">ชำระคืนเงินกู้บริษัท</small>
                        <div class="form-check form-check-inline ml-2">
                            <input class="form-check-input" type="checkbox" id="enable_loan_repayment">
                            <label class="form-check-label" for="enable_loan_repayment">Enable</label>
                        </div>
                    </label>
                    <input type="number" name="loan_repayment" id="loan_repayment" class="form-control deduction-input" 
                           value="0.00" step="0.01" min="0">
                    <div id="loan_details" style="display:none;">
                        <div class="mt-2">
                            <label for="loan_amount">Current Loan Amount <small>(฿)</small><br><small class="text-muted">จำนวนเงินกู้ปัจจุบัน</small></label>
                            <input type="number" name="loan_amount" id="loan_amount" class="form-control" 
                                   value="0.00" step="0.01" min="0" placeholder="Enter loan amount">
                        </div>
                        <small class="form-text text-muted mt-1" id="loan_balance_info">
                            Outstanding Balance: <span id="outstanding_balance">฿0.00</span>
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label for="employee_welfare_fund">
                        Employee Welfare Fund <small>(Auto-calculated: 0.25% of total earnings)</small><br>
                        <small class="text-muted">กองทุนสวัสดิการพนักงาน</small>
                        <div class="form-check form-check-inline ml-2">
                            <input class="form-check-input" type="checkbox" id="enable_welfare_fund" checked>
                            <label class="form-check-label" for="enable_welfare_fund">Enable</label>
                        </div>
                    </label>
                    <input type="number" name="employee_welfare_fund" id="employee_welfare_fund" class="form-control" 
                           value="0.00" step="0.01" min="0" readonly>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label for="deduction_other">Other Deductions</label>
                    <input type="number" name="deduction_other" id="deduction_other" class="form-control deduction-input" 
                           value="<?= old('deduction_other', '0') ?>" step="0.01" min="0">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i data-feather="check-circle" class="icon-16"></i> <?php echo app_lang('save'); ?>
                    </button>
                </div>
            </div>
        </div>

        </form>
    </div>
</div>

<style>
    .salary-period-input {
        cursor: pointer;
    }
    
    .modal-body .row {
        margin-bottom: 15px;
    }
    
    .btn-group-period {
        margin-top: 10px;
    }
    
    .btn-group-period .btn {
        margin-right: 5px;
    }
    
    #salary_period {
        background-color: white;
        cursor: pointer;
    }
    
    #salary_period:focus {
        box-shadow: none;
        border-color: #007bff;
    }
</style>

<script type="text/javascript">
    $(document).ready(function () {
        // Function to get default salary period (previous month)
        function getDefaultSalaryPeriod() {
            var today = new Date();
            var currentMonth = today.getMonth(); // 0-11
            var currentYear = today.getFullYear();
            
            // Get previous month
            var prevMonth = currentMonth - 1;
            var prevYear = currentYear;
            
            // Handle January (go to December of previous year)
            if (prevMonth < 0) {
                prevMonth = 11;
                prevYear = currentYear - 1;
            }
            
            // Get first day of previous month
            var firstDay = new Date(prevYear, prevMonth, 1);
            
            // Get last day of previous month
            var lastDay = new Date(prevYear, prevMonth + 1, 0);
            
            // Format dates as DD/MM/YYYY
            function formatDate(date) {
                var day = String(date.getDate()).padStart(2, '0');
                var month = String(date.getMonth() + 1).padStart(2, '0');
                var year = date.getFullYear();
                return day + '/' + month + '/' + year;
            }
            
            return formatDate(firstDay) + ' - ' + formatDate(lastDay);
        }
        
        // Set default salary period
        $('#salary_period').val(getDefaultSalaryPeriod());
        
        // Set default payment date to first day of current month
        function getFirstDayOfCurrentMonth() {
            var today = new Date();
            var year = today.getFullYear();
            var month = String(today.getMonth() + 1).padStart(2, '0');
            return year + '-' + month + '-01';
        }
        
        if (!$('#payment_date').val()) {
            $('#payment_date').val(getFirstDayOfCurrentMonth());
        }
        
        // Employee loans data from PHP
        var employeeLoans = <?= json_encode($employee_loans ?? []) ?>;
        
        // Handle employee selection change
        $('#employee_id').on('change', function() {
            var employeeId = $(this).val();
            if (employeeId && employeeLoans[employeeId]) {
                var loan = employeeLoans[employeeId];
                $('#enable_loan_repayment').prop('checked', true);
                $('#loan_details').show();
                $('#loan_amount').val(parseFloat(loan.remaining_balance).toFixed(2));
                $('#outstanding_balance').text('฿' + parseFloat(loan.remaining_balance).toFixed(2));
                calculateNetPay();
            } else {
                $('#enable_loan_repayment').prop('checked', false);
                $('#loan_details').hide();
                $('#loan_amount').val('0.00');
                calculateNetPay();
            }
        });
        
        // Initialize date range picker
        $('#salary_period, #salary_period_btn').on('click', function() {
            // Create date range picker modal
            var modal = $('<div class="modal fade" id="salaryPeriodModal" tabindex="-1" role="dialog">' +
                '<div class="modal-dialog" role="document">' +
                    '<div class="modal-content">' +
                        '<div class="modal-header">' +
                            '<h5 class="modal-title">Select Salary Period | เลือกรอบเงินเดือน</h5>' +
                            '<button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
                                '<span aria-hidden="true">&times;</span>' +
                            '</button>' +
                        '</div>' +
                        '<div class="modal-body">' +
                            '<div class="row">' +
                                '<div class="col-md-6">' +
                                    '<label>Start Date | วันที่เริ่มต้น:</label>' +
                                    '<input type="date" id="start_date" class="form-control">' +
                                '</div>' +
                                '<div class="col-md-6">' +
                                    '<label>End Date | วันที่สิ้นสุด:</label>' +
                                    '<input type="date" id="end_date" class="form-control">' +
                                '</div>' +
                            '</div>' +
                            '<div class="mt-3">' +
                                '<button type="button" class="btn btn-secondary btn-sm" id="set_prev_month">Previous Month | เดือนก่อน</button>' +
                                '<button type="button" class="btn btn-secondary btn-sm" id="set_current_month">Current Month | เดือนปัจจุบัน</button>' +
                            '</div>' +
                        '</div>' +
                        '<div class="modal-footer">' +
                            '<button type="button" class="btn btn-secondary" data-dismiss="modal" id="cancel_dates">Cancel | ยกเลิก</button>' +
                            '<button type="button" class="btn btn-primary" id="apply_dates">Apply | ใช้งาน</button>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>');
            
            $('body').append(modal);
            
            // Bind close events before showing modal
            modal.find('.close, #cancel_dates').on('click', function() {
                modal.modal('hide');
            });
            
            // Handle modal close with escape key
            modal.on('keydown', function(e) {
                if (e.keyCode === 27) { // Escape key
                    modal.modal('hide');
                }
            });
            
            modal.modal('show');
            
            // Parse current salary period if exists
            var currentPeriod = $('#salary_period').val();
            if (currentPeriod && currentPeriod.includes(' - ')) {
                var dates = currentPeriod.split(' - ');
                var startParts = dates[0].split('/');
                var endParts = dates[1].split('/');
                
                // Convert DD/MM/YYYY to YYYY-MM-DD for input[type="date"]
                if (startParts.length === 3) {
                    modal.find('#start_date').val(startParts[2] + '-' + startParts[1] + '-' + startParts[0]);
                }
                if (endParts.length === 3) {
                    modal.find('#end_date').val(endParts[2] + '-' + endParts[1] + '-' + endParts[0]);
                }
            }
            
            // Quick set buttons - use modal.find() to ensure proper binding
            modal.find('#set_prev_month').on('click', function() {
                var today = new Date();
                var prevMonth = today.getMonth() - 1;
                var prevYear = today.getFullYear();
                
                if (prevMonth < 0) {
                    prevMonth = 11;
                    prevYear = prevYear - 1;
                }
                
                var firstDay = new Date(prevYear, prevMonth, 1);
                var lastDay = new Date(prevYear, prevMonth + 1, 0);
                
                modal.find('#start_date').val(firstDay.toISOString().split('T')[0]);
                modal.find('#end_date').val(lastDay.toISOString().split('T')[0]);
            });
            
            modal.find('#set_current_month').on('click', function() {
                var today = new Date();
                var currentMonth = today.getMonth();
                var currentYear = today.getFullYear();
                
                var firstDay = new Date(currentYear, currentMonth, 1);
                var lastDay = new Date(currentYear, currentMonth + 1, 0);
                
                modal.find('#start_date').val(firstDay.toISOString().split('T')[0]);
                modal.find('#end_date').val(lastDay.toISOString().split('T')[0]);
            });
            
            // Apply button
            modal.find('#apply_dates').on('click', function() {
                var startDate = modal.find('#start_date').val();
                var endDate = modal.find('#end_date').val();
                
                if (startDate && endDate) {
                    // Validate date range
                    if (new Date(startDate) > new Date(endDate)) {
                        alert('Start date cannot be later than end date. | วันที่เริ่มต้นไม่สามารถมาหลังวันที่สิ้นสุด');
                        return;
                    }
                    
                    // Convert YYYY-MM-DD to DD/MM/YYYY
                    function convertDate(dateStr) {
                        var parts = dateStr.split('-');
                        return parts[2] + '/' + parts[1] + '/' + parts[0];
                    }
                    
                    var formattedPeriod = convertDate(startDate) + ' - ' + convertDate(endDate);
                    $('#salary_period').val(formattedPeriod);
                    modal.modal('hide');
                } else {
                    alert('Please select both start and end dates. | กรุณาเลือกวันที่เริ่มต้นและสิ้นสุด');
                }
            });
            
            // Clean up modal when closed
            modal.on('hidden.bs.modal', function() {
                modal.remove();
            });
        });
        
        // Auto-calculate tax, social security, loan repayment, net pay and employee welfare fund
        function calculateNetPay() {
            var totalEarnings = 0;
            var totalDeductions = 0;
            var commission = parseFloat($('#commission').val()) || 0;
            var allowance = parseFloat($('#allowance').val()) || 0;
            
            // Calculate tax (3% of commission + allowance)
            var tax = (commission + allowance) * 0.03;
            $('#tax').val(tax.toFixed(2));
            
            // Calculate total earnings (show full amounts in earnings section)
            $('.earning-input').each(function() {
                var value = parseFloat($(this).val()) || 0;
                totalEarnings += value;
            });
            
            // Calculate social security (5% of total earnings, max 750)
            var socialSecurity = Math.min(totalEarnings * 0.05, 750);
            $('#social_security').val(socialSecurity.toFixed(2));
            
            // Calculate loan repayment (10% of total earnings) - only if enabled
            var loanRepayment = 0;
            if ($('#enable_loan_repayment').is(':checked')) {
                var currentBalance = parseFloat($('#loan_amount').val()) || 0;
                var repaymentPercentage = 0.10; // Default 10%
                
                var employeeId = $('#employee_id').val();
                if (employeeId && employeeLoans[employeeId]) {
                    repaymentPercentage = parseFloat(employeeLoans[employeeId].monthly_repayment_percentage) / 100;
                }
                
                loanRepayment = Math.min(totalEarnings * repaymentPercentage, currentBalance);
                $('#loan_repayment').val(loanRepayment.toFixed(2));
                
                // Update outstanding balance display after repayment
                var newBalance = Math.max(0, currentBalance - loanRepayment);
                $('#outstanding_balance').text('฿' + newBalance.toFixed(2));
            } else {
                $('#loan_repayment').val('0.00');
            }
            
            // Calculate employee welfare fund (0.25% of total earnings) - only if enabled
            var employeeWelfareFund = 0;
            if ($('#enable_welfare_fund').is(':checked')) {
                employeeWelfareFund = totalEarnings * 0.0025;
            }
            $('#employee_welfare_fund').val(employeeWelfareFund.toFixed(2));
            
            // Calculate total deductions
            $('.deduction-input').each(function() {
                totalDeductions += parseFloat($(this).val()) || 0;
            });
            totalDeductions += employeeWelfareFund;
            
            // Net pay calculation: total earnings - total deductions
            var netPay = totalEarnings - totalDeductions;
            $('#netpay').val(netPay.toFixed(2));
        }
        
        $('.earning-input, .deduction-input').on('input', calculateNetPay);
        
        // Handle welfare fund toggle
        $('#enable_welfare_fund').on('change', function() {
            calculateNetPay();
        });
        
        // Handle loan repayment toggle
        $('#enable_loan_repayment').on('change', function() {
            if ($(this).is(':checked')) {
                $('#loan_details').show();
                var employeeId = $('#employee_id').val();
                if (employeeId && employeeLoans[employeeId]) {
                    $('#loan_amount').val(employeeLoans[employeeId].remaining_balance);
                }
            } else {
                $('#loan_details').hide();
                $('#loan_amount').val('0.00');
            }
            calculateNetPay();
        });
        
        // Handle loan amount changes
        $('#loan_amount').on('input', function() {
            var loanAmount = parseFloat($(this).val()) || 0;
            $('#outstanding_balance').text('฿' + loanAmount.toFixed(2));
            calculateNetPay();
        });
        
        // Special handling for commission and allowance fields
        $('#commission, #allowance').on('input', function() {
            calculateNetPay();
        });
        
        // Initial calculation
        calculateNetPay();
        
        // Initialize feather icons
        feather.replace();
    });
</script>