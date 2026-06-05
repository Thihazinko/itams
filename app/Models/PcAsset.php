<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PcAsset extends Model
{
    public const DEPARTMENTS = [
        'Admin', 'Finance', 'HR', 'IT Development', 'Contract',
        'Offshore', 'SST', 'BPO', 'Infra', 'Sale',
    ];

    protected $fillable = [
        'computer_id', 'hostname', 'employee_name', 'status', 'department',
        'location', 'brand', 'model', 'serial_number', 'cpu', 'ram', 'ssd',
        'hdd', 'display', 'operating_system', 'admin_password', 'username',
        'password', 'purchased_date', 'warranty_period', 'remarks', 'modified_by',
    ];

    protected $casts = [
        'purchased_date' => 'date',
        'admin_password' => 'encrypted',
        'username' => 'encrypted',
        'password' => 'encrypted',
    ];
}
