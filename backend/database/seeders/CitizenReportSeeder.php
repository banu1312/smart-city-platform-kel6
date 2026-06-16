<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitizenReportSeeder extends Seeder {
    public function run(): void {
        $titles = [
            'Sampah menumpuk di trotoar',
            'Tong sampah meluber',
            'Pembuangan sampah liar',
            'Tong sampah terbakar',
            'Sampah di tepi sungai',
            'Tong sampah rusak',
            'Sampah plastik menumpuk',
            'Bau busuk dari TPS',
            'Sampah elektronik liar',
            'Tong penuh tidak diangkut',
            'Lalat dari tumpukan sampah',
            'Sampah dibakar sembarangan',
            'Overflow tong komunal',
            'Jadwal angkut tidak tepat',
            'Sampah konstruksi liar',
            'Sampah pasar tidak dibersihkan',
            'Tong sampah dicuri',
            'Sampah banjir tidak dibersihkan',
            'Kontainer sampah penuh',
            'Penumpukan di area pasar',
        ];

        $descriptions = [
            'Tumpukan sampah besar menghalangi jalan kaki warga sekitar',
            'Tong di depan lokasi sudah penuh dan mengeluarkan bau tidak sedap',
            'Warga buang sampah sembarangan di pinggir jalan tanpa menggunakan tong',
            'Terlihat api kecil dari dalam tong sampah yang membahayakan lingkungan',
            'Penumpukan sampah besar di tepi sungai mengancam kualitas air',
            'Tong sampah berlubang sehingga sampah berserakan ke mana-mana',
            'Tumpukan sampah plastik di area perumahan sudah lama tidak diangkut',
            'Bau sangat menyengat berasal dari area pembuangan sampah sementara',
            'Ditemukan TV, kulkas, dan elektronik bekas dibuang di pinggir jalan',
            'Tong sampah sudah lebih dari 3 hari tidak dikosongkan oleh petugas',
            'Tumpukan sampah yang membusuk mengundang lalat dan nyamuk dalam jumlah banyak',
            'Warga membakar sampah secara sembarangan sehingga asap mengganggu pernapasan',
            'Tong sampah komunal di RT setempat sudah meluber hingga ke badan jalan',
            'Truk pengangkut sampah tidak datang sesuai jadwal yang sudah ditentukan',
            'Material sisa bangunan dibuang sembarangan di pinggir jalan umum',
            'Sisa sayuran dan buah dari pasar pagi tidak diangkut hingga sore hari',
            'Tong sampah di RT ini dilaporkan hilang atau dicuri oleh oknum tidak bertanggung jawab',
            'Sampah sisa banjir masih berserakan di jalan dan belum dibersihkan petugas',
            'Kontainer sampah besar di area terminal sudah dalam kondisi kritis penuh',
            'Seluruh area pasar tradisional dipenuhi sampah yang tidak segera dibersihkan',
        ];

        $statuses  = ['pending', 'in_progress', 'resolved'];
        $latitudes  = [-6.1944, -6.1950, -6.2383, -6.2390, -6.1200,
                       -6.1210, -6.1500, -6.1510, -6.2000, -6.2010];
        $longitudes = [106.8318, 106.8320, 106.8000, 106.8010, 106.8100,
                       106.8110, 106.7400, 106.7410, 106.9500, 106.9510];

        $records = [];
        for ($i = 0; $i < 200; $i++) {
            $zoneId  = ($i % 5) + 1;
            $titleIdx = $i % count($titles);
            $records[] = [
                'zone_id'     => $zoneId,
                'title'       => $titles[$titleIdx],
                'description' => $descriptions[$titleIdx],
                'photo_url'   => null,
                'latitude'    => $latitudes[$i % count($latitudes)] + (rand(-9, 9) / 1000),
                'longitude'   => $longitudes[$i % count($longitudes)] + (rand(-9, 9) / 1000),
                'status'      => $statuses[$i % count($statuses)],
                'created_at'  => date('Y-m-d H:i:s', strtotime('2026-06-01') + ($i * 1800)),
                'updated_at'  => date('Y-m-d H:i:s', strtotime('2026-06-01') + ($i * 1800)),
            ];
        }

        foreach (array_chunk($records, 50) as $chunk) {
            DB::table('citizen_reports')->insert($chunk);
        }
    }
}