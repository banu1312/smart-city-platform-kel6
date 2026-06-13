<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
<<<<<<< HEAD
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => 'admin@smartcity.local',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        User::firstOrCreate(
            ['username' => 'warga1'],
            [
                'name' => 'Warga Satu',
                'email' => 'warga1@smartcity.local',
                'password' => Hash::make('warga123'),
                'role' => 'citizen',
            ]
        );

        $clients = app(ClientRepository::class);

        if (! Client::where('name', 'smartcity-app')->exists()) {
            $clients->createPasswordGrantClient('smartcity-app');
        }

        if (! Client::where('name', 'iot-device')->exists()) {
            $clients->createClientCredentialsGrantClient('iot-device');
        }
=======
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
>>>>>>> 7c82b8cb177e6524c24803cf44868762e581f8f3
    }
}
