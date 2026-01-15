<?php

namespace App\Models;
use CodeIgniter\Model;

class RiseEarningsModel extends Model
{
    protected $table = 'rise_earnings';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'payslip_id', 'salary', 'overtime', 'commission',
        'allowance', 'bonus', 'earning_other', 'ytd_earning', 'total_earning'
    ];
}
