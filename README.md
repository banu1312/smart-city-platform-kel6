# TrashTrack - Smart Waste & Eco-Sanitation Management

Platform smart city untuk manajemen sampah pintar berbasis IoT, Machine Learning, dan microservices. Sistem ini memonitor tong sampah secara real-time, memprediksi waktu penuh, mendeteksi anomali, dan otomatis meng-dispatch truk pengangkut.

## Arsitektur

```
ESP32 / Simulator ──MQTT──> Mosquitto/HiveMQ ──> Node-RED ──> Express Gateway ──> Laravel Backend
                                                                                       |
                                                                                  ┌────┴────┐
                                                                                  |         |
                                                                              MySQL    Python ML
                                                                            (storage)  (predict)
                                                                                  |         |
                                                                                  └────┬────┘
                                                                                       |
                                                                                 Auto-Dispatch
                                                                                 (assign truk)
```

| Service | Port | Tech |
|---------|------|------|
| Express Gateway | 3000 | Node.js, Express, JWT, http-proxy-middleware |
| OAuth Server | 3002 | Node.js, Express, JWT, bcrypt |
| Laravel Backend | 8000 | PHP 8.2, Laravel 12, RabbitMQ |
| Python ML Service | 5000 | Python 3.11, FastAPI, scikit-learn |
| MySQL | 3306 | MySQL 8.0 |
| RabbitMQ | 5672 | RabbitMQ 3.12 |
| Mosquitto | 1883 | Eclipse Mosquitto 2.0 |
| Node-RED | 1880 | Node-RED |

## Prasyarat

- **PHP** 8.2+ dengan extension: pdo_mysql, mbstring, sockets, gd, zip
- **Composer** 2.x
- **Node.js** 18+ dengan npm
- **Python** 3.11+ dengan pip
- **MySQL** 8.0 (lokal atau Docker)
- **Docker Desktop** (untuk MySQL & RabbitMQ, opsional)
- **PlatformIO** (untuk IoT Device, opsional)

## Quick Start (Lokal)

### 1. Clone & Masuk

```bash
git clone https://github.com/banu1312/smart-city-platform-kel6.git
cd smart-city-platform-kel6
```

### 2. Start Infrastruktur (MySQL + RabbitMQ)

Pakai Docker (paling gampang):

```bash
docker run -d --name trashtrack-mysql \
  -e MYSQL_ROOT_PASSWORD=rootpass \
  -e MYSQL_DATABASE=smartcity \
  -p 3306:3306 mysql:8.0

docker run -d --name trashtrack-rabbitmq \
  -p 5672:5672 -p 15672:15672 rabbitmq:3.12-management
```

Atau pakai MySQL lokal (XAMPP/Laragon) — buat database `smartcity` manual.

### 3. Backend Laravel (Terminal 1)

```bash
cd backend
cp .env.example .env
```

Edit `backend/.env` sesuaikan untuk lokal:
```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
ML_SERVICE_URL=http://localhost:5000

DB_HOST=127.0.0.1
DB_DATABASE=smartcity
DB_USERNAME=root
DB_PASSWORD=rootpass        # sesuaikan password MySQL

RABBITMQ_HOST=127.0.0.1
```

Lalu jalankan:
```bash
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve --port=8000
```

> Biarkan terminal ini terbuka. Jangan ditutup.

### 4. OAuth Server (Terminal 2)

```bash
cd oauth-server
cp .env.example .env
npm install
node src/index.js
```

Output: `[OAuth Server] Running on port 3002`

### 5. Python ML Service (Terminal 3)

```bash
cd python-ml-service
python -m venv venv

# Windows
venv\Scripts\activate

# Linux/Mac
source venv/bin/activate

pip install -r requirements.txt
```

**Pertama kali — train model dulu:**
```bash
python generate_data.py
python train_fill_rate.py
python train_priority_route.py
python train_anomaly.py
```

**Jalankan service:**
```bash
python -m uvicorn main:app --host 0.0.0.0 --port 5000
```

Output: `Uvicorn running on http://0.0.0.0:5000`

### 6. Express Gateway (Terminal 4)

```bash
cd express-gateway
cp .env.example .env
```

Edit `express-gateway/.env`:
```env
JWT_SECRET=supersecretkeyjwtpilihanyangpanjangdanaman
BACKEND_URL=http://localhost:8000
PYTHON_ML_URL=http://localhost:5000
OAUTH_SERVER_URL=http://localhost:3002
```

```bash
npm install
node src/index.js
```

Output: `[API Gateway] Running on port 3000`

### 7. Verifikasi Semua Jalan

```bash
# Health check (semua service)
curl http://localhost:3000/health

# Login ambil token
curl -X POST http://localhost:3000/oauth/token \
  -H "Content-Type: application/json" \
  -d '{"grant_type":"password","client_id":"smartcity-app","client_secret":"smartcity-secret","username":"admin","password":"admin123"}'

# Test ambil data bins (ganti <TOKEN> dengan access_token dari response atas)
curl http://localhost:3000/api/bins \
  -H "Authorization: Bearer <TOKEN>"
```

## Setup IoT (Opsional — untuk test end-to-end)

### Opsi A: Python Simulator

```bash
cd iot
python -m venv venv
venv\Scripts\activate
pip install paho-mqtt python-dotenv
cp .env.example .env
```

Edit `iot/.env`:
```env
MQTT_HOST=localhost
MQTT_PORT=1883
MQTT_USERNAME=iot_device
MQTT_PASSWORD=trashtrack123
```

> Butuh Mosquitto broker lokal untuk opsi ini. Jalankan:
> `docker run -d --name trashtrack-mosquitto -p 1883:1883 eclipse-mosquitto:2.0`

```bash
python simulator.py
```

### Opsi B: PlatformIO + Wokwi (ESP32 Virtual)

```bash
cd "IOT Device"
pio run
```

Lalu di VS Code: `F1` > `Wokwi: Start Simulator`

ESP32 akan publish ke `broker.hivemq.com` (publik, tanpa auth).

### Setup Node-RED (Bridge IoT ke Backend)

```bash
cd "IOT Device/node-red-data"
cp .env.example .env
```

Edit `.env`:
```env
OAUTH_URL=http://localhost:3002/oauth/token
GATEWAY_URL=http://localhost:3000/iot/telemetry
OAUTH_CLIENT_ID=iot-device
OAUTH_CLIENT_SECRET=iot-secret
```

```bash
npx node-red -s settings.js
```

Buka `http://localhost:1880` — flow sudah ter-load otomatis.

## Data Flow End-to-End

```
1. IoT Device publish MQTT:
   { bin_id: "BIN-Z1-01", fill_level: 45.3, gas_level: 312, temperature: 32.0,
     calibrated_height: 98.3, latitude: -6.1944, longitude: 106.8318 }

2. Node-RED terima → validate → forward ke Gateway (dengan OAuth token)

3. Gateway verify IoT token → proxy ke Backend /api/iot/telemetry

4. Backend:
   - Lookup trash_bin by bin_code
   - Update: tinggi, latitude, longitude, temperature, volume%, methane
   - Simpan sensor_log
   - Publish ke RabbitMQ (bin.updated)
   - Panggil ML Service:
     - /predict/fill-rate → berapa jam sampai penuh
     - /predict/priority → Low/Medium/Urgent/Critical
     - /detect/anomaly → true/false
   - Jika priority Urgent/Critical → auto-dispatch truk

5. Response kembali ke Node-RED (201 Created)
```

## API Endpoints

### OAuth (via Gateway :3000)

| Method | Path | Body | Deskripsi |
|--------|------|------|-----------|
| POST | `/oauth/token` | `{grant_type, client_id, client_secret, username, password}` | Login / ambil token |
| POST | `/oauth/token` | `{grant_type: "client_credentials", client_id, client_secret}` | Token untuk IoT/service |
| POST | `/oauth/token` | `{grant_type: "refresh_token", refresh_token, client_id, client_secret}` | Refresh token |

### Smart Bin (JWT required)

| Method | Path | Deskripsi |
|--------|------|-----------|
| GET | `/api/bins` | List semua tong + sensor terbaru |
| GET | `/api/bins/:id` | Detail tong + 5 sensor terakhir |
| PUT | `/api/bins/:id/maintenance` | Set status Maintenance |
| POST | `/api/iot/telemetry` | Terima data IoT (via Node-RED) |

### Fleet (JWT required)

| Method | Path | Deskripsi |
|--------|------|-----------|
| GET | `/api/fleet/trucks` | List semua truk + jadwal |
| POST | `/api/fleet/trucks` | Register truk baru |
| POST | `/api/fleet/driver-checkin` | Driver set Available |
| PUT | `/api/fleet/schedules/:id/status` | Update status jadwal |
| POST | `/api/fleet/auto-dispatch` | Terima prediksi ML (otomatis) |

### Citizen Report (JWT required)

| Method | Path | Deskripsi |
|--------|------|-----------|
| POST | `/api/reports` | Warga submit laporan + foto |
| POST | `/api/reports/:id/verify` | Admin verifikasi laporan |
| POST | `/api/reports/:id/dispatch` | Admin dispatch truk |

### ML Prediction (JWT required)

| Method | Path | Input | Output |
|--------|------|-------|--------|
| POST | `/predict/fill-rate` | `{jam, suhu_cuaca, volume_sekarang, latitude, longitude, is_weekend}` | `{hours_until_full}` |
| POST | `/predict/priority` | `{volume_sekarang, kadar_metana, laporan_warga}` | `{pickup_priority}` |
| POST | `/detect/anomaly` | `{jarak_ultrasonik, delta_volume_sec, suhu_cuaca}` | `{is_anomaly}` |

### Health Check (Public)

| Method | Path | Deskripsi |
|--------|------|-----------|
| GET | `/health` | Status semua upstream services |
| GET | `/iot/status` | Status IoT gateway |

## Testing dengan Postman

### Setup Postman

1. Buka Postman
2. **Import** > pilih `postman/smartcity-postman-collection.json`
3. **Import** > pilih `postman/smart-city-postman-environtment.json`
4. Klik dropdown environment (kanan atas) > pilih **"Smart City - Local"**

### Urutan Test

Jalankan request **dari atas ke bawah** sesuai folder di collection:

| Urutan | Folder | Request | Expected |
|--------|--------|---------|----------|
| 1 | 0. Health Check | Gateway Health | `"gateway": "ok"` |
| 2 | 0. Health Check | OAuth Health | `"oauth-server"` |
| 3 | 0. Health Check | ML Health | `models_loaded: 3` |
| 4 | 1. OAuth | Login Admin | `accessToken` otomatis tersimpan di environment |
| 5 | 1. OAuth | IoT Token | `iotToken` otomatis tersimpan |
| 6 | 2. Smart Bin | List All Bins | 10 bins (`BIN-Z1-01` ~ `BIN-Z10-01`) |
| 7 | 2. Smart Bin | Detail Bin (id=1) | `bin_code: "BIN-Z1-01"` |
| 8 | 2. Smart Bin | Set Bin Maintenance | `status: "Maintenance"` |
| 9 | 3. IoT Telemetry | Telemetry Normal | `201`, tinggi update, `dispatch: null` |
| 10 | 3. IoT Telemetry | Telemetry Critical | `201`, `dispatch.truck` terisi (auto-dispatch!) |
| 11 | 3. IoT Telemetry | Tanpa Token | `401` |
| 12 | 4. ML Predictions | Fill Rate | `hours_until_full` angka |
| 13 | 4. ML Predictions | Priority | `"Urgent"` |
| 14 | 4. ML Predictions | Anomaly | `is_anomaly: true` |
| 15 | 5. Fleet | List Trucks | truk `"On-Route"` terlihat |
| 16 | 5. Fleet | Complete Schedule | truk kembali `"Available"`, volume bin reset ke 0 |
| 17 | 6. Citizen Report | Submit Report | `201` (pilih file foto JPG/PNG di field `photo`) |
| 18 | 6. Citizen Report | Verify Report | `"Reviewed"` |
| 19 | 6. Citizen Report | Dispatch Truck | `"Dispatched"` + truk assigned |

> Token, schedule ID, dan report ID **otomatis tersimpan** ke environment variable oleh test script — tidak perlu copy-paste manual.

### Test End-to-End dengan Wokwi (IoT → Backend → ML)

1. Pastikan semua 5 service + Node-RED sudah running
2. Buka VS Code > folder `IOT Device` > `F1` > `Wokwi: Start Simulator`
3. Tunggu 5 detik — data mulai publish ke HiveMQ → Node-RED → Gateway → Backend
4. Lihat terminal Backend — muncul log `POST /api/iot/telemetry ... 201`
5. Di Postman, jalankan `List All Bins` — cek `BIN-Z5-01` data berubah (volume, tinggi, temperature)

## Docker Compose (Full Stack)

Untuk jalankan semua service sekaligus tanpa setup manual:

```bash
docker-compose up --build -d
docker exec trashtrack-backend php artisan db:seed
curl http://localhost:3000/health
```

## Akun Default

| Username | Password | Role | Untuk |
|----------|----------|------|-------|
| `admin` | `admin123` | admin | Dashboard admin |
| `warga1` | `warga123` | citizen | Aplikasi warga |

| Client ID | Secret | Grant Type |
|-----------|--------|------------|
| `smartcity-app` | `smartcity-secret` | password, client_credentials, refresh_token |
| `iot-device` | `iot-secret` | client_credentials |

## Struktur Folder

```
smart-city/
├── backend/                  # Laravel 12 API (PHP 8.2)
│   ├── app/
│   │   ├── Http/Controllers/ # SmartBin, Fleet, CitizenReport
│   │   ├── Models/           # TrashBin, SensorLog, Truck, Schedule, SanitationReport
│   │   ├── Services/         # RabbitMQPublisher, MLClient
│   │   └── Http/Requests/    # Form validation
│   ├── database/migrations/  # Schema definitions
│   ├── database/seeders/     # Sample data (10 bins, 10 trucks, etc)
│   └── routes/api.php        # API routes
│
├── express-gateway/          # API Gateway (Node.js)
│   └── src/
│       ├── middleware/        # JWT, rate limiting, logger
│       └── routes/            # proxy, health, iot, metrics
│
├── oauth-server/             # OAuth 2.0 Server (Node.js)
│   └── src/
│       ├── routes/oauth.js   # token, introspect, revoke
│       └── models/tokenStore.js
│
├── python-ml-service/        # ML Prediction Service (FastAPI)
│   ├── main.py               # API endpoints
│   ├── train_fill_rate.py    # Model 1: RandomForest regression
│   ├── train_priority_route.py # Model 2: GradientBoosting classification
│   ├── train_anomaly.py      # Model 3: RandomForest classification
│   ├── generate_data.py      # Synthetic training data generator
│   └── models/               # Trained .pkl files
│
├── IOT Device/               # PlatformIO ESP32 project
│   ├── src/main.cpp          # ESP32 firmware
│   ├── diagram.json          # Wokwi circuit (HC-SR04 + MQ-2 + LED)
│   ├── platformio.ini        # PlatformIO config
│   └── node-red-data/        # Node-RED flows + functions
│
├── iot/                      # Python MQTT simulator
│   ├── simulator.py          # Simulate 4 zones, 30s interval
│   └── mosquitto.conf        # MQTT broker config
│
├── docker-compose.yml        # Full stack (7 services)
├── database/                 # Raw SQL schema + seed
└── postman/                  # API collection + environment
```

## Tech Stack

| Layer | Teknologi |
|-------|-----------|
| IoT Device | ESP32, HC-SR04 (ultrasonik), MQ-2 (gas), PlatformIO |
| IoT Simulator | Python, paho-mqtt |
| MQTT Broker | Eclipse Mosquitto 2.0, HiveMQ (publik) |
| IoT Bridge | Node-RED |
| API Gateway | Express.js, JWT, rate limiting, http-proxy-middleware |
| Auth | OAuth 2.0 (password, client_credentials, refresh_token), bcrypt |
| Backend | Laravel 12, PHP 8.2, Eloquent ORM |
| ML | FastAPI, scikit-learn (RandomForest, GradientBoosting) |
| Database | MySQL 8.0 |
| Message Broker | RabbitMQ 3.12 (topic exchange) |
| Container | Docker, Docker Compose |
