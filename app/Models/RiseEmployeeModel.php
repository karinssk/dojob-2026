<?php
namespace App\Models;
use CodeIgniter\Model;

class RiseEmployeeModel extends Model
{
    protected $table = 'rise_employees';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'name', 'company_name', 'position', 'bank_account'];
}
