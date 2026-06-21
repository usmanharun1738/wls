<?php

namespace Database\Seeders;

use App\Models\Ranger;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RangerSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed 10 active rangers across various Kaduna-area locations.
     */
    public function run(): void
    {
        Ranger::factory()->createMany([
            ['name' => 'Ibrahim Musa', 'phone_number' => '+2347012345678', 'email' => 'ibrahim.musa@wildlife.gov.ng', 'base_location' => 'Kamuku National Park HQ', 'latitude' => 10.2719, 'longitude' => 6.1527],
            ['name' => 'Fatima Bello', 'phone_number' => '+2347023456789', 'email' => 'fatima.bello@wildlife.gov.ng', 'base_location' => 'Birnin Gwari Forest Station', 'latitude' => 10.6637, 'longitude' => 6.5431],
            ['name' => 'Usman Sani', 'phone_number' => '+2347034567890', 'email' => 'usman.sani@wildlife.gov.ng', 'base_location' => 'Kuyambana Game Reserve Outpost', 'latitude' => 10.5190, 'longitude' => 5.7321],
            ['name' => 'Aisha Yusuf', 'phone_number' => '+2347045678901', 'email' => 'aisha.yusuf@wildlife.gov.ng', 'base_location' => 'River Kaduna Patrol Base', 'latitude' => 10.5264, 'longitude' => 7.4393],
            ['name' => 'Mohammed Abubakar', 'phone_number' => '+2347056789012', 'email' => 'mohammed.abubakar@wildlife.gov.ng', 'base_location' => 'Dagida Forest Reserve', 'latitude' => 10.3891, 'longitude' => 5.8634],
            ['name' => 'Zainab Adamu', 'phone_number' => '+2347067890123', 'email' => 'zainab.adamu@wildlife.gov.ng', 'base_location' => 'Kamuku National Park HQ', 'latitude' => 10.2719, 'longitude' => 6.1527],
            ['name' => 'Suleiman Garba', 'phone_number' => '+2347078901234', 'email' => 'suleiman.garba@wildlife.gov.ng', 'base_location' => 'Old Oyo National Park - North Gate', 'latitude' => 9.0765, 'longitude' => 4.5547],
            ['name' => 'Halima Idris', 'phone_number' => '+2347089012345', 'email' => 'halima.idris@wildlife.gov.ng', 'base_location' => 'Zugurma Game Reserve HQ', 'latitude' => 9.8200, 'longitude' => 5.1000],
            ['name' => 'Bello Tanko', 'phone_number' => '+2347090123456', 'email' => 'bello.tanko@wildlife.gov.ng', 'base_location' => 'Kainji Lake National Park', 'latitude' => 10.2542, 'longitude' => 4.5888],
            ['name' => 'Amina Lawal', 'phone_number' => '+2347001234567', 'email' => 'amina.lawal@wildlife.gov.ng', 'base_location' => 'Yankari Game Reserve South', 'latitude' => 9.7000, 'longitude' => 10.5000],
            ['name' => 'Usman Haruna', 'phone_number' => '+2348119106475', 'email' => 'usman.haruna@wildlife.gov.ng', 'base_location' => 'Kaduna Central Command', 'latitude' => 10.5100, 'longitude' => 7.4300],
        ]);
    }
}
