# IoT & Messaging — TrashTrack

Modul IoT untuk sistem **TrashTrack Smart Waste Management**. Bertanggung jawab atas simulasi sensor tong sampah pintar, konfigurasi MQTT broker, pipeline data via Node-RED, dan pengiriman data real-time ke platform.

---

## Arsitektur

```
┌─────────────────────────────────────────────────────┐
│                   DEVICE LAYER                      │
│                                                     │
│  Wokwi (ESP32 + HC-SR04 + MQ-2)                    │
│  atau simulator.py (Python)                         │
└──────────────────────┬──────────────────────────────┘
                       │ MQTT Publish
                       ▼
┌─────────────────────────────────────────────────────┐
│               COMMUNICATION LAYER                   │
│                                                     │
│  Lokal  : Mosquitto Broker (localhost:1883)         │
│  Publik : HiveMQ Broker (broker.hivemq.com:1883)   │
└──────────────────────┬──────────────────────────────┘
                       │ Node-RED subscribe ke KEDUA broker
                       ▼
┌─────────────────────────────────────────────────────┐
│                 SERVICE LAYER                       │
│                                                     │
│  Node-RED (localhost:1880)                          │
│  → OAuth Token → REST API Telemetry                 │
│  → Express Gateway (localhost:3000)  ← WAJIB lewat sini│
│  → Laravel Backend (localhost:8000)                 │
└─────────────────────────────────────────────────────┘
```

> Node-RED men-subscribe **dua** broker secara paralel — `Mosquitto Lokal` (untuk `simulator.py`, zona 1-4) dan `HiveMQ Publik` (untuk Wokwi, zona 5) — keduanya diproses lewat pipeline transform yang sama sebelum dikirim ke Gateway.

### Topik MQTT

| Topik | Deskripsi |
|---|---|
| `city/zone1/waste` | Data sensor tong sampah zona 1 (simulator.py) |
| `city/zone2/waste` | Data sensor tong sampah zona 2 (simulator.py) |
| `city/zone3/waste` | Data sensor tong sampah zona 3 (simulator.py) |
| `city/zone4/waste` | Data sensor tong sampah zona 4 (simulator.py) |
| `city/zone5/waste` | Data sensor tong sampah zona 5 — khusus Wokwi/ESP32, lewat broker publik HiveMQ (dipisah dari zona 1-4 supaya tidak rebutan baris bin yang sama) |
| `city/+/waste` | Wildcard — subscribe semua zona |
| `city/{zone}/command` | Subscribe oleh device — kirim `{"action":"recalibrate"}` ke topik ini (mis. `city/zone5/command`) untuk memicu kalibrasi ulang tinggi tong tanpa restart device |

### Skema Payload JSON

```json
{
  "zone": "zone5",
  "bin_id": "BIN-Z5-01",
  "fill_level": 45.3,
  "gas_level": 312,
  "temperature": 32.0,
  "calibrated_height": 98.3,
  "is_calibration": false,
  "latitude": -6.12,
  "longitude": 106.81,
  "status": "normal",
  "timestamp": "2026-06-15T00:49:52.869655"
}
```

| Field | Tipe | Keterangan |
|---|---|---|
| `zone` | string | ID zona kota |
| `bin_id` | string | ID unik tong sampah |
| `fill_level` | float | Persentase kepenuhan tong (0–100%) |
| `gas_level` | int | Kadar gas dalam ppm |
| `temperature` | float | Suhu dalam °C |
| `calibrated_height` | float | Tinggi tong (cm) hasil auto-calibration saat boot / command `recalibrate`. Dipakai sebagai acuan hitung `fill_level` |
| `is_calibration` | boolean | `true` selama 5 detik pertama setelah boot/recalibrate (proses kalibrasi baru selesai) |
| `latitude` / `longitude` | float | Koordinat lokasi tong — konstanta tetap, disamakan dengan `location_coordinate` bin terkait di `TrashBinSeeder.php` (bin tidak berpindah tempat, sehingga tidak dibaca dari modul GPS) |
| `status` | string | `normal` / `warning` / `critical` |
| `timestamp` | string | ISO 8601 timestamp (simulator.py) atau `millis()` sejak boot (Wokwi/ESP32) |

### Threshold Status

| Status | Kondisi |
|---|---|
| `normal` | fill < 70% DAN gas < 400 ppm |
| `warning` | fill ≥ 70% ATAU gas ≥ 400 ppm |
| `critical` | fill ≥ 90% ATAU gas ≥ 800 ppm |

> Kondisi pakai operator **OR**, bukan AND — kalau salah satu metrik (fill **atau** gas) sudah masuk ambang, status langsung naik meskipun metrik yang lain masih normal.

### Auto-Calibration Tinggi Tong

Tinggi tong (`calibrated_height`) **tidak lagi hardcoded**, melainkan diukur otomatis oleh device:

1. **Saat boot** — ESP32 membaca sensor ultrasonik 5x (jeda 200ms), lalu mengambil rata-ratanya sebagai tinggi tong acuan ("tong kosong"). `fill_level` dihitung relatif terhadap acuan ini.
2. **Saat menerima command `recalibrate`** — kirim pesan berikut ke topik `city/{zone}/command` (mis. `city/zone5/command`) lewat broker yang sesuai:
   ```bash
   mosquitto_pub -h broker.hivemq.com -p 1883 -t "city/zone5/command" -m '{"action":"recalibrate"}'
   ```
   Device akan mengukur ulang 5x dan memperbarui `calibrated_height` — dipakai misalnya setelah tong dikosongkan petugas, supaya acuan "kosong"-nya ikut update tanpa perlu restart/reflash device.

> Catatan: nilai acuan ini di-snapshot sekali (bukan terus mengikuti perubahan jarak secara live) — kalau sensor di posisi yang nggak realistis pas boot/recalibrate (misal kebentur, atau di Wokwi slider `distance` belum diset), `fill_level` bisa jadi nggak masuk akal sampai di-`recalibrate` ulang dengan posisi sensor yang benar.

---

## Struktur Folder

```
iot/
├── node-red-data/          # Konfigurasi & flow Node-RED (Sprint 2)
│   ├── flows.json          # Flow pipeline IoT → Laravel
│   ├── flows_cred.json     # Kredensial flow (tidak di-commit plaintext)
│   ├── settings.js         # Konfigurasi Node-RED
│   └── package.json        # Dependensi Node-RED
├── wokwi/
│   ├── diagram.json        # Skema sirkuit ESP32 untuk Wokwi
│   └── sketch.ino          # Firmware Arduino ESP32
├── data/                   # Persistence Mosquitto (auto-generated)
├── log/                    # Log broker (auto-generated)
├── venv/                   # Python virtual environment (tidak di-commit)
├── .env                    # Kredensial & konfigurasi (tidak di-commit)
├── .env.example            # Template .env
├── mosquitto.conf          # Konfigurasi MQTT broker
├── passwd                  # File kredensial Mosquitto (tidak di-commit)
├── simulator.py            # Simulator sensor Python
└── README.md               # Dokumentasi ini
```

---

## Prasyarat

- **Python** 3.9+ dengan pip
- **Mosquitto** 2.x ([download](https://mosquitto.org/download/))
- **Node-RED** ([download](https://nodered.org/docs/getting-started/windows))
- **Windows**: tambahkan `C:\Program Files\mosquitto` ke PATH

---

## Setup & Instalasi

### 1. Clone & Masuk ke Folder

```bash
git clone <repo-url>
cd smart-city-platform-kel6/iot
```

### 2. Buat Virtual Environment

```bash
python -m venv venv

# Windows
venv\Scripts\activate

# Linux/Mac
source venv/bin/activate
```

### 3. Install Dependensi Python

```bash
pip install paho-mqtt python-dotenv
```

### 4. Buat File `.env`

Salin dari template:

```bash
cp .env.example .env
```

Isi `.env`:

```dotenv
MQTT_HOST=localhost
MQTT_PORT=1883
MQTT_USERNAME=iot_device
MQTT_PASSWORD=trashtrack123
PUBLISH_INTERVAL=30
```

### 5. Setup Kredensial Mosquitto

```bash
# Windows (jalankan di folder iot/)
"C:\Program Files\mosquitto\mosquitto_passwd.exe" -c passwd iot_device
# masukkan password: trashtrack123
```

---

## Menjalankan Mosquitto Broker (Lokal)

Buka terminal pertama, jalankan broker dengan konfigurasi project:

```bash
# Windows
$env:PATH += ";C:\Program Files\mosquitto"
mosquitto -v -c mosquitto.conf
```

Biarkan terminal ini tetap terbuka.

**Output yang diharapkan:**
```
mosquitto version 2.1.2 starting
Opening ipv4 listen socket on port 1883.
```

---

## Menjalankan Simulator Python

Buka terminal kedua:

```bash
cd iot/
venv\Scripts\activate        # Windows
python simulator.py
```

**Output yang diharapkan:**
```
TrashTrack Sensor Simulator dimulai. Publish setiap 30 detik...
Zona aktif: zone1, zone2, zone3, zone4
[MQTT] Terhubung ke broker localhost:1883 sebagai 'iot_device'
[PUBLISH] city/zone1/waste -> {'zone': 'zone1', 'fill_level': 21.1, 'status': 'normal', ...}
[PUBLISH] city/zone2/waste -> {'zone': 'zone2', 'fill_level': 23.4, 'status': 'normal', ...}
```

Tekan `Ctrl+C` untuk berhenti.

---

## Node-RED Pipeline (Sprint 2)

Node-RED bertanggung jawab menjembatani data MQTT dari broker ke REST API Laravel via Express Gateway.

### Alur Flow

```
MQTT In (city/+/waste) — Mosquitto Lokal DAN HiveMQ Publik, dua broker terpisah
  → Parse & validasi payload
  → Lookup bin mapping (zone -> trash_bin_id)
  → Transform payload sesuai kontrak StoreTelemetryRequest.php
  → Ambil token OAuth (cache 1 jam)
  → POST /iot/telemetry ke Express Gateway (localhost:3000) — BUKAN langsung ke Laravel
  → Gateway forward ke Laravel (localhost:8000)
  → Handle response & retry jika gagal (auto retry tiap 60 detik)
```

### Menjalankan Node-RED

```bash
# Install Node-RED (sekali saja)
npm install -g --unsafe-perm node-red

# Jalankan dengan konfigurasi project
node-red --userDir ./iot/node-red-data
```

Node-RED akan berjalan di `http://localhost:1880`.

### Prasyarat Sebelum Menjalankan

- Laravel backend jalan di `http://localhost:8000` (`php artisan serve`)
- MySQL jalan dan migration sudah dijalankan (`php artisan migrate`)
- Express Gateway jalan di `http://localhost:3000`
- OAuth server jalan (untuk ambil token)

### Test Endpoint Telemetry

**Langsung ke Laravel (bypass Gateway)** — buat isolasi masalah, cek apakah Laravel-nya sendiri sehat:
```bash
# Windows PowerShell
Invoke-RestMethod -Uri "http://localhost:8000/api/iot/telemetry" `
  -Method POST `
  -ContentType "application/json" `
  -Body '{"trash_bin_id":1,"distance_cm":30}'

# Expected response:
# {"status":"success","code":201,"data":{...}}
```

**Lewat Gateway (jalur yang sebenarnya dipakai Node-RED)** — butuh token OAuth dulu (lihat node "Get OAuth Token" di flow), jadi paling gampang dites lewat `simulator.py` atau Wokwi langsung daripada manual curl. Kalau mau cek Gateway-nya hidup tanpa token:
```powershell
(Invoke-RestMethod http://localhost:3000/health).data.services
```
Pastikan minimal `backend-smart-bin` statusnya `"ok"` sebelum tes kirim data dari device.

---

## Menjalankan Simulasi Wokwi (ESP32 Virtual)

Wokwi digunakan sebagai simulator hardware ESP32 + sensor tanpa perlu perangkat fisik.

### Komponen Sirkuit

| Komponen | Model | Pin ESP32 | Fungsi |
|---|---|---|---|
| Mikrokontroler | ESP32 DevKitC | — | Prosesor + WiFi |
| Sensor Ultrasonik | HC-SR04 | TRIG=GPIO12, ECHO=GPIO14 | Deteksi fill level + auto-calibration |
| Sensor Gas (MQ-2) | Potentiometer *(sim)* | GPIO 34 | Simulasi kadar gas |
| LED Status | LED Merah | GPIO 2 | Indikator critical (nyala kalau fill **atau** gas critical — OR, bukan AND) |

> Wokwi tidak memiliki komponen MQ-2 native, sehingga digunakan **Potentiometer** sebagai pengganti analog sensor gas.
>
> Wokwi juga tidak punya komponen GPS interaktif — koordinat lokasi (`BIN_LATITUDE`/`BIN_LONGITUDE`) di-set sebagai **konstanta** di `sketch.ino` (bin tidak berpindah tempat, jadi tidak perlu modul GPS sungguhan). Kalau perlu pindah ke bin lain, ubah dua nilai ini sesuai `location_coordinate` bin tujuan di `TrashBinSeeder.php`.

### Langkah Menjalankan di Wokwi

1. Buka [https://wokwi.com](https://wokwi.com) → **New Project** → **ESP32**
2. Paste isi `wokwi/diagram.json` ke tab **diagram.json**
3. Paste isi `wokwi/sketch.ino` ke tab **sketch.ino**
4. Isi tab **libraries.txt**:
   ```
   PubSubClient
   ArduinoJson
   ```
5. Sebelum **Start Simulation**, klik komponen **HC-SR04** dan set slider `Distance` ke nilai realistis (mis. 100-150cm = tong kosong) — nilai ini bakal dikunci jadi `calibrated_height` pas boot
6. Klik **▶ Start Simulation**
7. Data ESP32 ini terdaftar sebagai **zona 5** (`city/zone5/waste`), terpisah dari `simulator.py` yang memegang zona 1-4 — supaya tidak rebutan baris bin yang sama di database
8. Data ter-publish ke `broker.hivemq.com` setiap 2 detik

### Subscribe Data dari Wokwi (Lokal)

```bash
# Windows
$env:PATH += ";C:\Program Files\mosquitto"
mosquitto_sub -h broker.hivemq.com -p 1883 -t "city/#" -v
```

**Output yang diharapkan:**
```
city/zone5/waste {"zone":"zone5","bin_id":"BIN-Z5-01","fill_level":45.3,"gas_level":312,"calibrated_height":98.3,"is_calibration":false,"latitude":-6.12,"longitude":106.81,"status":"normal",...}
```

### Simulasi Skenario Sensor

| Skenario | Cara di Wokwi | Status |
|---|---|---|
| Tong hampir penuh | Geser slider `Distance` di HC-SR04 mendekati `calibrated_height` (mis. dari 100cm ke 10cm) | `critical` + LED menyala |
| Gas berbahaya | Putar Potentiometer ke maksimum | `critical` + LED menyala |
| Kondisi normal | Distance dekat dengan `calibrated_height` awal, Potentiometer rendah | `normal` |
| Kalibrasi ulang | Set `Distance` ke posisi "kosong" yang baru, lalu publish `{"action":"recalibrate"}` ke `city/zone5/command` | `calibrated_height` ter-update, `is_calibration` sempat `true` |

> **Penting:** kalau LED tetap nyala walau `Distance` sudah dikembalikan ke posisi normal, cek nilai `gas` di Serial Monitor — status `critical` dipicu kalau **salah satu** dari fill **atau** gas melewati ambang (OR), jadi potentiometer gas juga perlu diturunkan untuk status balik `normal`.

---

## Verifikasi & Testing

### Test 1 — Broker Lokal Berjalan

```bash
mosquitto_sub -h localhost -p 1883 -u iot_device -P trashtrack123 -t "city/#" -v
```

Jalankan `simulator.py` di terminal lain → data muncul setiap 30 detik.

### Test 2 — Data dari Wokwi

```bash
mosquitto_sub -h broker.hivemq.com -p 1883 -t "city/zone5/waste" -v
```

Jalankan simulasi Wokwi → data muncul setiap 2 detik.

### Test 3 — Autentikasi Broker

```bash
# Harus ditolak (tanpa kredensial)
mosquitto_sub -h localhost -p 1883 -t "city/#"
# Output: Connection Refused: not authorised
```

### Test 4 — Pipeline Node-RED ke Laravel (lewat Gateway)

```bash
# Pastikan Mosquitto, Laravel (8000), Express Gateway (3000), OAuth server, dan RabbitMQ
# semuanya jalan, lalu jalankan Node-RED
# Cek di Node-RED debug panel node "Sukses":
# - Dari simulator.py: response 201 tiap ~30 detik
# - Dari Wokwi: response 201 tiap ~2 detik
# Field "responseUrl" di hasil debug harus "http://localhost:3000/iot/telemetry"
# (lewat Gateway) — kalau ke :8000 langsung berarti flow belum lewat Gateway
```

### Test 5 — Command Recalibrate

```bash
mosquitto_pub -h broker.hivemq.com -p 1883 -t "city/zone5/command" -m '{"action":"recalibrate"}'
```

Cek Serial Monitor Wokwi, harus muncul `[Re-Calibration] Tinggi baru: XX.X cm` dan payload publish berikutnya punya `calibrated_height` yang ter-update serta `is_calibration: true` sesaat.

---

## Keamanan

- Kredensial MQTT **tidak hardcoded** — disimpan di `.env`
- File `passwd` dan `.env` masuk `.gitignore` — tidak di-commit ke repo
- `allow_anonymous false` di `mosquitto.conf` — koneksi tanpa kredensial ditolak
- Token OAuth di-cache di Node-RED dan diperbarui otomatis setiap 1 jam

---

## Handoff ke Tim Lain

Data telemetry dari modul ini sudah tersimpan ke database `smartcity` via pipeline:

- **Tabel:** `sensor_logs` dan `trash_bins`
- **Endpoint:** `POST /iot/telemetry` (lewat Express Gateway `:3000`, diteruskan ke Laravel `:8000`)
- **Topik MQTT subscribe:** `city/+/waste` (di dua broker, lihat tabel Topik MQTT)
- **Topik command (device-side):** `city/{zone}/command`, action yang didukung: `recalibrate`
- **Broker lokal:** `localhost:1883` (username: `iot_device`) — zona 1-4, `simulator.py`
- **Broker publik (Wokwi):** `broker.hivemq.com:1883` — zona 5, ESP32
- **Format payload:** JSON sesuai skema di atas (termasuk `calibrated_height`, `is_calibration`, `latitude`, `longitude`)
- **Frekuensi:** setiap 30 detik (simulator) / 2 detik (Wokwi)