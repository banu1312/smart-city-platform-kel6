<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MLClient {
    private string $baseUrl;

    public function __construct() {
        $this->baseUrl = env('ML_SERVICE_URL', 'http://python-ml:5000');
    }

    /**
     * Prediksi jam sampai penuh
     * Hit: POST /predict/fill-rate
     */
    public function predictFillRate(array $data): ?float {
        try {
            $response = Http::timeout(5)->post("{$this->baseUrl}/predict/fill-rate", [
                'jam'            => $data['jam'],
                'suhu_cuaca'     => $data['suhu_cuaca'],
                'volume_sekarang'=> $data['volume_sekarang'],
                'tipe_lokasi'    => $data['tipe_lokasi']   ?? 'Perumahan',
                'is_weekend'     => $data['is_weekend']    ?? 0,
                'ada_event'      => $data['ada_event']     ?? 0,
            ]);

            if ($response->successful()) {
                return $response->json('hours_until_full');
            }

            Log::warning('[ML] fill-rate prediction failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('[ML] fill-rate request error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Klasifikasi prioritas penjemputan
     * Hit: POST /predict/priority
     */
    public function predictPriority(array $data): ?string {
        try {
            $response = Http::timeout(5)->post("{$this->baseUrl}/predict/priority", [
                'volume_sekarang' => $data['volume_sekarang'],
                'kadar_metana'    => $data['kadar_metana'],
                'laporan_warga'   => $data['laporan_warga'] ?? 0,
            ]);

            if ($response->successful()) {
                return $response->json('pickup_priority');
            }

            Log::warning('[ML] priority prediction failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('[ML] priority request error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Deteksi anomali/vandalisme
     * Hit: POST /detect/anomaly
     */
    public function detectAnomaly(array $data): ?bool {
        try {
            $response = Http::timeout(5)->post("{$this->baseUrl}/detect/anomaly", [
                'jarak_ultrasonik' => $data['jarak_ultrasonik'],
                'delta_volume_sec' => $data['delta_volume_sec'],
                'suhu_cuaca'       => $data['suhu_cuaca'],
            ]);

            if ($response->successful()) {
                return $response->json('is_anomaly');
            }

            Log::warning('[ML] anomaly detection failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('[ML] anomaly request error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cek apakah ML service nyala
     * Hit: GET /health
     */
    public function isHealthy(): bool {
        try {
            $response = Http::timeout(3)->get("{$this->baseUrl}/health");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}