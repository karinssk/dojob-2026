<?php

class EmployeeModel extends Model
{
    protected $table = 'employees';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', /* other columns */];
}
