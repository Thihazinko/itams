<?php

namespace Database\Seeders;

use App\Models\LicenseContract;
use App\Models\PcAsset;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@rrs.local'],
            [
                'name' => 'Administrator',
                'password' => 'password',
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'user@rrs.local'],
            [
                'name' => 'Standard User',
                'password' => 'password',
                'role' => 'user',
            ]
        );

        // Sample PC Assets
        if (PcAsset::count() === 0) {
            PcAsset::create([
                'computer_id' => 'PC-001',
                'hostname' => 'IT-WS01',
                'employee_name' => 'John Doe',
                'status' => 'Active',
                'department' => 'IT Development',
                'location' => 'Office',
                'brand' => 'Dell',
                'model' => 'Latitude 5430',
                'serial_number' => 'SN12345678',
                'cpu' => 'Intel Core i7-1265U',
                'ram' => '16GB',
                'ssd' => '512GB NVMe',
                'display' => '14" FHD',
                'operating_system' => 'Windows 11 Pro',
                'admin_password' => 'AdminPass123',
                'username' => 'jdoe',
                'password' => 'UserPass123',
                'purchased_date' => '2024-01-15',
                'warranty_period' => '3 years',
                'modified_by' => 'Administrator',
            ]);

            PcAsset::create([
                'computer_id' => 'PC-002',
                'hostname' => 'HR-WS02',
                'employee_name' => 'Jane Smith',
                'status' => 'Active',
                'department' => 'HR',
                'location' => 'WFH',
                'brand' => 'Lenovo',
                'model' => 'ThinkPad T14',
                'serial_number' => 'SN87654321',
                'cpu' => 'AMD Ryzen 7',
                'ram' => '32GB',
                'ssd' => '1TB NVMe',
                'display' => '14" QHD',
                'operating_system' => 'Windows 11 Pro',
                'purchased_date' => '2023-06-20',
                'warranty_period' => '3 years',
                'modified_by' => 'Administrator',
            ]);
        }

        // Sample Subscriptions
        if (Subscription::count() === 0) {
            Subscription::create([
                'service_type' => 'Domain',
                'project_name' => 'Corporate Website',
                'subscription_name' => 'company.com',
                'status' => 'Active',
                'period' => '1 Year',
                'previous_cost' => 15.00,
                'expire_date' => Carbon::today()->addDays(15),
                'renewal_cost' => 18.00,
                'renewal_type' => 'Yearly',
                'renewal_status' => 'Pending',
                'remarks' => 'Auto-renew enabled',
                'modified_by' => 'Administrator',
            ]);

            Subscription::create([
                'service_type' => 'SSL',
                'project_name' => 'Corporate Website',
                'subscription_name' => 'Wildcard SSL *.company.com',
                'status' => 'Active',
                'period' => '1 Year',
                'previous_cost' => 120.00,
                'expire_date' => Carbon::today()->addDays(7),
                'renewal_cost' => 130.00,
                'renewal_type' => 'Yearly',
                'renewal_status' => 'Pending',
                'modified_by' => 'Administrator',
            ]);

            Subscription::create([
                'service_type' => 'Cloud Service',
                'project_name' => 'Internal Apps',
                'subscription_name' => 'AWS EC2 Production',
                'status' => 'Active',
                'period' => '12 months',
                'previous_cost' => 850.00,
                'expire_date' => Carbon::today()->addDays(45),
                'renewal_cost' => 920.00,
                'renewal_type' => 'Monthly',
                'renewal_status' => 'Pending',
                'modified_by' => 'Administrator',
            ]);

            Subscription::create([
                'service_type' => 'Subscription',
                'project_name' => 'Productivity',
                'subscription_name' => 'Microsoft 365 Business',
                'status' => 'Active',
                'period' => '1 Year',
                'previous_cost' => 1200.00,
                'expire_date' => Carbon::today()->addDays(90),
                'renewal_cost' => 1320.00,
                'renewal_type' => 'Yearly',
                'renewal_status' => 'Pending',
                'modified_by' => 'Administrator',
            ]);
        }

        // Bulk-seed 50 randomised Subscriptions (once — marker on modified_by).
        if (! Subscription::where('modified_by', 'Seeder Bulk')->exists()) {
            $this->seedRandomSubscriptions(50);
        }

        // Sample License & Contract records
        if (LicenseContract::count() === 0) {
            LicenseContract::create([
                'software_name' => 'Microsoft 365 Business Premium',
                'status' => 'Active',
                'renewal_type' => 'Yearly',
                'license_info' => 'Tenant ID: 12345-abcde / Invoice INV-2025-014',
                'last_renewal_date' => Carbon::today()->subMonths(11),
                'expire_date' => Carbon::today()->addDays(30),
                'vendor_name' => 'Microsoft',
                'previous_cost' => 850000.00,
                'renewal_cost' => 920000.00,
                'currency' => 'MMK',
                'remarks' => 'Covers 25 seats; price increased due to seat expansion.',
                'modified_by' => 'Administrator',
            ]);

            LicenseContract::create([
                'software_name' => 'Adobe Creative Cloud (Team)',
                'status' => 'Active',
                'renewal_type' => 'Yearly',
                'license_info' => 'Serial: ADBE-TEAM-2024-XYZ / Seats: 5',
                'last_renewal_date' => Carbon::today()->subMonths(6),
                'expire_date' => Carbon::today()->addDays(180),
                'vendor_name' => 'Adobe',
                'previous_cost' => 720.00,
                'renewal_cost' => 720.00,
                'currency' => 'USD',
                'remarks' => 'Price unchanged for the second year in a row.',
                'modified_by' => 'Administrator',
            ]);

            LicenseContract::create([
                'software_name' => 'JetBrains All Products Pack',
                'status' => 'Active',
                'renewal_type' => 'Yearly',
                'license_info' => 'License key: JB-AP-9988-7766 / 3 dev seats',
                'last_renewal_date' => Carbon::today()->subMonths(2),
                'expire_date' => Carbon::today()->addDays(305),
                'vendor_name' => 'JetBrains',
                'previous_cost' => 79200.00,
                'renewal_cost' => 72000.00,
                'currency' => 'JPY',
                'remarks' => 'Loyalty discount applied on renewal.',
                'modified_by' => 'Administrator',
            ]);

            LicenseContract::create([
                'software_name' => 'Office Cleaning Service Contract',
                'status' => 'Pending',
                'renewal_type' => 'Yearly',
                'license_info' => 'Contract #CL-2024-007',
                'last_renewal_date' => Carbon::today()->subMonths(12),
                'expire_date' => Carbon::today()->addDays(5),
                'vendor_name' => 'Clean Pro Co., Ltd.',
                'previous_cost' => 360000.00,
                'renewal_cost' => 420000.00,
                'currency' => 'MMK',
                'remarks' => 'Awaiting management approval for renewal.',
                'modified_by' => 'Administrator',
            ]);

            LicenseContract::create([
                'software_name' => 'Antivirus Enterprise (Kaspersky)',
                'status' => 'Expired',
                'renewal_type' => 'Yearly',
                'license_info' => 'Activation code: KAV-ENT-2023-AAAA',
                'last_renewal_date' => Carbon::today()->subYear(),
                'expire_date' => Carbon::today()->subDays(10),
                'vendor_name' => 'Kaspersky Lab',
                'previous_cost' => 450000.00,
                'renewal_cost' => 480000.00,
                'currency' => 'MMK',
                'remarks' => 'Switching to alternate vendor under review.',
                'modified_by' => 'Administrator',
            ]);

            LicenseContract::create([
                'software_name' => 'Internet Leased Line Contract',
                'status' => 'Active',
                'renewal_type' => 'Monthly',
                'license_info' => 'Contract #ISP-2025-002 / 200 Mbps dedicated',
                'last_renewal_date' => Carbon::today()->subMonths(1),
                'expire_date' => Carbon::today()->addDays(335),
                'vendor_name' => 'MyanmarNet',
                'previous_cost' => 380000.00,
                'renewal_cost' => 380000.00,
                'currency' => 'MMK',
                'remarks' => 'Auto-billed monthly; annual contract.',
                'modified_by' => 'Administrator',
            ]);
        }

        $this->call(DeviceSeeder::class);
        $this->call(EmailMasterSeeder::class);
        $this->call(FinancialManagementSeeder::class);
        $this->call(GcpCostBreakdownSeeder::class);
    }
}
