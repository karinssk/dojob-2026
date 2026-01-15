<?php namespace App\Models;

use CodeIgniter\Model;

class PayslipModel extends Model
{
    protected $table = 'payslips';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'employee_id', 'salary_period', 'payment_date', 'salary',
        'overtime', 'commission', 'allowance', 'bonus', 'earning_other',
        'sso', 'tax', 'student_loan', 'deposit', 'absence', 'deduction_other',
        'year', 'ytd_earning', 'ytd_tax', 'ytd_sso', 'total_earning',
        'total_deduction', 'netpay', 'employee_signature'
    ];
}
