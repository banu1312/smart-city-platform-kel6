# Smart City Platform Kelompok 6

## Cara Setup & Jalankan Backend

### 1. Masuk ke Folder Backend

```bash
cd smart-city-platform-kel6/backend
```

### 2. Install Dependencies

```bash
composer install --ignore-platform-req=ext-sockets
```

### 3. Copy dan Konfigurasi Environment

```bash
cp .env.example .env
```

Edit file `.env` dan sesuaikan:

```env
DB_HOST=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

### 4. Jalankan Migration

```bash
php artisan migrate
```

### 5. Jalankan Seeder

```bash
php artisan db:seed
```

### 6. Jalankan Server

```bash
php artisan serve --port=8000
```

---

# Daftar Endpoint Lengkap

**Base URL**

```text
http://localhost:8000/api
```

---

## Smart Bin Service

| Method | Endpoint | Deskripsi | Body |
|----------|----------|----------|----------|
| GET | `/smart-bin/health` | Health check | - |
| GET | `/bins` | List semua tong + status terkini | - |
| POST | `/bins` | Daftarkan tong baru | `zone_id`, `location`, `capacity_kg` |
| GET | `/bins/{id}/history` | Riwayat sensor per tong | - |
| POST | `/bins/telemetry` | Data IoT dari Node-RED | `bin_id`, `zone_id`, `fill_level`, `gas_level`, `temperature`, `distance_cm` |

---

## Fleet Service

| Method | Endpoint | Deskripsi | Body |
|----------|----------|----------|----------|
| GET | `/fleet/health` | Health check | - |
| GET | `/fleet/trucks` | Daftar armada truk | - |
| POST | `/fleet/dispatch` | Buat surat tugas supir | `truck_id`, `bin_id`, `zone_id`, `priority`, `triggered_by` |
| PUT | `/fleet/tasks/{id}/status` | Update status tugas | `status` |

---

## Citizen Report Service

| Method | Endpoint | Deskripsi | Body |
|----------|----------|----------|----------|
| GET | `/citizen-report/health` | Health check | - |
| POST | `/reports` | Submit laporan warga | `title`, `zone_id`, `description`, `latitude`, `longitude`, `photo_url` |
| GET | `/reports/zone/{zone_id}` | List laporan per zona | - |

---

# Format Response Standar

```json
{
    "status": "success",
    "code": 200,
    "data": {},
    "message": "Keterangan singkat",
    "timestamp": "2026-06-14T00:00:00+00:00",
    "service": "smart-bin-service"
}
```

---

# Payload IoT

Contoh payload dari Node-RED ke:

```http
POST /api/bins/telemetry
```

```json
{
    "bin_id": 1,
    "zone_id": 1,
    "fill_level": 85.5,
    "gas_level": 150.2,
    "temperature": 32.1,
    "distance_cm": 14.5,
    "is_anomaly": 0
}
```

---

# RabbitMQ Events

| Routing Key | Publisher | Consumer | Trigger |
|------------|------------|------------|------------|
| `bin.telemetry` | Smart Bin | Python ML | Setiap telemetry masuk |
| `bin.alert` | Smart Bin | Fleet Service | `fill_level > 80%` |
| `bin.anomaly` | Smart Bin | Fleet Service | `is_anomaly = true` |
| `fleet.dispatched` | Fleet | Notifikasi / IoT | Truk dikirim |
| `report.submitted` | Citizen Report | ML Priority Route | Laporan warga masuk |

---

