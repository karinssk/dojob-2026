<?php

namespace App\Models;
use CodeIgniter\Model;

class RiseDeductionsModel extends Model
{
    protected $table = 'rise_deductions';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'payslip_id', 'tax', 'social_security', 'deduction_other', 'total_deduction'
    ];
}
