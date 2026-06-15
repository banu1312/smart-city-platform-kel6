<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OauthClientSeeder extends Seeder {
    public function run(): void {
        DB::table('oauth_clients')->insertOrIgnore([
            ['client_id'=>'iot-device',   'client_secret'=>'secret_iot_2026',      'grant_types'=>'client_credentials'],
            ['client_id'=>'mobile-app',   'client_secret'=>'secret_mobile_2026',   'grant_types'=>'password,refresh_token'],
            ['client_id'=>'internal-svc', 'client_secret'=>'secret_internal_2026', 'grant_types'=>'client_credentials'],
        ]);
    }
}