<?php
namespace App\Controllers;

use Dompdf\Dompdf;
use Dompdf\Options;

class Payslips extends Security_Controller
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $this->access_only_admin();
        
        try {
            $query = $this->db->query("
                SELECT 
                    p.id,
                    p.salary_period,
                    p.payment_date,
                    p.netpay,
                    p.remark,
                    CONCAT(u.first_name, ' ', u.last_name) as employee_name
                FROM rise_payslips p
                LEFT JOIN rise_employees e ON e.id = p.employee_id
                LEFT JOIN rise_users u ON u.id = e.user_id
                ORDER BY p.id DESC
            ");
            
            $payslips = $query->getResultArray();
        } catch (\Exception $e) {
            $payslips = [];
            log_message('error', 'Payslips index error: ' . $e->getMessage());
        }

        $view_data['payslips'] = $payslips;
        return $this->template->rander('payslips/index', $view_data);
    }

    public function create()
    {
        $this->access_only_admin();
        
        try {
            $query = $this->db->query("
                SELECT 
                    e.id as emp_id,
                    u.id as user_id,
                    u.first_name,
                    u.last_name,
                    e.name,
                    e.position
                FROM rise_employees e
                LEFT JOIN rise_users u ON u.id = e.user_id
                WHERE u.deleted = 0
                ORDER BY u.first_name, u.last_name
            ");
            
            $employees = $query->getResultArray();
            
            // Get active loans for all employees
            $loans_query = $this->db->query("
                SELECT 
                    cl.employee_id,
                    cl.remaining_balance,
                    cl.monthly_repayment_percentage
                FROM rise_company_loans cl
                WHERE cl.status = 'active' AND cl.remaining_balance > 0
            ");
            $loans = $loans_query->getResultArray();
            
            // Index loans by employee_id for easy lookup
            $employee_loans = [];
            foreach ($loans as $loan) {
                $employee_loans[$loan['employee_id']] = $loan;
            }
            
        } catch (\Exception $e) {
            $employees = [];
            $employee_loans = [];
            log_message('error', 'Payslips create error: ' . $e->getMessage());
        }

        $view_data['employees'] = $employees;
        $view_data['employee_loans'] = $employee_loans;
        return $this->template->rander('payslips/create', $view_data);
    }

    public function store()
    {
        $this->access_only_admin();
        
        // Debug: Log that we reached the store method
        log_message('debug', 'Payslip store method called');
        
        // Debug: Check if it's a POST request
        if (!$this->request->getMethod() === 'post') {
            log_message('debug', 'Not a POST request: ' . $this->request->getMethod());
            return redirect()->back()->with('error', 'Invalid request method');
        }
        
        // Debug: Log all POST data
        $postData = $this->request->getPost();
        log_message('debug', 'POST data: ' . json_encode($postData));
        
        // Simple validation first
        $employee_id = $this->request->getPost('employee_id');
        $salary_period = $this->request->getPost('salary_period');
        $payment_date = $this->request->getPost('payment_date');
        $year = $this->request->getPost('year');
        $netpay = $this->request->getPost('netpay');
        
        log_message('debug', "Employee ID: $employee_id, Period: $salary_period, Date: $payment_date");
        
        if (!$employee_id || !$salary_period || !$payment_date || !$year || !$netpay) {
            log_message('debug', 'Missing required fields');
            return redirect()->back()->withInput()->with('error', 'Please fill all required fields');
        }

        try {
            // Test database connection
            $test_query = $this->db->query("SELECT COUNT(*) as count FROM rise_employees");
            $test_result = $test_query->getRowArray();
            log_message('debug', 'Employee count: ' . $test_result['count']);
            
            // Get employee info
            $employee_query = $this->db->query("SELECT user_id FROM rise_employees WHERE id = ?", [$employee_id]);
            $employee = $employee_query->getRowArray();
            
            log_message('debug', 'Employee found: ' . json_encode($employee));
            
            if (!$employee) {
                return redirect()->back()->withInput()->with('error', 'Employee not found');
            }

            // Simple insert without transaction first
            $payslip_data = [
                'user_id' => $employee['user_id'],
                'employee_id' => $employee_id,
                'salary_period' => $salary_period,
                'payment_date' => $payment_date,
                'year' => $year,
                'netpay' => $netpay,
                'employee_signature' => $this->request->getPost('employee_signature') ?? '',
                'remark' => $this->request->getPost('remark') ?? '',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            log_message('debug', 'Payslip data to insert: ' . json_encode($payslip_data));

            $result = $this->db->table('rise_payslips')->insert($payslip_data);
            $payslip_id = $this->db->insertID();
            
            log_message('debug', "Insert result: $result, Payslip ID: $payslip_id");

            if (!$payslip_id) {
                $error = $this->db->error();
                log_message('error', 'Database error: ' . json_encode($error));
                return redirect()->back()->withInput()->with('error', 'Failed to create payslip: ' . $error['message']);
            }

            // Insert earnings with new fields and calculations
            $salary = floatval($this->request->getPost('salary') ?? 0);
            $overtime = floatval($this->request->getPost('overtime') ?? 0);
            $commission = floatval($this->request->getPost('commission') ?? 0);
            $allowance = floatval($this->request->getPost('allowance') ?? 0);
            $bonus = floatval($this->request->getPost('bonus') ?? 0);
            $earning_other = floatval($this->request->getPost('earning_other') ?? 0);
            
            // Calculate total earning (show full amounts in earnings)
            $total_earning = $salary + $overtime + $commission + $allowance + $bonus + $earning_other;

            // Calculate tax (3% of commission + allowance)
            $tax = ($commission + $allowance) * 0.03;
            
            // Calculate social security (5% of total earnings, max 750)
            $social_security = min($total_earning * 0.05, 750);
            
            // Get previous YTD values for this employee in this year
            $previous_values_query = $this->db->query("
                SELECT 
                    COALESCE(SUM(e.total_earning), 0) as previous_total_earning,
                    COALESCE(SUM(d.social_security), 0) as previous_social_security,
                    COALESCE(SUM(d.tax), 0) as previous_tax
                FROM rise_earnings e 
                JOIN rise_payslips p ON p.id = e.payslip_id 
                JOIN rise_deductions d ON d.payslip_id = p.id
                WHERE p.employee_id = ? AND p.year = ?
            ", [$employee_id, $year]);
            $previous_values = $previous_values_query->getRowArray();
            
            // Calculate YTD values (previous + current)
            $ytd_earning = ($previous_values['previous_total_earning'] ?? 0) + $total_earning;
            $accumulated_ssf = ($previous_values['previous_social_security'] ?? 0) + $social_security;
            $ytd_withholding_tax = ($previous_values['previous_tax'] ?? 0) + $tax;

            $earnings_data = [
                'payslip_id' => $payslip_id,
                'salary' => $salary,
                'overtime' => $overtime,
                'commission' => $commission, // Store full commission amount
                'allowance' => $allowance, // Store full allowance amount
                'bonus' => $bonus,
                'earning_other' => $earning_other,
                'total_earning' => $total_earning,
                'ytd_earning' => $ytd_earning,
                'ytd-withholding-tax' => $ytd_withholding_tax,
                'accumulated-ssf' => $accumulated_ssf
            ];

            $this->db->table('rise_earnings')->insert($earnings_data);

            // Insert deductions with new fields and calculations
            $provident_fund = floatval($this->request->getPost('provident_fund') ?? 0);
            $advance_deduction = floatval($this->request->getPost('advance_deduction') ?? 0);
            $loan_repayment = floatval($this->request->getPost('loan_repayment') ?? 0);
            
            // Employee welfare fund - check if enabled (from form)
            $employee_welfare_fund = floatval($this->request->getPost('employee_welfare_fund') ?? 0);
            
            $deduction_other = floatval($this->request->getPost('deduction_other') ?? 0);
            
            $total_deduction = $tax + $social_security + $provident_fund + $advance_deduction + $loan_repayment + $employee_welfare_fund + $deduction_other;

            // Handle loan repayment if applicable
            if ($loan_repayment > 0) {
                $loan_amount = floatval($this->request->getPost('loan_amount') ?? 0);
                
                // Get or create loan for this employee
                $loan_query = $this->db->query("
                    SELECT * FROM rise_company_loans 
                    WHERE employee_id = ? AND status = 'active'
                    ORDER BY loan_date ASC LIMIT 1
                ", [$employee_id]);
                $loan = $loan_query->getRowArray();
                
                if (!$loan && $loan_amount > 0) {
                    // Create new loan if doesn't exist
                    $this->db->table('rise_company_loans')->insert([
                        'employee_id' => $employee_id,
                        'loan_amount' => $loan_amount,
                        'remaining_balance' => $loan_amount,
                        'monthly_repayment_percentage' => 10.00,
                        'loan_date' => $payment_date,
                        'status' => 'active'
                    ]);
                    $loan_id = $this->db->insertID();
                } else if ($loan) {
                    // Update existing loan amount if different
                    if ($loan_amount != $loan['remaining_balance']) {
                        $this->db->query("
                            UPDATE rise_company_loans 
                            SET remaining_balance = ?, updated_at = NOW()
                            WHERE id = ?
                        ", [$loan_amount, $loan['id']]);
                    }
                    $loan_id = $loan['id'];
                }
                
                if (isset($loan_id)) {
                    // Calculate new remaining balance after repayment
                    $new_balance = max(0, $loan_amount - $loan_repayment);
                    
                    // Update loan balance
                    $this->db->query("
                        UPDATE rise_company_loans 
                        SET remaining_balance = ?, 
                            status = CASE WHEN ? <= 0 THEN 'completed' ELSE 'active' END,
                            updated_at = NOW()
                        WHERE id = ?
                    ", [$new_balance, $new_balance, $loan_id]);
                    
                    // Record repayment
                    $this->db->table('rise_loan_repayments')->insert([
                        'loan_id' => $loan_id,
                        'payslip_id' => $payslip_id,
                        'repayment_amount' => $loan_repayment,
                        'remaining_balance_after' => $new_balance,
                        'repayment_date' => $payment_date
                    ]);
                }
            }

            $deductions_data = [
                'payslip_id' => $payslip_id,
                'tax' => $tax,
                'social_security' => $social_security,
                'employee-welfare-fund' => $employee_welfare_fund,
                'provident_fund' => $provident_fund,
                'advance-deduction' => $advance_deduction,
                'loan_repayment' => $loan_repayment,
                'deduction_other' => $deduction_other,
                'total_deduction' => $total_deduction
            ];

            $this->db->table('rise_deductions')->insert($deductions_data);

            log_message('debug', 'Payslip created successfully, redirecting...');
            return redirect()->to(base_url('payslips'))->with('success', 'Payslip created successfully!');

        } catch (\Exception $e) {
            log_message('error', 'Payslip store error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return redirect()->back()->withInput()->with('error', 'Database error: ' . $e->getMessage());
        }
    }

    public function view($id)
    {
        $this->access_only_admin();
        
        log_message('debug', "Viewing payslip ID: $id");
        
        try {
            // Get payslip with employee info
            $payslip_query = $this->db->query("
                SELECT 
                    p.*,
                    CONCAT(u.first_name, ' ', u.last_name) as employee_name,
                    e.position,
                    e.company_name,
                    e.bank_account
                FROM rise_payslips p
                LEFT JOIN rise_employees e ON e.id = p.employee_id
                LEFT JOIN rise_users u ON u.id = e.user_id
                WHERE p.id = ?
            ", [$id]);
            
            $payslip = $payslip_query->getRowArray();
            
            log_message('debug', 'Payslip data: ' . json_encode($payslip));
            
            if (!$payslip) {
                log_message('debug', 'Payslip not found');
                return redirect()->to(base_url('payslips'))->with('error', 'Payslip not found');
            }

            // Get earnings
            $earnings_query = $this->db->query("SELECT * FROM rise_earnings WHERE payslip_id = ?", [$id]);
            $earnings = $earnings_query->getRowArray();
            
            log_message('debug', 'Earnings data: ' . json_encode($earnings));

            // Get deductions
            $deductions_query = $this->db->query("SELECT * FROM rise_deductions WHERE payslip_id = ?", [$id]);
            $deductions = $deductions_query->getRowArray();
            
            log_message('debug', 'Deductions data: ' . json_encode($deductions));

            // Get loan information for this employee
            $loan_query = $this->db->query("
                SELECT 
                    remaining_balance,
                    monthly_repayment_percentage,
                    notes
                FROM rise_company_loans 
                WHERE employee_id = ? AND status = 'active'
                ORDER BY loan_date DESC LIMIT 1
            ", [$payslip['employee_id']]);
            $loan_info = $loan_query->getRowArray();

            $view_data = [
                'payslip' => $payslip,
                'earnings' => $earnings,
                'deductions' => $deductions,
                'loan_info' => $loan_info
            ];

            log_message('debug', 'Rendering view template');
            return $this->template->rander('payslips/view', $view_data);

        } catch (\Exception $e) {
            log_message('error', 'Payslip view error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return redirect()->to(base_url('payslips'))->with('error', 'Error loading payslip: ' . $e->getMessage());
        }
    }

    public function print($id)
    {
        $this->access_only_admin();
        
        try {
            // Get payslip with employee info
            $payslip_query = $this->db->query("
                SELECT 
                    p.*,
                    CONCAT(u.first_name, ' ', u.last_name) as employee_name,
                    e.position,
                    e.company_name,
                    e.bank_account
                FROM rise_payslips p
                LEFT JOIN rise_employees e ON e.id = p.employee_id
                LEFT JOIN rise_users u ON u.id = e.user_id
                WHERE p.id = ?
            ", [$id]);
            
            $payslip = $payslip_query->getRowArray();
            
            if (!$payslip) {
                show_404();
            }

            // Get earnings
            $earnings_query = $this->db->query("SELECT * FROM rise_earnings WHERE payslip_id = ?", [$id]);
            $earnings = $earnings_query->getRowArray();
            
            if (!$earnings) {
                $earnings = [
                    'salary' => 0, 'overtime' => 0, 'commission' => 0,
                    'allowance' => 0, 'bonus' => 0, 'earning_other' => 0,
                    'total_earning' => 0, 'ytd_earning' => 0
                ];
            }

            // Get deductions
            $deductions_query = $this->db->query("SELECT * FROM rise_deductions WHERE payslip_id = ?", [$id]);
            $deductions = $deductions_query->getRowArray();
            
            if (!$deductions) {
                $deductions = [
                    'tax' => 0, 'social_security' => 0, 'deduction_other' => 0,
                    'total_deduction' => 0
                ];
            }

            return view('payslips/print', [
                'payslip' => $payslip,
                'earnings' => $earnings,
                'deductions' => $deductions
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Payslip print error: ' . $e->getMessage());
            show_404();
        }
    }

    public function delete($id)
    {
        $this->access_only_admin();
        
        $this->db->transStart();

        try {
            // Delete in correct order due to foreign keys
            $this->db->query("DELETE FROM rise_deductions WHERE payslip_id = ?", [$id]);
            $this->db->query("DELETE FROM rise_earnings WHERE payslip_id = ?", [$id]);
            $this->db->query("DELETE FROM rise_payslips WHERE id = ?", [$id]);

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return redirect()->to(base_url('payslips'))->with('success', 'Payslip deleted successfully!');

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Payslip delete error: ' . $e->getMessage());
            return redirect()->to(base_url('payslips'))->with('error', 'Failed to delete payslip');
        }
    }

    public function downloadPdf($id)
    {
        $this->access_only_admin();
        
        // Debug logging
        log_message('debug', 'PDF download requested for payslip ID: ' . $id);
        
        try {
            // Get payslip data (reuse print logic)
            $payslip_query = $this->db->query("
                SELECT 
                    p.*,
                    CONCAT(u.first_name, ' ', u.last_name) as employee_name,
                    e.position,
                    e.company_name,
                    e.bank_account
                FROM rise_payslips p
                LEFT JOIN rise_employees e ON e.id = p.employee_id
                LEFT JOIN rise_users u ON u.id = e.user_id
                WHERE p.id = ?
            ", [$id]);
            
            $payslip = $payslip_query->getRowArray();
            
            if (!$payslip) {
                return redirect()->to(base_url('payslips'))->with('error', 'Payslip not found');
            }

            $earnings_query = $this->db->query("SELECT * FROM rise_earnings WHERE payslip_id = ?", [$id]);
            $earnings = $earnings_query->getRowArray() ?? [];

            $deductions_query = $this->db->query("SELECT * FROM rise_deductions WHERE payslip_id = ?", [$id]);
            $deductions = $deductions_query->getRowArray() ?? [];

            $data = [
                'payslip' => $payslip,
                'earnings' => $earnings,
                'deductions' => $deductions
            ];

            // Create a clean HTML template for PDF (without scripts and complex CSS)
            log_message('debug', 'Generating clean HTML for PDF');
            $html = $this->generateCleanPdfHtml($data);
            
            log_message('debug', 'Setting up Dompdf options');
            $options = new Options();
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isRemoteEnabled', false);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('debugKeepTemp', true);
            
            log_message('debug', 'Creating Dompdf instance');
            $dompdf = new Dompdf($options);
            
            log_message('debug', 'Loading HTML into Dompdf');
            $dompdf->loadHtml($html);
            
            log_message('debug', 'Setting paper size');
            $dompdf->setPaper('A4', 'portrait');
            
            log_message('debug', 'Rendering PDF');
            $dompdf->render();

            log_message('debug', 'Getting PDF output');
            $pdfContent = $dompdf->output();
            
            log_message('debug', 'PDF content length: ' . strlen($pdfContent));
            
            if (strlen($pdfContent) == 0) {
                throw new \Exception('PDF content is empty');
            }
            
            // Clear any previous output
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Set proper headers
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="payslip_' . $id . '.pdf"');
            header('Content-Length: ' . strlen($pdfContent));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            // Output the PDF
            echo $pdfContent;
            exit;

        } catch (\Exception $e) {
            log_message('error', 'PDF generation error: ' . $e->getMessage());
            log_message('error', 'PDF generation stack trace: ' . $e->getTraceAsString());
            
            // Return error as HTML for debugging
            header('Content-Type: text/html');
            echo '<h1>PDF Generation Error</h1>';
            echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><strong>File:</strong> ' . $e->getFile() . '</p>';
            echo '<p><strong>Line:</strong> ' . $e->getLine() . '</p>';
            echo '<h3>Stack Trace:</h3>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            exit;
        }
    }
    
    private function generateCleanPdfHtml($data)
    {
        $payslip = $data['payslip'];
        $earnings = $data['earnings'];
        $deductions = $data['deductions'];
        
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payslip #' . $payslip['id'] . '</title>
    <style>
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            margin: 20px;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        .company-info {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .payslip-title {
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0;
        }
        .employee-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .employee-left, .employee-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 10px;
        }
        .employee-left {
            border-right: 1px solid #ccc;
        }
        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .content-table th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            font-weight: bold;
        }
        .content-table td {
            border: 1px solid #000;
            padding: 6px;
        }
        .amount {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .net-pay {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            border: 2px solid #000;
            background-color: #f0f0f0;
        }
        .footer {
            margin-top: 30px;
            display: table;
            width: 100%;
        }
        .footer-left, .footer-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            width: 200px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            ห้างหุ้นส่วนจำกัด รูบี้ช๊อป (สำนักงานใหญ่)<br>
            97/60 หมู่บ้าน หลักสี่แลนด์ซอย โกสุมรวมใจ 39<br>
            แขวงดอนเมือง เขตดอนเมือง กรุงเทพมหานคร 10210<br>
            เลขที่ผู้เสียภาษี: 0103555019171
        </div>
        <div class="payslip-title">สลิปเงินเดือน / Pay Slip</div>
    </div>

    <div class="employee-section">
        <div class="employee-left">
            <strong>ชื่อพนักงาน / Employee Name:</strong><br>
            ' . ($payslip['employee_name'] ?? 'N/A') . '<br><br>
            <strong>รหัสพนักงาน / Employee ID:</strong><br>
            ' . ($payslip['employee_id'] ?? 'N/A') . '<br><br>
            <strong>ตำแหน่ง / Position:</strong><br>
            ' . ($payslip['position'] ?? 'N/A') . '
        </div>
        <div class="employee-right">
            <strong>รอบเงินเดือน / Salary Period:</strong><br>
            ' . $payslip['salary_period'] . '<br><br>
            <strong>วันที่จ่าย / Payment Date:</strong><br>
            ' . date('d/m/Y', strtotime($payslip['payment_date'])) . '<br><br>
            <strong>ปี / Year:</strong><br>
            ' . $payslip['year'] . '
        </div>
    </div>

    <table class="content-table">
        <tr>
            <th width="33%">รายได้ / Earnings</th>
            <th width="33%">รายการหัก / Deductions</th>
            <th width="34%">สรุป / Summary</th>
        </tr>
        <tr>
            <td style="vertical-align: top;">
                <table width="100%" style="border: none;">
                    <tr><td>เงินเดือน / Salary</td><td class="amount">' . number_format($earnings['salary'] ?? 0, 2) . '</td></tr>
                    <tr><td>ค่าล่วงเวลา / Overtime</td><td class="amount">' . number_format($earnings['overtime'] ?? 0, 2) . '</td></tr>
                    <tr><td>คอมมิชชั่น / Commission</td><td class="amount">' . number_format($earnings['commission'] ?? 0, 2) . '</td></tr>
                    <tr><td>ค่าเบี้ยเลี้ยง / Allowance</td><td class="amount">' . number_format($earnings['allowance'] ?? 0, 2) . '</td></tr>
                    <tr><td>โบนัส / Bonus</td><td class="amount">' . number_format($earnings['bonus'] ?? 0, 2) . '</td></tr>
                    <tr><td>อื่นๆ / Other</td><td class="amount">' . number_format($earnings['earning_other'] ?? 0, 2) . '</td></tr>
                    <tr class="total-row"><td><strong>รวม / Total</strong></td><td class="amount"><strong>' . number_format($earnings['total_earning'] ?? 0, 2) . '</strong></td></tr>
                </table>
            </td>
            <td style="vertical-align: top;">
                <table width="100%" style="border: none;">
                    <tr><td>ประกันสังคม / Social Security</td><td class="amount">' . number_format($deductions['social_security'] ?? 0, 2) . '</td></tr>
                    <tr><td>ภาษี / Tax</td><td class="amount">' . number_format($deductions['tax'] ?? 0, 2) . '</td></tr>
                    <tr><td>กองทุนสำรองเลี้ยงชีพ / Provident Fund</td><td class="amount">' . number_format($deductions['provident_fund'] ?? 0, 2) . '</td></tr>
                    <tr><td>กองทุนสวัสดิการ / Welfare Fund</td><td class="amount">' . number_format($deductions['employee-welfare-fund'] ?? 0, 2) . '</td></tr>
                    <tr><td>เงินเบิกล่วงหน้า / Advance</td><td class="amount">' . number_format($deductions['advance-deduction'] ?? 0, 2) . '</td></tr>
                    ' . (($deductions['loan_repayment'] ?? 0) > 0 ? '<tr><td>ชำระเงินกู้ / Loan Repayment</td><td class="amount">' . number_format($deductions['loan_repayment'], 2) . '</td></tr>' : '') . '
                    <tr><td>อื่นๆ / Other</td><td class="amount">' . number_format($deductions['deduction_other'] ?? 0, 2) . '</td></tr>
                    <tr class="total-row"><td><strong>รวม / Total</strong></td><td class="amount"><strong>' . number_format($deductions['total_deduction'] ?? 0, 2) . '</strong></td></tr>
                </table>
            </td>
            <td style="vertical-align: top;">
                <table width="100%" style="border: none;">
                    <tr><td>เงินได้สะสม / YTD Earnings</td><td class="amount">' . number_format($earnings['ytd_earning'] ?? 0, 2) . '</td></tr>
                    <tr><td>ประกันสังคมสะสม / Accumulated SSF</td><td class="amount">' . number_format($earnings['accumulated-ssf'] ?? 0, 2) . '</td></tr>
                    <tr><td>ภาษีหักสะสม / YTD Tax</td><td class="amount">' . number_format($earnings['ytd-withholding-tax'] ?? 0, 2) . '</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="net-pay">
        เงินได้สุทธิ / Net Pay: ฿' . number_format($payslip['netpay'], 2) . '
    </div>

    <div class="footer">
        <div class="footer-left">
            <strong>หมายเหตุ / Remark:</strong><br>
            ' . ($payslip['remark'] ? nl2br(htmlspecialchars($payslip['remark'])) : 'ไม่มีหมายเหตุ / No remarks') . '
        </div>
        <div class="footer-right">
            <strong>ลายเซ็นผู้รับเงิน / Employee Signature:</strong>
            <div class="signature-line"></div>
            <br>
            <strong>วันที่ / Date:</strong> ' . date('d/m/Y') . '
        </div>
    </div>
</body>
</html>';
    }

    public function createEmployees()
    {
        $this->access_only_admin();
        
        try {
            // Get all users who are not already employees
            $users_query = $this->db->query("
                SELECT u.id, u.first_name, u.last_name, u.email, u.job_title
                FROM rise_users u
                LEFT JOIN rise_employees e ON e.user_id = u.id
                WHERE e.id IS NULL 
                AND u.deleted = 0 
                AND u.user_type = 'staff'
                ORDER BY u.first_name, u.last_name
            ");
            
            $available_users = $users_query->getResultArray();
            
            log_message('debug', 'Available users for employee creation: ' . count($available_users));
            
        } catch (\Exception $e) {
            $available_users = [];
            log_message('error', 'Error fetching available users: ' . $e->getMessage());
        }

        $view_data['available_users'] = $available_users;
        return $this->template->rander('payslips/create_employees', $view_data);
    }

    public function storeEmployee()
    {
        $this->access_only_admin();
        
        $validation = \Config\Services::validation();
        
        $validation->setRules([
            'user_id' => 'required|numeric',
            'name' => 'required|min_length[2]',
            'position' => 'required|min_length[2]',
            'company_name' => 'required|min_length[2]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        try {
            // Check if user is already an employee
            $existing_query = $this->db->query("SELECT id FROM rise_employees WHERE user_id = ?", [$this->request->getPost('user_id')]);
            if ($existing_query->getNumRows() > 0) {
                return redirect()->back()->withInput()->with('error', 'This user is already an employee.');
            }

            // Get user info for validation
            $user_query = $this->db->query("SELECT id, first_name, last_name FROM rise_users WHERE id = ? AND deleted = 0", [$this->request->getPost('user_id')]);
            $user = $user_query->getRowArray();
            
            if (!$user) {
                return redirect()->back()->withInput()->with('error', 'User not found.');
            }

            // Insert employee record
            $employee_data = [
                'user_id' => $this->request->getPost('user_id'),
                'name' => $this->request->getPost('name'),
                'company_name' => $this->request->getPost('company_name'),
                'position' => $this->request->getPost('position'),
                'bank_account' => $this->request->getPost('bank_account') ?? ''
            ];

            log_message('debug', 'Creating employee: ' . json_encode($employee_data));

            $result = $this->db->table('rise_employees')->insert($employee_data);
            $employee_id = $this->db->insertID();

            if (!$employee_id) {
                $error = $this->db->error();
                log_message('error', 'Employee creation failed: ' . json_encode($error));
                return redirect()->back()->withInput()->with('error', 'Failed to create employee: ' . $error['message']);
            }

            log_message('debug', 'Employee created successfully with ID: ' . $employee_id);
            return redirect()->to(base_url('payslips/createEmployees'))->with('success', 'Employee created successfully!');

        } catch (\Exception $e) {
            log_message('error', 'Employee creation error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Database error: ' . $e->getMessage());
        }
    }

    public function listEmployees()
    {
        $this->access_only_admin();
        
        try {
            $employees_query = $this->db->query("
                SELECT 
                    e.*,
                    CONCAT(u.first_name, ' ', u.last_name) as full_name,
                    u.email,
                    u.job_title,
                    u.status,
                    cl.remaining_balance as loan_balance,
                    cl.monthly_repayment_percentage,
                    cl.notes as loan_notes
                FROM rise_employees e
                LEFT JOIN rise_users u ON u.id = e.user_id
                LEFT JOIN rise_company_loans cl ON cl.employee_id = e.id AND cl.status = 'active'
                WHERE u.deleted = 0
                ORDER BY e.name
            ");
            
            $employees = $employees_query->getResultArray();
            
        } catch (\Exception $e) {
            $employees = [];
            log_message('error', 'Error fetching employees: ' . $e->getMessage());
        }

        $view_data['employees'] = $employees;
        return $this->template->rander('payslips/list_employees', $view_data);
    }
    
    public function updateEmployeeLoan()
    {
        $this->access_only_admin();
        
        if (!$this->request->getMethod() === 'post') {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request method']);
        }
        
        try {
            $employee_id = $this->request->getPost('employee_id');
            $position = $this->request->getPost('position') ?? '';
            $company_name = $this->request->getPost('company_name') ?? '';
            $bank_account = $this->request->getPost('bank_account') ?? '';
            $loan_amount = floatval($this->request->getPost('loan_amount') ?? 0);
            $repayment_percentage = floatval($this->request->getPost('repayment_percentage') ?? 10);
            $loan_notes = $this->request->getPost('loan_notes') ?? '';
            
            if (!$employee_id) {
                return $this->response->setJSON(['success' => false, 'message' => 'Employee ID is required']);
            }
            
            // Update employee details
            $this->db->query("
                UPDATE rise_employees 
                SET position = ?, 
                    company_name = ?, 
                    bank_account = ?
                WHERE id = ?
            ", [$position, $company_name, $bank_account, $employee_id]);
            
            // Check if employee has existing active loan
            $existing_loan_query = $this->db->query("
                SELECT * FROM rise_company_loans 
                WHERE employee_id = ? AND status = 'active'
                LIMIT 1
            ", [$employee_id]);
            $existing_loan = $existing_loan_query->getRowArray();
            
            if ($loan_amount > 0) {
                if ($existing_loan) {
                    // Update existing loan
                    $this->db->query("
                        UPDATE rise_company_loans 
                        SET remaining_balance = ?, 
                            monthly_repayment_percentage = ?,
                            notes = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ", [$loan_amount, $repayment_percentage, $loan_notes, $existing_loan['id']]);
                } else {
                    // Create new loan
                    $this->db->table('rise_company_loans')->insert([
                        'employee_id' => $employee_id,
                        'loan_amount' => $loan_amount,
                        'remaining_balance' => $loan_amount,
                        'monthly_repayment_percentage' => $repayment_percentage,
                        'loan_date' => date('Y-m-d'),
                        'status' => 'active',
                        'notes' => $loan_notes
                    ]);
                }
            } else {
                // Remove/complete loan if amount is 0
                if ($existing_loan) {
                    $this->db->query("
                        UPDATE rise_company_loans 
                        SET status = 'completed', 
                            remaining_balance = 0,
                            updated_at = NOW()
                        WHERE id = ?
                    ", [$existing_loan['id']]);
                }
            }
            
            return $this->response->setJSON(['success' => true, 'message' => 'Employee details and loan updated successfully']);
            
        } catch (\Exception $e) {
            log_message('error', 'Error updating employee loan: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function deleteEmployee($id)
    {
        $this->access_only_admin();
        
        try {
            // Check if employee has payslips
            $payslips_query = $this->db->query("SELECT COUNT(*) as count FROM rise_payslips WHERE employee_id = ?", [$id]);
            $payslips_count = $payslips_query->getRowArray()['count'];
            
            if ($payslips_count > 0) {
                return redirect()->back()->with('error', 'Cannot delete employee with existing payslips. Delete payslips first.');
            }

            // Delete employee
            $this->db->query("DELETE FROM rise_employees WHERE id = ?", [$id]);
            
            return redirect()->back()->with('success', 'Employee deleted successfully!');
            
        } catch (\Exception $e) {
            log_message('error', 'Employee deletion error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete employee: ' . $e->getMessage());
        }
    }

    // Debug method - remove in production
    public function test_db()
    {
        $this->access_only_admin();
        
        echo "<h2>Database Test Results</h2>";
        
        $tables = ['rise_users', 'rise_employees', 'rise_payslips', 'rise_earnings', 'rise_deductions'];
        
        foreach ($tables as $table) {
            try {
                $query = $this->db->query("SHOW TABLES LIKE '$table'");
                if ($query->getNumRows() > 0) {
                    $count = $this->db->table($table)->countAllResults();
                    echo "<p>✅ Table '$table' exists with $count records</p>";
                } else {
                    echo "<p>❌ Table '$table' does not exist</p>";
                }
            } catch (\Exception $e) {
                echo "<p>❌ Error checking table '$table': " . $e->getMessage() . "</p>";
            }
        }
        
        // Show sample data
        try {
            $users = $this->db->query("SELECT id, first_name, last_name FROM rise_users LIMIT 3")->getResultArray();
            echo "<h3>Sample Users:</h3><pre>" . print_r($users, true) . "</pre>";
            
            $employees = $this->db->query("SELECT * FROM rise_employees LIMIT 3")->getResultArray();
            echo "<h3>Sample Employees:</h3><pre>" . print_r($employees, true) . "</pre>";
        } catch (\Exception $e) {
            echo "<p>Error fetching sample data: " . $e->getMessage() . "</p>";
        }
        
        // Test form submission
        echo "<hr><h3>Test Form Submission</h3>";
        echo '<form action="' . base_url('payslips/test_form') . '" method="post">';
        echo csrf_field();
        echo '<input type="text" name="test_field" value="test_value" />';
        echo '<button type="submit">Test Submit</button>';
        echo '</form>';
    }
    
    public function test_form()
    {
        $this->access_only_admin();
        
        echo "<h2>Form Test Results</h2>";
        echo "<p>Method: " . $this->request->getMethod() . "</p>";
        echo "<p>POST data:</p>";
        echo "<pre>" . print_r($this->request->getPost(), true) . "</pre>";
        
        echo '<p><a href="' . base_url('payslips/test_db') . '">Back to test</a></p>';
    }
}