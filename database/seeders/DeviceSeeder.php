<?php

namespace Database\Seeders;

use App\Models\Device;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        if (Device::where('modified_by', 'Seeder Bulk')->exists()) {
            $this->command?->info('Devices already seeded — skipping.');
            return;
        }

        $samples = [
            ['Dell Latitude 5430 Laptop', 'DL5430-001', 'IT Storage', 1, 'Free', 'Spare laptop for new hires.', 'Dell Direct', '2024-02-10', '3 years', '2024-02-12', 'Office HQ - Level 2', null],
            ['HP EliteBook 840 G10', 'HPEB-840-022', 'Office HQ - Level 3', 1, 'Active', 'Assigned to Finance manager.', 'HP Authorized', '2024-04-22', '3 years', '2024-04-25', 'Office HQ - Level 3', 'Battery replaced under warranty.'],
            ['Lenovo ThinkPad T14 Gen 4', 'TPT14-G4-018', 'Office HQ - Level 1', 1, 'Active', 'Sales team workstation.', 'KMD', '2023-11-08', '3 years', '2023-11-10', 'Office HQ - Level 1', null],
            ['Apple MacBook Pro 14"', 'MBP14-2024-007', 'Office HQ - Level 2', 1, 'Active', 'Design team primary device.', 'iCity Yangon', '2024-06-15', '1 year', '2024-06-18', 'Office HQ - Level 2', 'AppleCare+ active.'],
            ['Dell UltraSharp U2723QE 27"', 'DU27-2023-099', 'Office HQ - Level 2', 5, 'Active', '4K monitors for designers.', 'Dell Direct', '2023-09-12', '3 years', '2023-09-15', 'Office HQ - Level 2', null],
            ['LG 24MP400 24" FHD Monitor', 'LG24-2024-045', 'Office HQ - Level 1', 8, 'Active', 'Standard monitors for support desk.', 'PC World', '2024-01-20', '2 years', '2024-01-22', 'Office HQ - Level 1', null],
            ['Logitech MX Master 3S', 'LOG-MX3S-031', 'Office HQ - Level 2', 12, 'Active', 'Wireless productivity mice.', 'PC World', '2024-03-08', '2 years', '2024-03-10', 'Office HQ - Level 2', null],
            ['Logitech MX Keys', 'LOG-MXK-029', 'Office HQ - Level 2', 12, 'Active', 'Wireless keyboards paired with MX Master.', 'PC World', '2024-03-08', '2 years', '2024-03-10', 'Office HQ - Level 2', null],
            ['HP LaserJet Pro M404dn', 'HP-M404-008', 'Reception', 1, 'Active', 'Reception printer.', 'HP Authorized', '2023-05-14', '1 year', '2023-05-16', 'Reception', 'Warranty expired - service contract instead.'],
            ['Canon imageRUNNER 2630i', 'CN-IR-2630-002', 'Office HQ - Level 1', 1, 'Active', 'Multi-function copier on Level 1.', 'Canon Myanmar', '2022-08-30', null, '2022-09-02', 'Office HQ - Level 1', 'Managed print service contract.'],
            ['Brother HL-L2350DW', 'BR-HL2350-014', 'Office HQ - Level 3', 1, 'Damage', 'Paper jam mechanism broken.', 'Tech Mart', '2022-12-01', '1 year', '2022-12-04', 'Office HQ - Level 3', 'Repair quote exceeds replacement cost.'],
            ['Cisco Catalyst 2960-X Switch', 'CSC-2960X-001', 'Server Room A', 2, 'Active', 'Core access switches, 48-port.', 'Cisco Partner MM', '2022-04-18', '5 years', '2022-04-25', 'Server Room A', 'Smart-Net contract until 2027.'],
            ['TP-Link Archer AX73 Router', 'TPL-AX73-006', 'Office HQ - Level 2', 1, 'Active', 'Wi-Fi 6 router for guest network.', 'PC World', '2024-07-02', '2 years', '2024-07-04', 'Office HQ - Level 2', null],
            ['Netgear GS324 24-Port Switch', 'NG-GS324-011', 'Server Room B', 1, 'Active', 'Distribution switch for branch link.', 'KMD', '2023-10-05', '3 years', '2023-10-08', 'Server Room B', null],
            ['Dell PowerEdge R740 Server', 'DELL-R740-001', 'Server Room A', 1, 'Active', 'Primary virtualisation host.', 'Dell Direct', '2022-06-20', '5 years', '2022-06-30', 'Server Room A', 'ProSupport Plus.'],
            ['HP ProLiant DL380 Gen10', 'HP-DL380-002', 'Server Room A', 1, 'Active', 'Backup and replication target.', 'HP Authorized', '2023-02-15', '5 years', '2023-02-22', 'Server Room A', null],
            ['APC Smart-UPS 3000VA', 'APC-SU3000-003', 'Server Room A', 2, 'Active', 'Rack-mount UPS for core racks.', 'APC Reseller', '2023-08-11', '3 years', '2023-08-14', 'Server Room A', 'Battery replacement scheduled 2026-08.'],
            ['CyberPower CP1500AVRLCD', 'CPP-CP1500-012', 'Office HQ - Level 1', 4, 'Active', 'Desk-side UPS for reception and meeting rooms.', 'PC World', '2024-05-30', '2 years', '2024-06-02', 'Office HQ - Level 1', null],
            ['Epson EB-X51 Projector', 'EPS-EBX51-003', 'Meeting Room A', 1, 'Active', 'Main boardroom projector.', 'Tech Mart', '2023-12-19', '2 years', '2023-12-22', 'Meeting Room A', null],
            ['BenQ MX560 Projector', 'BQ-MX560-005', 'Meeting Room B', 1, 'Free', 'Spare projector in IT storage cabinet.', 'PC World', '2022-11-04', '2 years', '2022-11-07', 'Meeting Room B', null],
            ['Logitech BRIO 4K Webcam', 'LOG-BRIO-017', 'Office HQ - Level 2', 3, 'Active', 'For video conferencing rooms.', 'PC World', '2024-02-28', '2 years', '2024-03-02', 'Office HQ - Level 2', null],
            ['Jabra Evolve2 65 Headset', 'JBR-E65-024', 'Office HQ - Level 1', 10, 'Active', 'Support desk headsets.', 'KMD', '2024-04-10', '2 years', '2024-04-13', 'Office HQ - Level 1', null],
            ['Logitech Zone Wireless', 'LOG-ZW-009', 'Office HQ - Level 3', 4, 'Free', 'Spare headsets for hot desks.', 'PC World', '2023-11-22', '2 years', '2023-11-25', 'Office HQ - Level 3', null],
            ['Seagate 4TB External HDD', 'SG-4TB-021', 'IT Storage', 6, 'Free', 'Backup drives, rotated weekly.', 'Tech Mart', '2024-01-08', '2 years', '2024-01-10', 'IT Storage', null],
            ['Western Digital My Passport 2TB', 'WD-MP-2TB-013', 'IT Storage', 5, 'Active', 'Travel backups for field engineers.', 'PC World', '2023-07-19', '2 years', '2023-07-22', 'IT Storage', null],
            ['iPhone 14 Pro 256GB', 'APL-IP14P-004', 'Office HQ - Level 3', 1, 'Active', 'Director mobile device.', 'iCity Yangon', '2023-10-30', '1 year', '2023-11-02', 'Office HQ - Level 3', 'Warranty lapsed; under monthly insurance.'],
            ['Samsung Galaxy S24', 'SS-S24-010', 'Office HQ - Level 1', 2, 'Active', 'Field engineer phones.', 'Samsung Mart', '2024-05-15', '1 year', '2024-05-17', 'Office HQ - Level 1', null],
            ['iPad Pro 12.9" M2', 'APL-IPP-129-006', 'Meeting Room A', 1, 'Active', 'Presentation tablet for boardroom.', 'iCity Yangon', '2024-03-19', '1 year', '2024-03-21', 'Meeting Room A', null],
            ['Samsung Galaxy Tab S9 FE', 'SS-TS9FE-015', 'Office HQ - Level 2', 2, 'Active', 'Sales demo tablets.', 'Samsung Mart', '2024-06-04', '1 year', '2024-06-06', 'Office HQ - Level 2', null],
            ['Fujitsu fi-7160 Scanner', 'FJ-FI7160-002', 'Office HQ - Level 2', 1, 'Active', 'High-volume document scanner.', 'KMD', '2023-04-25', '3 years', '2023-04-28', 'Office HQ - Level 2', null],
            ['Canon imageFORMULA DR-C225W', 'CN-DRC225-008', 'Office HQ - Level 1', 1, 'Retirement', 'Retired - replaced by network scanner.', 'Tech Mart', '2020-09-12', null, '2020-09-15', 'Office HQ - Level 1', 'Pending physical disposal.'],
            ['Dell OptiPlex 7090 Desktop', 'DELL-OP-7090-003', 'Office HQ - Level 1', 4, 'Active', 'Support desk workstations.', 'Dell Direct', '2022-10-17', '3 years', '2022-10-21', 'Office HQ - Level 1', null],
            ['HP Z2 Mini G9 Workstation', 'HP-Z2-G9-001', 'Office HQ - Level 2', 1, 'Active', 'CAD engineer workstation.', 'HP Authorized', '2024-08-09', '3 years', '2024-08-12', 'Office HQ - Level 2', null],
            ['Logitech Rally Bar', 'LOG-RB-001', 'Meeting Room A', 1, 'Active', 'All-in-one video bar for the boardroom.', 'PC World', '2024-07-25', '2 years', '2024-07-28', 'Meeting Room A', null],
            ['Poly Studio X30 Video Bar', 'PLY-SX30-002', 'Meeting Room B', 1, 'Free', 'Spare video bar in IT storage.', 'KMD', '2023-09-30', '2 years', '2023-10-03', 'Meeting Room B', null],
            ['Samsung 55" 4K Signage Display', 'SS-55-SIG-004', 'Reception', 1, 'Active', 'Lobby information display.', 'Samsung Mart', '2023-03-14', '3 years', '2023-03-17', 'Reception', null],
            ['Yealink T46U IP Phone', 'YL-T46U-016', 'Office HQ - Level 1', 8, 'Active', 'Support desk IP phones.', 'KMD', '2023-06-11', '2 years', '2023-06-14', 'Office HQ - Level 1', null],
            ['Zebra ZD420 Label Printer', 'ZB-ZD420-007', 'IT Storage', 1, 'Active', 'Asset label printer.', 'Tech Mart', '2024-05-20', '2 years', '2024-05-23', 'IT Storage', null],
            ['Synology DS1522+ NAS', 'SYN-DS1522-001', 'Server Room B', 1, 'Active', '40TB NAS for file shares.', 'KMD', '2023-12-05', '3 years', '2023-12-08', 'Server Room B', 'RAID-6 with hot spare.'],
            ['Fortinet FortiGate 60F Firewall', 'FTN-FG60F-001', 'Server Room A', 1, 'Active', 'Edge firewall with FortiCare bundle.', 'Fortinet Partner MM', '2023-01-30', '3 years', '2023-02-05', 'Server Room A', 'FortiCare renewed annually.'],
        ];

        foreach ($samples as $row) {
            [$itemName, $serial, $location, $qty, $status, $description, $vendor, $purchasedDate, $warranty, $deliveryDate, $deliveryLocation, $remark] = $row;

            Device::create([
                'item_name'         => $itemName,
                'serial_number'     => $serial,
                'location'          => $location,
                'qty'               => $qty,
                'status'            => $status,
                'description'       => $description,
                'vendor'            => $vendor,
                'purchased_date'    => $purchasedDate,
                'warranty'          => $warranty,
                'delivery_date'     => $deliveryDate,
                'delivery_location' => $deliveryLocation,
                'remark'            => $remark,
                'modified_by'       => 'Seeder Bulk',
            ]);
        }

        $this->command?->info('Seeded ' . count($samples) . ' sample devices.');
    }
}
