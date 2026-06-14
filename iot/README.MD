# IoT & Messaging — TrashTrack

Modul IoT untuk sistem **TrashTrack Smart Waste Management**. Bertanggung jawab atas simulasi sensor tong sampah pintar, konfigurasi MQTT broker, dan pengiriman data real-time ke platform.

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
                       │ Subscribe / Bridge
                       ▼
┌─────────────────────────────────────────────────────┐
│                 SERVICE LAYER                       │
│                                                     │
│  Node-RED / Backend Service (Sprint 2)              │
│  REST API → API Gateway (Sprint 2)                  │
└─────────────────────────────────────────────────────┘
```

### Topik MQTT

| Topik | Deskripsi |
|---|---|
| `city/zone1/waste` | Data sensor tong sampah zona 1 |
| `city/zone2/waste` | Data sensor tong sampah zona 2 |
| `city/zone3/waste` | Data sensor tong sampah zona 3 |
| `city/zone4/waste` | Data sensor tong sampah zona 4 |
| `city/+/waste` | Wildcard — subscribe semua zona |

### Skema Payload JSON

```json
{
  "zone": "zone1",
  "bin_id": "BIN-Z1-01",
  "fill_level": 45.3,
  "gas_level": 312,
  "temperature": 32.0,
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
| `status` | string | `normal` / `warning` / `critical` |
| `timestamp` | string | ISO 8601 timestamp |

### Threshold Status

| Status | Kondisi |
|---|---|
| `normal` | fill < 70% DAN gas < 400 ppm |
| `warning` | fill ≥ 70% ATAU gas ≥ 400 ppm |
| `critical` | fill ≥ 90% ATAU gas ≥ 800 ppm |

---

## Struktur Folder

```
iot/
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

## Menjalankan Simulasi Wokwi (ESP32 Virtual)

Wokwi digunakan sebagai simulator hardware ESP32 + sensor tanpa perlu perangkat fisik.

### Komponen Sirkuit

| Komponen | Model | Pin ESP32 | Fungsi |
|---|---|---|---|
| Mikrokontroler | ESP32 DevKitC | — | Prosesor + WiFi |
| Sensor Ultrasonik | HC-SR04 | TRIG=GPIO12, ECHO=GPIO14 | Deteksi fill level |
| Sensor Gas (MQ-2) | Potentiometer *(sim)* | GPIO 34 | Simulasi kadar gas |
| LED Status | LED Merah | GPIO 2 | Indikator critical |

> Wokwi tidak memiliki komponen MQ-2 native, sehingga digunakan **Potentiometer** sebagai pengganti analog sensor gas.

### Langkah Menjalankan di Wokwi

1. Buka [https://wokwi.com](https://wokwi.com) → **New Project** → **ESP32**
2. Paste isi `wokwi/diagram.json` ke tab **diagram.json**
3. Paste isi `wokwi/sketch.ino` ke tab **sketch.ino**
4. Isi tab **libraries.txt**:
   ```
   PubSubClient
   ArduinoJson
   ```
5. Klik **▶ Start Simulation**
6. Data akan ter-publish ke `broker.hivemq.com` setiap 2 detik

### Subscribe Data dari Wokwi (Lokal)

```bash
# Windows
$env:PATH += ";C:\Program Files\mosquitto"
mosquitto_sub -h broker.hivemq.com -p 1883 -t "city/#" -v
```

**Output yang diharapkan:**
```
city/zone1/waste {"zone":"zone1","bin_id":"BIN-Z1-01","fill_level":45.3,"gas_level":312,"status":"normal",...}
```

### Simulasi Skenario Sensor

| Skenario | Cara di Wokwi | Status |
|---|---|---|
| Tong hampir penuh | Klik HC-SR04 → geser `distance` ke 5 cm | `critical` + LED menyala |
| Gas berbahaya | Putar Potentiometer ke maksimum | `critical` + LED menyala |
| Kondisi normal | Distance ~50cm, Potentiometer ~30% | `normal` |

---

## Verifikasi & Testing

### Test 1 — Broker Lokal Berjalan

```bash
mosquitto_sub -h localhost -p 1883 -u iot_device -P trashtrack123 -t "city/#" -v
```

Jalankan `simulator.py` di terminal lain → data muncul setiap 30 detik.

### Test 2 — Data dari Wokwi

```bash
mosquitto_sub -h broker.hivemq.com -p 1883 -t "city/zone1/waste" -v
```

Jalankan simulasi Wokwi → data muncul setiap 2 detik.

### Test 3 — Autentikasi Broker

```bash
# Harus ditolak (tanpa kredensial)
mosquitto_sub -h localhost -p 1883 -t "city/#"
# Output: Connection Refused: not authorised
```

---

## Keamanan

- Kredensial MQTT **tidak hardcoded** — disimpan di `.env`
- File `passwd` dan `.env` masuk `.gitignore` — tidak di-commit ke repo
- `allow_anonymous false` di `mosquitto.conf` — koneksi tanpa kredensial ditolak

---

## Handoff ke Sprint 2

Data yang dipublish dari modul ini siap dikonsumsi oleh tim Sprint 2:

- **Topik subscribe:** `city/+/waste`
- **Broker lokal:** `localhost:1883` (username: `iot_device`)
- **Broker publik (Wokwi):** `broker.hivemq.com:1883`
- **Format payload:** JSON sesuai skema di atas
- **Frekuensi:** setiap 30 detik (simulator) / 2 detik (Wokwi)