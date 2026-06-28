# TrashTrack - Smart Waste & Eco-Sanitation Management

Platform smart city untuk manajemen sampah pintar berbasis IoT, Machine Learning, dan microservices. Sistem ini memonitor tong sampah secara real-time, memprediksi waktu penuh, mendeteksi anomali, dan otomatis meng-dispatch truk pengangkut.

## Daftar Isi

- [Arsitektur](#arsitektur)
- [Tech Stack](#tech-stack)
- [Struktur Folder](#struktur-folder)
- [Prerequisites](#prerequisites)
- [Setup Environment (.env)](#setup-environment-env)
- [Cara Jalankan di Lokal](#cara-jalankan-di-lokal)
- [Cara Jalankan via Docker Compose](#cara-jalankan-via-docker-compose)
- [Cara Jalankan di Server (Kubernetes)](#cara-jalankan-di-server-kubernetes)
- [Setup IoT (Opsional)](#setup-iot-opsional)
- [Data Flow End-to-End](#data-flow-end-to-end)
- [API Endpoints](#api-endpoints)
- [Testing dengan Postman](#testing-dengan-postman)
- [Monitoring](#monitoring)
- [Akun & Kredensial Default](#akun--kredensial-default)
- [Troubleshooting](#troubleshooting)
- [Catatan Keamanan](#catatan-keamanan)

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
| RabbitMQ | 5672 (AMQP), 15672 (UI) | RabbitMQ 3.12 |
| Mosquitto | 1883 | Eclipse Mosquitto 2.0 |
| Node-RED | 1880 | Node-RED |
| Prometheus | 9090 | Monitoring metrics |
| Grafana | 3001 | Dashboard monitoring |

## Tech Stack

| Layer | Teknologi |
|-------|-----------|
| IoT Device | ESP32, HC-SR04 (ultrasonik), MQ-2 (gas), PlatformIO |
| IoT Simulator | Python, paho-mqtt |
| MQTT Broker | Eclipse Mosquitto 2.0, HiveMQ (publik, untuk Wokwi) |
| IoT Bridge | Node-RED |
| API Gateway | Express.js, JWT, rate limiting, http-proxy-middleware |
| Auth | OAuth 2.0 (password, client_credentials, refresh_token), bcrypt |
| Backend | Laravel 12, PHP 8.2, Eloquent ORM |
| ML | FastAPI, scikit-learn (RandomForest, GradientBoosting, Isolation Forest) |
| Database | MySQL 8.0 |
| Message Broker | RabbitMQ 3.12 (topic exchange) |
| Container & Orkestrasi | Docker, Docker Compose, Kubernetes |
| Monitoring | Prometheus, Grafana |

## Struktur Folder

```
smart-city-platform-kel6/
├── backend/                  # Laravel 12 API (PHP 8.2)
│   ├── app/
│   │   ├── Http/Controllers/ # SmartBin, Fleet, CitizenReport
│   │   ├── Models/           # TrashBin, SensorLog, Truck, Schedule, SanitationReport
│   │   ├── Services/         # RabbitMQPublisher, MLClient
│   │   └── Http/Requests/    # Form validation
│   ├── database/migrations/  # Schema definitions
│   └── database/seeders/     # Sample data (10 bins, 10 trucks, dst)
│
├── express-gateway/          # API Gateway (Node.js)
│   └── src/{middleware,routes}/
│
├── oauth-server/              # OAuth 2.0 Server (Node.js)
│   └── src/{models,routes}/
│
├── python-ml-service/        # ML Prediction Service (FastAPI)
│   ├── main.py
│   ├── train_fill_rate.py / train_priority_route.py / train_anomaly.py
│   ├── generate_data.py
│   └── notebooks/EDA.ipynb
│
├── IOT-Device/                # PlatformIO ESP32 project + Node-RED
│   ├── src/main.cpp
│   ├── diagram.json / platformio.ini / wokwi.toml
│   └── node-red-data/        # Flow bridge IoT → Gateway
│
├── database/                  # Raw SQL schema + seed
├── k8s/                       # Manifest Kubernetes (production)
├── monitoring/                 # Konfigurasi Prometheus & Grafana
├── postman/                    # API collection + environment
├── docker-compose.yml          # Full stack (production-like, lokal)
├── docker-compose.dev.yml      # (kosong - reserved untuk override dev, lihat catatan)
└── .env.example                # Variabel untuk docker-compose
```

## Prerequisites

Pastikan tools berikut sudah terpasang sebelum mulai:

| Tool | Versi Minimum | Wajib Untuk |
|------|----------------|-------------|
| PHP | 8.2+ (ekstensi: `pdo_mysql`, `mbstring`, `sockets`, `gd`, `zip`) | Backend Laravel |
| Composer | 2.x | Backend Laravel |
| Node.js | 18+ (dengan npm) | Express Gateway, OAuth Server, Node-RED |
| Python | 3.11+ (dengan pip) | Python ML Service, IoT Simulator |
| MySQL | 8.0 | Database (lokal atau via Docker) |
| Docker & Docker Compose | terbaru | Menjalankan full stack / infrastruktur |
| Git | terbaru | Clone repository |
| Postman | terbaru | Testing API (opsional) |
| PlatformIO + VS Code | terbaru | Simulasi ESP32 via Wokwi (opsional) |
| kubectl | sesuai versi cluster | Deploy ke Kubernetes (opsional, hanya untuk server) |

## Setup Environment (.env)

Repo ini punya **beberapa file `.env` terpisah** karena setiap service berjalan independen. Salin semua `.env.example` menjadi `.env` lalu sesuaikan nilainya.

| File | Lokasi | Dipakai Untuk |
|------|--------|----------------|
| `.env.example` | root | Variabel `docker-compose.yml` (password DB, JWT, RabbitMQ, MQTT, Grafana) |
| `backend/.env.example` | `backend/` | Konfigurasi Laravel (DB, RabbitMQ, ML service URL) |
| `express-gateway/.env.example` | `express-gateway/` | JWT secret, URL upstream (OAuth/Backend/ML) |
| `oauth-server/.env.example` | `oauth-server/` | JWT secret (harus identik dengan gateway) |
| `IOT-Device/node-red-data/.env.example` | `IOT-Device/node-red-data/` | Kredensial MQTT & OAuth untuk bridge Node-RED |
| `iot/.env` (dibuat manual) | `iot/` | Kredensial MQTT untuk simulator Python |

> **Penting:** nilai `JWT_SECRET` **harus identik** di `express-gateway/.env` dan `oauth-server/.env`. Token yang diterbitkan OAuth Server tidak akan terverifikasi Gateway jika secret berbeda.

### Variabel kunci di `.env` root (untuk Docker Compose)

```env
# --- Database ---
MYSQL_ROOT_PASSWORD=ganti_dengan_password_kuat
DB_DATABASE=smartcity
DB_USERNAME=root
DB_PASSWORD=ganti_dengan_password_kuat

# --- Laravel App Key (generate dengan: php artisan key:generate --show) ---
APP_KEY=base64:GANTI_DENGAN_OUTPUT_ARTISAN_KEY_GENERATE

# --- JWT (harus sama persis di semua service) ---
JWT_SECRET=ganti_dengan_secret_panjang_minimum_32_karakter
JWT_EXPIRES_IN=1h
JWT_REFRESH_EXPIRES_IN=7d

# --- RabbitMQ ---
RABBITMQ_USER=trashtrack
RABBITMQ_PASS=ganti_dengan_password_rabbitmq

# --- MQTT Mosquitto ---
MQTT_ADMIN_PASSWORD=ganti_dengan_password_mqtt_admin
MQTT_IOT_PASSWORD=ganti_dengan_password_mqtt_iot

# --- Grafana ---
GRAFANA_PASSWORD=ganti_dengan_password_grafana
```

Generate `APP_KEY` dengan masuk ke folder `backend/` lalu jalankan:
```bash
php artisan key:generate --show
```
Salin hasilnya ke variabel `APP_KEY` di `.env` root **dan** di `backend/.env`.

## Cara Jalankan di Lokal

Cocok untuk development, tanpa Docker (kecuali untuk MySQL & RabbitMQ).

### 1. Clone Repository

```bash
git clone https://github.com/banu1312/smart-city-platform-kel6.git
cd smart-city-platform-kel6
```

### 2. Siapkan Infrastruktur (MySQL + RabbitMQ)

Paling mudah pakai Docker:
```bash
docker run -d --name trashtrack-mysql \
  -e MYSQL_ROOT_PASSWORD=rootpass \
  -e MYSQL_DATABASE=smartcity \
  -p 3306:3306 mysql:8.0

docker run -d --name trashtrack-rabbitmq \
  -p 5672:5672 -p 15672:15672 rabbitmq:3.12-management
```
Atau gunakan MySQL lokal (XAMPP/Laragon) — buat database `smartcity` secara manual.

### 3. Backend Laravel (Terminal 1)

```bash
cd backend
cp .env.example .env
```

Edit `backend/.env` untuk lokal:
```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
ML_SERVICE_URL=http://localhost:5000

DB_HOST=127.0.0.1
DB_DATABASE=smartcity
DB_USERNAME=root
DB_PASSWORD=rootpass        # sesuaikan dengan password MySQL kalian

RABBITMQ_HOST=127.0.0.1
```

Lalu jalankan:
```bash
composer install --ignore-platform-req=ext-sockets
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve --port=8000
```
> Biarkan terminal ini terbuka.

### 4. OAuth Server (Terminal 2)

```bash
cd oauth-server
cp .env.example .env
npm install
node src/index.js
```
Output yang diharapkan: `[OAuth Server] Running on port 3002`

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

**Pertama kali — generate data & train model dulu:**
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

### 7. Verifikasi Semua Service Jalan

```bash
# Health check semua service
curl http://localhost:3000/health

# Login ambil token
curl -X POST http://localhost:3000/oauth/token \
  -H "Content-Type: application/json" \
  -d '{"grant_type":"password","client_id":"smartcity-app","client_secret":"smartcity-secret","username":"admin","password":"admin123"}'

# Test ambil data bins (ganti <TOKEN> dengan access_token dari response di atas)
curl http://localhost:3000/api/bins \
  -H "Authorization: Bearer <TOKEN>"
```

## Cara Jalankan via Docker Compose

Cocok untuk menjalankan semua service sekaligus tanpa setup manual satu per satu (mirip kondisi production, tapi di mesin lokal/single server).

### 1. Siapkan `.env` di Root

```bash
cp .env.example .env
```
Isi semua nilai sesuai instruksi di [Setup Environment](#setup-environment-env). **Jangan biarkan nilai placeholder `ganti_dengan_...`.**

### 2. Build & Jalankan

```bash
docker-compose up --build -d
```

Ini akan menjalankan 9 service: `mysql`, `rabbitmq`, `mosquitto`, `oauth-server`, `backend`, `python-ml`, `express-gateway`, `prometheus`, `grafana`.

### 3. Migrasi & Seed Database

```bash
docker exec trashtrack-backend php artisan migrate --force
docker exec trashtrack-backend php artisan db:seed
```

### 4. Verifikasi

```bash
curl http://localhost:3000/health
```

### 5. Perintah Berguna

```bash
docker-compose logs -f backend        # lihat log salah satu service
docker-compose ps                     # status semua container
docker-compose down                   # stop semua service
docker-compose down -v                # stop + hapus volume (reset data!)
```

> **Catatan:** `docker-compose.dev.yml` saat ini masih kosong di repo. Jika ingin override khusus development (misal mount volume source code untuk hot-reload), isi file ini dan jalankan dengan:
> ```bash
> docker-compose -f docker-compose.yml -f docker-compose.dev.yml up --build
> ```

## Cara Jalankan di Server (Kubernetes)

Untuk deployment production di server/cluster (VPS dengan k3s, atau cloud managed Kubernetes seperti GKE/EKS/DOKS). Manifest sudah disiapkan di folder `k8s/`.

### 1. Build & Push Image ke Registry

Setiap deployment k8s mereferensikan image `smart-city-platform-kel6-<service>:latest`. Build dan push image dulu ke registry yang bisa dijangkau cluster (Docker Hub, GHCR, dll):

```bash
docker build -t <registry>/smart-city-platform-kel6-backend:latest ./backend
docker build -t <registry>/smart-city-platform-kel6-express-gateway:latest ./express-gateway
docker build -t <registry>/smart-city-platform-kel6-oauth-server:latest ./oauth-server
docker build -t <registry>/smart-city-platform-kel6-python-ml:latest ./python-ml-service

docker push <registry>/smart-city-platform-kel6-backend:latest
docker push <registry>/smart-city-platform-kel6-express-gateway:latest
docker push <registry>/smart-city-platform-kel6-oauth-server:latest
docker push <registry>/smart-city-platform-kel6-python-ml:latest
```

Lalu update field `image:` di setiap file `k8s/*-deployment.yaml` agar sesuai dengan `<registry>/...` yang baru di-push (saat ini masih menunjuk ke image lokal `imagePullPolicy: IfNotPresent`).

### 2. Siapkan Secret

**Jangan pakai `k8s/secrets.yaml` apa adanya** — file itu hanya contoh/demo dan sudah berisi nilai bawaan repo. Buat secret sendiri dari `k8s/secrets.example.yaml` sebagai acuan, isi dengan password yang kuat, lalu apply manual (bukan lewat git):

```bash
kubectl create namespace trashtrack

kubectl create secret generic trashtrack-secrets \
  --namespace trashtrack \
  --from-literal=JWT_SECRET='ganti_dengan_secret_kuat_min_32_karakter' \
  --from-literal=APP_KEY="base64:$(openssl rand -base64 32)" \
  --from-literal=DB_USERNAME='root' \
  --from-literal=DB_PASSWORD='ganti_dengan_password_kuat' \
  --from-literal=MYSQL_ROOT_PASSWORD='ganti_dengan_password_kuat' \
  --from-literal=RABBITMQ_USER='trashtrack' \
  --from-literal=RABBITMQ_PASS='ganti_dengan_password_kuat'
```

> Alternatif yang lebih aman untuk tim: gunakan **Sealed Secrets** atau **Vault** alih-alih menyimpan secret mentah di file manifest.

### 3. Apply Manifest Secara Berurutan

```bash
kubectl apply -f k8s/namespace.yaml
kubectl apply -f k8s/configmap.yaml
# (secret sudah dibuat manual di langkah 2 — skip k8s/secrets.yaml)
kubectl apply -f k8s/mysql-statefulset.yaml
kubectl apply -f k8s/rabbitmq-deployment.yaml

# Tunggu MySQL & RabbitMQ ready dulu
kubectl get pods -n trashtrack -w

kubectl apply -f k8s/oauth-deployment.yaml
kubectl apply -f k8s/python-ml-deployment.yaml
kubectl apply -f k8s/backend-deployment.yaml
kubectl apply -f k8s/gateway-deployment.yaml
kubectl apply -f k8s/ingress.yaml
```

### 4. Install Metrics Server (untuk HPA)

HPA (`k8s/hpa.yaml`) butuh `metrics-server` agar bisa membaca penggunaan CPU/memori pod:

```bash
kubectl apply -f https://github.com/kubernetes-sigs/metrics-server/releases/latest/download/components.yaml
kubectl apply -f k8s/hpa.yaml

# Verifikasi
kubectl top pods -n trashtrack
```

### 5. Konfigurasi Ingress

Edit `k8s/ingress.yaml`, ganti host sesuai domain atau IP server kalian. Jika belum punya domain dan deploy di VPS dengan IP publik, bisa pakai layanan `nip.io`:
```yaml
- host: trashtrack.<IP_SERVER_KALIAN>.nip.io
```
Pastikan **nginx ingress controller** sudah terpasang di cluster. Untuk TLS otomatis, install `cert-manager` lalu aktifkan baris `cert-manager.io/cluster-issuer` dan blok `tls:` yang sudah disiapkan (saat ini di-comment).

### 6. Verifikasi Deployment

```bash
kubectl get all -n trashtrack
kubectl logs -n trashtrack deployment/backend
curl http://trashtrack.<domain-atau-ip>.nip.io/health
```

### 7. Update / Rollout Versi Baru

```bash
docker build -t <registry>/smart-city-platform-kel6-backend:latest ./backend
docker push <registry>/smart-city-platform-kel6-backend:latest
kubectl rollout restart deployment/backend -n trashtrack
kubectl rollout status deployment/backend -n trashtrack
```

## Setup IoT (Opsional)

Untuk uji coba alur end-to-end dari sensor hingga backend.

### Opsi A: Python Simulator

```bash
cd iot
python -m venv venv
venv\Scripts\activate          # Windows, atau: source venv/bin/activate (Linux/Mac)
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

> Butuh broker Mosquitto lokal: `docker run -d --name trashtrack-mosquitto -p 1883:1883 eclipse-mosquitto:2.0`

```bash
python simulator.py
```

### Opsi B: PlatformIO + Wokwi (ESP32 Virtual)

```bash
cd IOT-Device
pio run
```
Lalu di VS Code: `F1` → `Wokwi: Start Simulator`. ESP32 akan publish data ke `broker.hivemq.com` (publik, tanpa auth).

### Setup Node-RED (Bridge IoT → Backend)

```bash
cd IOT-Device/node-red-data
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
| GET | `/health` | Status semua upstream service |
| GET | `/iot/status` | Status IoT gateway |

## Testing dengan Postman

1. Buka Postman.
2. **Import** → pilih `postman/smartcity-postman-collection.json`.
3. **Import** → pilih `postman/smart-city-postman-environtment.json`.
4. Pilih environment **"Smart City - Local"** di dropdown kanan atas.
5. Jalankan request **dari atas ke bawah** sesuai urutan folder (Health Check → OAuth → Smart Bin → IoT Telemetry → ML Predictions → Fleet → Citizen Report). Token, schedule ID, dan report ID otomatis tersimpan ke variabel environment oleh test script.

### Test End-to-End dengan Wokwi (IoT → Backend → ML)

1. Pastikan semua 5 service + Node-RED sudah running.
2. Buka VS Code → folder `IOT-Device` → `F1` → `Wokwi: Start Simulator`.
3. Tunggu beberapa detik — data mulai publish ke HiveMQ → Node-RED → Gateway → Backend.
4. Lihat terminal Backend — muncul log `POST /api/iot/telemetry ... 201`.
5. Di Postman, jalankan `List All Bins` — cek data salah satu bin berubah (volume, tinggi, temperature).

## Monitoring

Saat berjalan via Docker Compose atau Kubernetes, Prometheus & Grafana sudah otomatis terkonfigurasi:

- Prometheus: `http://localhost:9090`
- Grafana: `http://localhost:3001` (login: `admin` / nilai `GRAFANA_PASSWORD` di `.env`)
- Dashboard utama sudah di-provision otomatis dari `monitoring/grafana-dashboard.json`.

## Akun & Kredensial Default

| Username | Password | Role | Untuk |
|----------|----------|------|-------|
| `admin` | `admin123` | admin | Dashboard admin |
| `warga1` | `warga123` | citizen | Aplikasi warga |

| Client ID | Secret | Grant Type |
|-----------|--------|------------|
| `smartcity-app` | `smartcity-secret` | password, client_credentials, refresh_token |
| `iot-device` | `iot-secret` | client_credentials |

> Kredensial ini hanya untuk demo/lokal. **Wajib diganti** sebelum digunakan di server production.
