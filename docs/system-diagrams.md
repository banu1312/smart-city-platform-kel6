# TrashTrack — System Diagrams
> Paste setiap blok ke [mermaid.live](https://mermaid.live) untuk preview.

---

## 1. System Architecture Diagram

```mermaid
graph TB
    subgraph IOT["⚙️ IoT Layer"]
        ESP32["ESP32\n(Wokwi Simulator)\nHC-SR04 Ultrasonic\nMQ-2 Gas · LED"]
        SIM["simulator.py\nZone 1–4"]
    end

    subgraph BROKER["📡 MQTT Broker"]
        HIVEMQ["HiveMQ Public Cloud\nbroker.hivemq.com:1883\nTopic: city/zone5/waste"]
        MOSQ["Mosquitto Local\nlocalhost:1883\nTopic: city/+/waste"]
    end

    subgraph BRIDGE["🌉 IoT Bridge — Laptop"]
        NR["Node-RED :1880\nOAuth Token Cache\nHTTP Retry Logic"]
    end

    subgraph K8S["☸️ Kubernetes Cluster — 103.143.12.197:30000"]
        GW["Express Gateway\nNodePort :30000\nJWT Validation · Proxy Router"]

        subgraph SVC["Microservices"]
            OAUTH["OAuth2 Server :3000\ngrant: password\ngrant: client_credentials\ngrant: refresh_token"]
            LARAVEL["Laravel Backend :8000\nBin Management\nFleet Management\nCitizen Report\nIoT Telemetry Handler"]
            ML["ML Service FastAPI :5000\nFill Rate Predictor\nPriority Classifier\nAnomaly Detector"]
        end

        subgraph DATA["Data Layer"]
            MYSQL[("MySQL :3306\nPersistent Storage")]
            REDIS[("Redis :6379\nToken Cache")]
            MQ["RabbitMQ :5672\ndispatch.queue"]
        end

        subgraph MODELS["ML Models (.pkl)"]
            M1["RandomForestRegressor\njam_sampai_penuh\n(Fill Rate)"]
            M2["GradientBoostingClassifier\npickup_priority\n(Low/Medium/Urgent/Critical)"]
            M3["RandomForestClassifier\nis_anomaly\n(class_weight=balanced)"]
        end
    end

    subgraph CLIENT["👥 Client Layer"]
        POSTMAN["Postman\nAdmin Demo & Testing"]
        MOBILE["Mobile App\nWarga (Citizen)"]
        WEB["Web Dashboard\nAdmin · Petugas"]
    end

    %% IoT flow
    ESP32 -->|"MQTT PUBLISH\ncity/zone5/waste"| HIVEMQ
    SIM -->|"MQTT PUBLISH\ncity/zone1-4/waste"| MOSQ
    HIVEMQ -->|subscribe| NR
    MOSQ -->|subscribe| NR
    NR -->|"POST /iot/telemetry\nAuthorization: Bearer iotToken"| GW

    %% Client flow
    POSTMAN -->|"HTTP REST"| GW
    MOBILE -->|"HTTP REST"| GW
    WEB -->|"HTTP REST"| GW

    %% Gateway routing
    GW -->|"/oauth/token"| OAUTH
    GW -->|"/api/* · /iot/*"| LARAVEL
    GW -->|"/ml/*"| ML

    %% Backend internals
    LARAVEL -->|"read · write"| MYSQL
    LARAVEL -->|"session cache"| REDIS
    LARAVEL -->|"sync HTTP predict"| ML
    LARAVEL -->|"publish event"| MQ
    MQ -->|"consume\nauto-dispatch"| LARAVEL

    %% ML models
    ML -->|"load · infer"| M1
    ML -->|"load · infer"| M2
    ML -->|"load · infer"| M3

    %% Styling
    classDef iot     fill:#ff6b6b,stroke:#c0392b,color:#fff
    classDef broker  fill:#f39c12,stroke:#d68910,color:#fff
    classDef bridge  fill:#00b894,stroke:#00a381,color:#fff
    classDef gateway fill:#0984e3,stroke:#0773c5,color:#fff
    classDef service fill:#6c5ce7,stroke:#5a4fcf,color:#fff
    classDef data    fill:#e17055,stroke:#c0392b,color:#fff
    classDef model   fill:#fdcb6e,stroke:#e17055,color:#333
    classDef client  fill:#b2bec3,stroke:#7f8c8d,color:#333

    class ESP32,SIM iot
    class HIVEMQ,MOSQ broker
    class NR bridge
    class GW gateway
    class OAUTH,LARAVEL,ML service
    class MYSQL,REDIS,MQ data
    class M1,M2,M3 model
    class POSTMAN,MOBILE,WEB client
```

---

## 2. Sequence Diagram — S1: IoT Telemetry → ML Prediction → Auto Dispatch

```mermaid
sequenceDiagram
    autonumber

    participant ESP  as ESP32 (Wokwi)
    participant HMQ  as HiveMQ MQTT
    participant NR   as Node-RED
    participant GW   as Express Gateway :30000
    participant OA   as OAuth2 Server
    participant BE   as Laravel Backend
    participant DB   as MySQL
    participant ML   as ML Service (FastAPI)
    participant MQ   as RabbitMQ

    Note over ESP,HMQ: Sensor membaca data tiap 2 detik

    ESP  ->> HMQ  : PUBLISH city/zone5/waste<br/>{bin_id, fill_level, gas_level, temperature,<br/>calibrated_height, latitude, longitude}
    HMQ  ->> NR   : MQTT message diterima

    alt Token expired / cache kosong
        NR   ->> GW   : POST /oauth/token<br/>Basic iot-device:iot-secret<br/>{grant_type: client_credentials}
        GW   ->> OA   : proxy request
        OA  -->> GW   : {access_token, expires_in: 3600}
        GW  -->> NR   : access_token
        NR   ->> NR   : simpan ke cache (TTL 55 menit)
    end

    NR   ->> GW   : POST /iot/telemetry<br/>Authorization: Bearer iotToken<br/>{bin_id, fill_level, gas_level, ...}
    GW   ->> GW   : validate JWT signature & expiry
    GW   ->> BE   : forward POST /iot/telemetry

    BE   ->> DB   : INSERT smart_bin_telemetry<br/>(fill_level, gas_level, timestamp, bin_id)

    Note over BE,ML: Prediksi ML — 3 model dipanggil secara berurutan

    BE   ->> ML   : POST /ml/predict/fill-rate<br/>{volume_sekarang, jam, suhu_cuaca, latitude, longitude, is_weekend}
    ML  -->> BE   : {jam_sampai_penuh: 2.3, confidence: 0.89}

    BE   ->> ML   : POST /ml/predict/priority<br/>{volume_sekarang, kadar_metana, laporan_warga}
    ML  -->> BE   : {priority: "Critical", confidence: 0.94}

    BE   ->> ML   : POST /ml/predict/anomaly<br/>{jarak_ultrasonik, delta_volume_sec, suhu_cuaca}
    ML  -->> BE   : {is_anomaly: false, confidence: 0.97}

    BE   ->> DB   : UPDATE smart_bins<br/>(ml_priority, jam_sampai_penuh, is_anomaly)

    alt priority == "Critical" atau "Urgent"
        BE   ->> MQ   : PUBLISH dispatch.queue<br/>{bin_id, priority, location, fill_level}
        MQ   ->> BE   : CONSUME — AutoDispatchConsumer
        BE   ->> DB   : SELECT armada WHERE status='available' LIMIT 1
        DB  -->> BE   : truck_id: TRK-003
        BE   ->> DB   : INSERT pickup_schedules<br/>(bin_id, truck_id, status='assigned', priority)
        BE  -->> GW   : 200 OK {telemetry_id, priority: Critical,<br/>schedule_id, schedule_created: true}
    else priority == "Low" atau "Medium"
        BE  -->> GW   : 200 OK {telemetry_id, priority: Low,<br/>schedule_created: false}
    end

    GW  -->> NR   : HTTP 200 response
```

---

## 3. Sequence Diagram — S2: Citizen Report (Laporan Warga)

```mermaid
sequenceDiagram
    autonumber

    participant WRG as Warga (Mobile App)
    participant GW  as Express Gateway :30000
    participant OA  as OAuth2 Server
    participant BE  as Laravel Backend
    participant DB  as MySQL
    participant MQ  as RabbitMQ

    Note over WRG,OA: Step 1 — Autentikasi warga (password grant)

    WRG  ->> GW  : POST /oauth/token<br/>Authorization: Basic base64(smartcity-app:smartcity-secret)<br/>{grant_type: password, username: warga1, password: warga123}
    GW   ->> OA  : proxy request
    OA   ->> OA  : validasi username & password dari DB
    OA  -->> GW  : {access_token, refresh_token, expires_in: 3600}
    GW  -->> WRG : access_token + refresh_token

    Note over WRG,DB: Step 2 — Kirim laporan dengan foto

    WRG  ->> GW  : POST /api/reports<br/>Authorization: Bearer wargaToken<br/>Content-Type: multipart/form-data<br/>{reporter_name, reporter_phone, issue_description,<br/>geo_coordinate: "-6.2383,106.8000", photo: [file]}
    GW   ->> GW  : validate JWT (role: warga)
    GW   ->> BE  : forward POST /api/reports

    BE   ->> BE  : simpan foto ke storage/reports/
    BE   ->> DB  : INSERT citizen_reports<br/>(reporter_name, phone, description,<br/>photo_path, location, status: pending)
    DB  -->> BE  : report_id: 42

    alt Issue terdeteksi kritis (fill_level > 80% di area yang sama)
        BE   ->> DB  : SELECT smart_bins WHERE radius < 100m AND fill_level > 80
        DB  -->> BE  : bin_id: BIN-Z3-01
        BE   ->> MQ  : PUBLISH dispatch.queue<br/>{source: citizen_report, report_id: 42,<br/>bin_id: BIN-Z3-01, priority: Urgent}
        MQ   ->> BE  : CONSUME — AutoDispatchConsumer
        BE   ->> DB  : INSERT pickup_schedules<br/>(report_id, bin_id, priority: Urgent, status: assigned)
        BE  -->> GW  : 201 Created<br/>{report_id: 42, status: pending,<br/>schedule_created: true, schedule_id: 17}
    else Laporan normal, queue untuk review manual
        BE  -->> GW  : 201 Created<br/>{report_id: 42, status: pending,<br/>schedule_created: false}
    end

    GW  -->> WRG : 201 Created response

    Note over WRG,DB: Step 3 — Warga cek status laporan

    WRG  ->> GW  : GET /api/reports/42<br/>Authorization: Bearer wargaToken
    GW   ->> BE  : forward
    BE   ->> DB  : SELECT * FROM citizen_reports WHERE id=42
    DB  -->> BE  : {report_id: 42, status: pending, schedule_id: 17}
    BE  -->> GW  : 200 OK
    GW  -->> WRG : {report_id, status, schedule_info}
```

---

## 4. Sequence Diagram — OAuth2 Token Flow

```mermaid
sequenceDiagram
    autonumber

    participant C   as Client (Postman / App)
    participant GW  as Express Gateway :30000
    participant OA  as OAuth2 Server :3000

    rect rgb(230, 245, 255)
        Note over C,OA: Flow A — Password Grant (Admin / Warga)

        C  ->> GW  : POST /oauth/token<br/>Authorization: Basic base64(smartcity-app:smartcity-secret)<br/>{grant_type: password,<br/> username: admin, password: admin123}
        GW ->> OA  : proxy
        OA ->> OA  : hash & compare password di DB
        OA -->> GW : 200 OK<br/>{access_token, token_type: Bearer,<br/>expires_in: 3600, refresh_token}
        GW -->> C  : access_token + refresh_token
    end

    rect rgb(255, 245, 230)
        Note over C,OA: Flow B — Client Credentials (IoT Device)

        C  ->> GW  : POST /oauth/token<br/>Authorization: Basic base64(iot-device:iot-secret)<br/>{grant_type: client_credentials}
        GW ->> OA  : proxy
        OA ->> OA  : validate client_id & client_secret
        OA -->> GW : 200 OK<br/>{access_token, token_type: Bearer,<br/>expires_in: 3600}
        GW -->> C  : iotToken (no refresh token)
    end

    rect rgb(240, 255, 240)
        Note over C,OA: Flow C — Refresh Token

        C  ->> GW  : POST /oauth/token<br/>Authorization: Basic base64(smartcity-app:smartcity-secret)<br/>{grant_type: refresh_token,<br/> refresh_token: eyJhbGci...}
        GW ->> OA  : proxy
        OA ->> OA  : validate & rotate refresh_token
        OA -->> GW : 200 OK<br/>{new access_token, new refresh_token}
        GW -->> C  : rotated tokens

        Note over C: simpan token baru ke environment variable
    end

    rect rgb(255, 235, 235)
        Note over C,OA: Flow D — Akses dengan Token Expired

        C  ->> GW  : GET /api/bins<br/>Authorization: Bearer expired_token
        GW ->> GW  : JWT verify → TokenExpiredError
        GW -->> C  : 401 Unauthorized<br/>{error: token_expired}

        Note over C: ulangi dari Flow C (refresh)
    end
```

---

## 5. Sequence Diagram — S6: Fleet Management & Manual Dispatch

```mermaid
sequenceDiagram
    autonumber

    participant ADM as Admin (Postman / Dashboard)
    participant GW  as Express Gateway :30000
    participant BE  as Laravel Backend
    participant DB  as MySQL
    participant MQ  as RabbitMQ

    Note over ADM,DB: Lihat daftar armada & jadwal aktif

    ADM  ->> GW  : GET /api/fleet<br/>Authorization: Bearer adminToken
    GW   ->> BE  : forward
    BE   ->> DB  : SELECT * FROM armada WHERE status IN (available, on_route)
    DB  -->> BE  : [{truck_id, driver, status, location}, ...]
    BE  -->> GW  : 200 OK [{armada list}]
    GW  -->> ADM : daftar armada

    ADM  ->> GW  : GET /api/fleet/schedules?status=assigned<br/>Authorization: Bearer adminToken
    GW   ->> BE  : forward
    BE   ->> DB  : SELECT pickup_schedules JOIN smart_bins JOIN armada
    DB  -->> BE  : [{schedule_id, bin_id, truck_id, priority, status}, ...]
    BE  -->> GW  : 200 OK
    GW  -->> ADM : jadwal pengangkutan aktif

    Note over ADM,DB: Manual dispatch ke bin tertentu

    ADM  ->> GW  : POST /api/fleet/dispatch<br/>Authorization: Bearer adminToken<br/>{bin_id: "BIN-Z3-01", truck_id: "TRK-002",<br/> priority: "Urgent", notes: "Full by user request"}
    GW   ->> GW  : validate JWT (role: admin)
    GW   ->> BE  : forward POST /api/fleet/dispatch
    BE   ->> DB  : SELECT armada WHERE truck_id='TRK-002' AND status='available'
    DB  -->> BE  : truck found
    BE   ->> DB  : INSERT pickup_schedules<br/>(bin_id, truck_id, priority, status: assigned,<br/>source: manual_dispatch)
    BE   ->> DB  : UPDATE armada SET status='on_route' WHERE truck_id='TRK-002'
    BE  -->> GW  : 201 Created<br/>{schedule_id: 18, status: assigned, truck_id: TRK-002}
    GW  -->> ADM : dispatch berhasil

    Note over ADM,DB: Update status setelah pengangkutan selesai

    ADM  ->> GW  : PATCH /api/fleet/schedules/18<br/>Authorization: Bearer adminToken<br/>{status: "completed"}
    GW   ->> BE  : forward
    BE   ->> DB  : UPDATE pickup_schedules SET status='completed', completed_at=NOW()
    BE   ->> DB  : UPDATE armada SET status='available' WHERE truck_id='TRK-002'
    BE   ->> DB  : UPDATE smart_bins SET fill_level=0 WHERE bin_id='BIN-Z3-01'
    BE  -->> GW  : 200 OK {schedule_id: 18, status: completed}
    GW  -->> ADM : status diperbarui
```
