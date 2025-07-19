<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Hash;
use App\Models\System;
use App\Models\Alert;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $system = System::first();
        if(!$system) {
            System::create([
                'is_active' => true,
            ]);
        }

        $alert = Alert::where('name', 'Alert')->first();
        if(empty($alert)) {
            Alert::create([
                'name' => 'Alert',
                'type' => 'Warning',
                'description' => 'The water is cloudy, fogging cannot be done. The water is not enough, fogging cannot be done.',
                'result' => '',
            ]);
        }
        

        // Seed other data
        $this->call([
            RoleSeeder::class,
            AboutSeeder::class,
        ]);
    }
}
