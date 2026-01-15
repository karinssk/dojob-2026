<?php

namespace App\Models;
use CodeIgniter\Model;

class RisePayslipModel extends Model
{
    protected $table = 'rise_payslips';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'employee_id', 'salary_period', 'payment_date',
        'year', 'netpay', 'employee_signature', 'created_at', 'updated_at'
    ];
}
