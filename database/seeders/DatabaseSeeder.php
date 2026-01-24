<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $headOffice = Branch::create([
            'name' => 'Head Office',
            'code' => 'HQ-001',
            'address' => 'Central Business District',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'phone' => '+234 800 000 0000',
            'email' => 'hq@tele-fleet.test',
            'is_head_office' => true,
        ]);

        $ikeja = Branch::create([
            'name' => 'Ikeja Operations',
            'code' => 'IKE-002',
            'address' => 'Alausa Industrial Zone',
            'city' => 'Ikeja',
            'state' => 'Lagos',
            'phone' => '+234 800 000 0100',
            'email' => 'ikeja@tele-fleet.test',
        ]);

        $abuja = Branch::create([
            'name' => 'Abuja Logistics',
            'code' => 'ABJ-003',
            'address' => 'Central Area',
            'city' => 'Abuja',
            'state' => 'FCT',
            'phone' => '+234 800 000 0200',
            'email' => 'abuja@tele-fleet.test',
        ]);

        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@tele-fleet.test',
            'phone' => '+234 800 000 0001',
            'role' => User::ROLE_SUPER_ADMIN,
            'branch_id' => $headOffice->id,
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('password'),
        ]);

        Vehicle::create([
            'registration_number' => 'TF-1001',
            'branch_id' => $headOffice->id,
            'make' => 'Toyota',
            'model' => 'Hiace',
            'year' => 2020,
            'color' => 'Silver',
            'fuel_type' => 'diesel',
            'engine_capacity' => '2.8L',
            'current_mileage' => 48210,
            'insurance_expiry' => now()->addMonths(8)->toDateString(),
            'registration_expiry' => now()->addMonths(10)->toDateString(),
            'status' => 'available',
        ]);

        Vehicle::create([
            'registration_number' => 'TF-2002',
            'branch_id' => $ikeja->id,
            'make' => 'Ford',
            'model' => 'Ranger',
            'year' => 2019,
            'color' => 'Blue',
            'fuel_type' => 'diesel',
            'engine_capacity' => '2.2L',
            'current_mileage' => 61500,
            'insurance_expiry' => now()->addMonths(4)->toDateString(),
            'registration_expiry' => now()->addMonths(5)->toDateString(),
            'status' => 'maintenance',
        ]);

        Vehicle::create([
            'registration_number' => 'TF-3003',
            'branch_id' => $abuja->id,
            'make' => 'Hyundai',
            'model' => 'Staria',
            'year' => 2022,
            'color' => 'Black',
            'fuel_type' => 'petrol',
            'engine_capacity' => '2.4L',
            'current_mileage' => 18900,
            'insurance_expiry' => now()->addMonths(11)->toDateString(),
            'registration_expiry' => now()->addMonths(12)->toDateString(),
            'status' => 'available',
        ]);

        Driver::create([
            'full_name' => 'Emeka Johnson',
            'license_number' => 'LIC-ENG-2041',
            'license_type' => 'Class B',
            'license_expiry' => now()->addYears(2)->toDateString(),
            'phone' => '+234 800 000 0300',
            'email' => 'emeka.johnson@tele-fleet.test',
            'address' => 'Ikeja',
            'branch_id' => $ikeja->id,
            'status' => 'active',
        ]);

        Driver::create([
            'full_name' => 'Tomi Bassey',
            'license_number' => 'LIC-LAG-8830',
            'license_type' => 'Class C',
            'license_expiry' => now()->addYears(1)->toDateString(),
            'phone' => '+234 800 000 0400',
            'email' => 'tomi.bassey@tele-fleet.test',
            'address' => 'Victoria Island',
            'branch_id' => $headOffice->id,
            'status' => 'active',
        ]);

        Driver::create([
            'full_name' => 'Ibrahim Musa',
            'license_number' => 'LIC-ABJ-4502',
            'license_type' => 'Class B',
            'license_expiry' => now()->addYears(3)->toDateString(),
            'phone' => '+234 800 000 0500',
            'email' => 'ibrahim.musa@tele-fleet.test',
            'address' => 'Garki',
            'branch_id' => $abuja->id,
            'status' => 'active',
        ]);
    }
}
