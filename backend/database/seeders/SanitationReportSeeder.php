<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SanitationReportSeeder extends Seeder {
    public function run(): void {
        $reports = [
            ['Budi Santoso',    '081234567001', 'Sampah menumpuk di trotoar menghalangi jalan kaki',        '-6.1944,106.8318', 'Pending'],
            ['Siti Rahayu',     '081234567002', 'Tong di depan pasar sudah penuh dan bau menyengat',        '-6.1950,106.8320', 'Reviewed'],
            ['Ahmad Fauzi',     '081234567003', 'Warga buang sampah sembarangan di pinggir jalan',          '-6.2383,106.8000', 'Pending'],
            ['Dewi Kusuma',     '081234567004', 'Api kecil terlihat dari tong sampah dekat sekolah',        '-6.2390,106.8010', 'Dispatched'],
            ['Riko Pratama',    '081234567005', 'Penumpukan sampah besar di bantaran kali',                 '-6.1200,106.8100', 'Pending'],
            ['Fitri Handayani', '081234567006', 'Tong sampah berlubang dan sampah berserakan di jalan',     '-6.1210,106.8110', 'Resolved'],
            ['Hendro Wijaya',   '081234567007', 'Tumpukan plastik di perumahan tidak diangkut 3 hari',     '-6.1500,106.7400', 'Pending'],
            ['Maya Sari',       '081234567008', 'Bau sangat menyengat dari area pembuangan sementara',     '-6.1510,106.7410', 'Reviewed'],
            ['Doni Kurniawan',  '081234567009', 'Ada TV dan kulkas dibuang di pinggir jalan',              '-6.2000,106.9500', 'Pending'],
            ['Lina Marlina',    '081234567010', 'Sudah 3 hari tong sampah tidak dikosongkan petugas',      '-6.2010,106.9510', 'Dispatched'],
            ['Agus Setiawan',   '081234567011', 'Tumpukan sampah mengundang lalat dan nyamuk',             '-6.1955,106.8325', 'Pending'],
            ['Rina Susanti',    '081234567012', 'Warga membakar sampah asap mengganggu pernapasan',        '-6.2395,106.8015', 'Resolved'],
            ['Wahyu Nugroho',   '081234567013', 'Tong komunal RT 05 meluber sampai ke badan jalan',        '-6.1215,106.8115', 'Pending'],
            ['Yuni Astuti',     '081234567014', 'Truk sampah tidak datang sesuai jadwal minggu ini',       '-6.1515,106.7415', 'Reviewed'],
            ['Hendra Gunawan',  '081234567015', 'Material bangunan dibuang sembarangan di pinggir jalan',  '-6.2015,106.9515', 'Pending'],
            ['Nita Permata',    '081234567016', 'Sisa sayuran pasar pagi tidak diangkut sampai sore',      '-6.1960,106.8330', 'Resolved'],
            ['Bayu Saputra',    '081234567017', 'Tong sampah di RT 08 hilang diduga dicuri',               '-6.2400,106.8020', 'Pending'],
            ['Citra Dewi',      '081234567018', 'Sampah sisa banjir masih berserakan di jalan raya',       '-6.1220,106.8120', 'Dispatched'],
            ['Fajar Hidayat',   '081234567019', 'Kontainer besar di terminal sudah kritis penuh',           '-6.1520,106.7420', 'Pending'],
            ['Gita Lestari',    '081234567020', 'Seluruh area pasar tradisional dipenuhi tumpukan sampah', '-6.2020,106.9520', 'Reviewed'],
        ];

        foreach ($reports as $r) {
            DB::table('sanitation_reports')->insertOrIgnore([
                'reporter_name'      => $r[0],
                'reporter_phone'     => $r[1],
                'issue_description'  => $r[2],
                'geo_coordinate'     => $r[3],
                'verification_status'=> $r[4],
            ]);
        }
    }
}