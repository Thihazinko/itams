<?php

namespace Database\Seeders;

use App\Models\EmailAccount;
use App\Models\EmailAlias;
use Illuminate\Database\Seeder;

class EmailMasterSeeder extends Seeder
{
    public function run(): void
    {
        if (EmailAccount::where('modified_by', 'Seeder Bulk')->exists()
            || EmailAlias::where('modified_by', 'Seeder Bulk')->exists()) {
            $this->command?->info('Email Master already seeded — skipping.');
            return;
        }

        // [name, department, address, username, password, status, remark]
        $gmail = [
            ['John Doe', 'IT', 'john.doe@gmail.com', 'john.doe', 'Jd@2024!secure', 'Active', 'Primary IT admin Gmail.'],
            ['Jane Smith', 'HR', 'jane.smith@gmail.com', 'jane.smith', 'Js#hr2024pass', 'Active', 'HR recruitment inbox.'],
            ['Finance Team', 'Finance', 'company.finance@gmail.com', 'company.finance', 'Fin@nce2024$', 'Active', 'Shared finance Gmail for invoices.'],
            ['Marketing', 'Marketing', 'company.marketing@gmail.com', 'company.marketing', 'Mktg2024!go', 'Inactive', 'Old campaign account — no longer used.'],
            ['Support Desk', 'IT', 'company.support@gmail.com', 'company.support', 'Supp0rt#2024', 'Active', 'Customer support backup mailbox.'],
        ];

        // [name, department, address, username, password, status, remark]
        $email = [
            ['John Doe', 'IT', 'john.doe@company.com', 'jdoe', 'Jd@corp2024!', 'Active', 'Corporate primary account.'],
            ['Jane Smith', 'HR', 'jane.smith@company.com', 'jsmith', 'Js@corp2024!', 'Active', 'HR corporate account.'],
            ['Aung Kyaw', 'Sales', 'aung.kyaw@company.com', 'akyaw', 'Ak@sales2024', 'Active', 'Sales lead account.'],
            ['Su Su', 'Admin', 'su.su@company.com', 'ssu', 'Ss@admin2024', 'Inactive', 'Staff left — account suspended.'],
            ['Info Mailbox', 'General', 'info@company.com', 'info', 'Inf0@company24', 'Active', 'Public contact address on the website.'],
            ['No-Reply', 'IT', 'no-reply@company.com', 'noreply', 'N0reply@2024sys', 'Active', 'System notifications sender.'],
        ];

        foreach ($gmail as [$name, $dept, $address, $username, $password, $status, $remark]) {
            EmailAccount::create([
                'type'        => 'Gmail',
                'status'      => $status,
                'name'        => $name,
                'department'  => $dept,
                'address'     => $address,
                'username'    => $username,
                'password'    => $password,
                'remark'      => $remark,
                'modified_by' => 'Seeder Bulk',
            ]);
        }

        foreach ($email as [$name, $dept, $address, $username, $password, $status, $remark]) {
            EmailAccount::create([
                'type'        => 'Email',
                'status'      => $status,
                'name'        => $name,
                'department'  => $dept,
                'address'     => $address,
                'username'    => $username,
                'password'    => $password,
                'remark'      => $remark,
                'modified_by' => 'Seeder Bulk',
            ]);
        }

        // [main_email, [member addresses...], remark]
        $aliases = [
            ['it@company.com', ['john.doe@company.com', 'aung.kyaw@company.com', 'su.su@company.com'], 'IT team distribution list.'],
            ['hr@company.com', ['jane.smith@company.com', 'su.su@company.com'], 'HR distribution list.'],
            ['sales@company.com', ['aung.kyaw@company.com', 'john.doe@company.com'], 'Sales enquiries forwarded to the sales team.'],
            ['all-staff@company.com', ['john.doe@company.com', 'jane.smith@company.com', 'aung.kyaw@company.com', 'su.su@company.com', 'info@company.com'], 'Company-wide announcements.'],
            ['support@company.com', ['company.support@gmail.com', 'john.doe@company.com'], 'Support tickets routed to the desk + backup Gmail.'],
        ];

        foreach ($aliases as [$main, $members, $remark]) {
            $alias = EmailAlias::create([
                'main_email'  => $main,
                'remark'      => $remark,
                'modified_by' => 'Seeder Bulk',
            ]);

            foreach ($members as $address) {
                $alias->members()->create(['address' => $address]);
            }
        }

        $this->command?->info('Seeded ' . count($gmail) . ' Gmail, ' . count($email) . ' Email accounts and ' . count($aliases) . ' aliases.');
    }
}
