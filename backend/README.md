# Cara Setup Backend

```bash
# 1. Clone repo dan masuk folder backend
cd smart-city-platform-kel6/backend

# 2. Install dependencies
composer install --ignore-platform-req=ext-sockets

# 3. Setup .env
cp .env.example .env

# Edit:
# DB_HOST=127.0.0.1
# DB_DATABASE=smartcity
# DB_USERNAME=root
# DB_PASSWORD=

# 4. Jalankan migration
php artisan migrate

# 5. Isi data dummy
php artisan db:seed

# 6. Setup storage untuk foto
php artisan storage:link

# 7. Jalankan server
php artisan serve --port=8000
```

---

## Daftar Endpoint Lengkap

### Base URL

**Local**
```text
http://localhost:8000/api
```

**Server**
```text
http://103.147.92.134:8000/api
```

---

## Health Checks

| Method | Endpoint | Deskripsi |
|----------|----------|----------|
| GET | `/smart-bin/health` | Status Smart Bin Service |
| GET | `/fleet/health` | Status Fleet Service |
| GET | `/citizen-report/health` | Status Citizen Report Service |

---

## Smart Bin Service

| Method | Endpoint | Auth | Deskripsi |
|----------|----------|----------|----------|
| GET | `/bins` | JWT | List semua tong + status terkini (cached 30s) |
| GET | `/bins/{id}` | JWT | Detail tong + 5 log sensor terakhir (cached 30s) |
| PUT | `/bins/{id}/maintenance` | JWT | Set tong ke status Maintenance |
| POST | `/iot/telemetry` | API Key | Terima data sensor dari Node-RED |

### Payload POST `/api/iot/telemetry`

```json
{
  "trash_bin_id": 1,
  "distance_cm": 20.5,
  "methane_ppm": 12.3,
  "temperature_c": 31.5,
  "raw_payload": {
    "source": "wokwi-esp32",
    "zone": "zone1"
  }
}
```

---

## Fleet Service

| Method | Endpoint | Auth | Deskripsi |
|----------|----------|----------|----------|
| GET | `/fleet/trucks` | JWT | Daftar truk + jadwal aktif |
| POST | `/fleet/driver-checkin` | JWT | Supir ubah status jadi Available |
| PUT | `/fleet/schedules/{id}/status` | JWT | Supir update status jadwal |
| POST | `/fleet/auto-dispatch` | Internal | Terima prediksi dari Python ML |

### Payload POST `/api/fleet/auto-dispatch`

```json
{
  "trash_bin_id": 3,
  "pickup_priority": "Urgent",
  "hours_until_full": 1.5
}
```

### Nilai `pickup_priority`

- Low
- Medium
- Urgent
- Critical

### Nilai `execution_status`

- Pending
- In-Progress
- Completed

---

## Citizen Report Service

| Method | Endpoint | Auth | Deskripsi |
|----------|----------|----------|----------|
| POST | `/reports` | JWT | Warga submit laporan (multipart/form-data) |
| POST | `/reports/{id}/verify` | JWT + Admin | Admin verifikasi laporan |
| POST | `/reports/{id}/dispatch` | JWT + Admin | Admin assign truk ke laporan |

### Form Fields POST `/api/reports`

| Field | Tipe | Keterangan |
|---------|---------|---------|
| `reporter_name` | string | Required |
| `reporter_phone` | string | Optional |
| `geo_coordinate` | string | Required, format `"-6.1944,106.8318"` |
| `issue_description` | string | Optional |
| `photo` | file | Required, max 2MB, jpg/jpeg/png |

---

## RabbitMQ Events yang Dipublish Backend

| Routing Key | Trigger | Consumer | Payload |
|------------|----------|----------|----------|
| `bin.updated` | Setiap data IoT masuk | Python ML | `bin_id, volume_pct, methane, temperature, delta_volume` |
| `vandalism.alert` | `delta_volume < -5` atau `suhu > 36` | Fleet Service | `bin_id, data sensor` |
| `report.submitted` | Warga submit laporan | ML Priority | `report_id, geo_coordinate` |

---

## Struktur Database

### Smart Bin

```text
trash_bins
└── sensor_logs
```

- `trash_bins` → data master tong sampah
- `sensor_logs` → riwayat data sensor IoT

### Fleet

```text
trucks
└── schedules
```

- `trucks` → data armada truk
- `schedules` → jadwal penjemputan

### Citizen Report

```text
sanitation_reports
```

- `sanitation_reports` → laporan warga
