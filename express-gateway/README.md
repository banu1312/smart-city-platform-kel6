# express-gateway

API Gateway untuk Smart City Integrated Platform. Berfungsi sebagai **single entry point** seluruh request eksternal - menangani autentikasi JWT, rate limiting, routing proxy ke upstream service, dan forwarding OAuth token.

---

## Arsitektur

```
Client / Postman / Node-RED
        │
        ▼
┌─────────────────────────────┐
│       API Gateway :3000     │
│                             │
│  ┌─────────────────────┐    │
│  │   Morgan Logger     │    │
│  └─────────────────────┘    │
│  ┌─────────────────────┐    │
│  │  JWT Middleware     │    │
│  └─────────────────────┘    │
│  ┌─────────────────────┐    │
│  │  Axios OAuth Fwd    │    │──→ OAuth Server :3002
│  └─────────────────────┘    │
│  ┌─────────────────────┐    │
│  │  Proxy Routes       │    │──→ Citizen Service  :8000
│  │                     │    │──→ Traffic Service  :8001
│  │                     │    │──→ Env Service      :8002
│  │                     │    │──→ Python ML        :5000
│  └─────────────────────┘    │
└─────────────────────────────┘
```

---

## Struktur Folder

```
express-gateway/
├── src/
│   ├── index.js              # Entry point — setup Express, semua middleware
│   ├── routes/
│   │   └── proxy.js          # Proxy routing ke semua upstream service
│   ├── middleware/
│   │   ├── jwt.js            # Verifikasi JWT + IoT scope check
│   │   └── logger.js         # Morgan request logger
│   └── utils/
│       └── response.js       # Helper format response JSON standar
├── .env.example              # Template environment variable
├── .env                      # Environment lokal (tidak di-commit)
├── package.json
└── Dockerfile                # (Sprint 2)
```

---

## Prasyarat

- Node.js >= 18
- npm >= 9
- OAuth Server sudah berjalan di port 3002

---

## Instalasi

```bash
cd express-gateway

# Install dependencies
npm install

# Buat file .env dari template
cp .env.example .env

# Edit .env sesuai kebutuhan
nano .env
```

---

## Konfigurasi `.env`

```bash
# JWT — harus sama persis dengan JWT_SECRET di oauth-server
JWT_SECRET=supersecretkeyjwtpilihanyangpanjangdanaman
JWT_EXPIRES_IN=1h

# Port Gateway
GATEWAY_PORT=3000

# OAuth Server — gunakan localhost untuk development lokal
OAUTH_SERVER_URL=http://localhost:3002

# Upstream Services — gunakan localhost untuk development lokal
# Ganti ke nama Docker service saat pakai docker-compose
CITIZEN_SERVICE_URL=http://localhost:8000
TRAFFIC_SERVICE_URL=http://localhost:8001
ENV_SERVICE_URL=http://localhost:8002
PYTHON_ML_URL=http://localhost:5000
```

> **Penting:** `JWT_SECRET` harus **identik** dengan yang ada di `oauth-server/.env`. Token yang di-issue OAuth Server tidak akan bisa diverifikasi Gateway jika secret berbeda.

---

## Menjalankan

```bash
# Development (auto-restart saat file berubah)
npm run dev

# Production
npm start
```

Output yang diharapkan:

```
[API Gateway] Running on port 3000
  → Citizen  : http://localhost:8000
  → Traffic  : http://localhost:8001
  → Env      : http://localhost:8002
  → Python ML: http://localhost:5000
  → OAuth    : http://localhost:3002
```

---

## Endpoint

### Public (tanpa token)

| Method | Path | Deskripsi |
|--------|------|-----------|
| `GET` | `/health` | Status Gateway |

### OAuth Forward (tanpa token — diteruskan ke OAuth Server)

| Method | Path | Deskripsi |
|--------|------|-----------|
| `POST` | `/oauth/token` | Issue token (password / client_credentials / refresh_token grant) |
| `POST` | `/oauth/introspect` | Validasi token |
| `POST` | `/oauth/revoke` | Cabut token |

> Semua request ke `/oauth/*` di-forward secara manual menggunakan `axios` ke OAuth Server. Response dikembalikan apa adanya ke client.

### Protected — Citizen Service (JWT required)

| Method | Path | Forward ke |
|--------|------|------------|
| `*` | `/api/citizens/*` | `CITIZEN_SERVICE_URL` |
| `*` | `/api/reports/*` | `CITIZEN_SERVICE_URL` |
| `*` | `/api/notifications/*` | `CITIZEN_SERVICE_URL` |

### Protected — Traffic Service (JWT required)

| Method | Path | Forward ke |
|--------|------|------------|
| `*` | `/api/traffic/*` | `TRAFFIC_SERVICE_URL` |

### Protected — Environment Service (JWT required)

| Method | Path | Forward ke |
|--------|------|------------|
| `*` | `/api/environment/*` | `ENV_SERVICE_URL` |

### Protected — Python ML Service (JWT required)

| Method | Path | Forward ke |
|--------|------|------------|
| `*` | `/predict/*` | `PYTHON_ML_URL` |
| `*` | `/detect/*` | `PYTHON_ML_URL` |
| `*` | `/model/*` | `PYTHON_ML_URL` |

### IoT Routes (client_credentials token required)

| Method | Path | Forward ke |
|--------|------|------------|
| `POST` | `/iot/traffic` | `TRAFFIC_SERVICE_URL/api/traffic/readings` |
| `POST` | `/iot/air` | `ENV_SERVICE_URL/api/environment/readings` |

---

## Format Request Berautentikasi

Semua endpoint protected memerlukan header:

```
Authorization: Bearer <access_token>
```

Cara mendapatkan token:

```bash
curl -X POST http://localhost:3000/oauth/token \
  -H "Content-Type: application/json" \
  -d '{
    "grant_type": "password",
    "client_id": "smartcity-app",
    "client_secret": "smartcity-secret",
    "username": "admin",
    "password": "admin123"
  }'
```

---

## Format Response Standar

Semua response dari Gateway mengikuti format:

```json
{
  "status": "success | error",
  "code": 200,
  "data": { } ,
  "message": "Keterangan singkat",
  "service": "api-gateway",
  "timestamp": "2026-06-13T00:00:00.000Z"
}
```

### HTTP Status Code yang Digunakan

| Code | Kondisi |
|------|---------|
| `200` | OK |
| `401` | Token tidak ada, tidak valid, atau expired |
| `403` | Token valid tapi tidak punya akses (scope kurang) |
| `404` | Route tidak ditemukan |
| `429` | Rate limit terlampaui (Sprint 2) |
| `502` | Upstream service tidak bisa dijangkau |
| `503` | OAuth Server tidak bisa dijangkau |

---

## Testing Lokal

Pastikan OAuth Server sudah jalan di port 3002, lalu:

```bash
# 1. Health check
curl http://localhost:3000/health

# 2. Ambil token via Gateway
TOKEN=$(curl -s -X POST http://localhost:3000/oauth/token \
  -H "Content-Type: application/json" \
  -d '{"grant_type":"password","client_id":"smartcity-app","client_secret":"smartcity-secret","username":"admin","password":"admin123"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['access_token'])")

echo "Token: $TOKEN"

# 3. Akses tanpa token — harus 401
curl -i http://localhost:3000/api/citizens

# 4. Akses dengan token — harus 502 (upstream belum jalan, normal di Sprint 1)
curl -i http://localhost:3000/api/citizens \
  -H "Authorization: Bearer $TOKEN"

# 5. Akses dengan token palsu — harus 401
curl -i http://localhost:3000/api/citizens \
  -H "Authorization: Bearer tokenpalsu"
```

Atau gunakan Postman Collection yang sudah tersedia di root repo:
```
postman/smartcity-postman-collection.json
postman/smartcity-local.postman_environment.json
```

---

## Catatan Implementasi

**Mengapa OAuth forward menggunakan `axios` bukan `http-proxy-middleware`?**

`http-proxy-middleware` v2/v3 memiliki masalah path matching di Express ketika path filter tidak didefinisikan secara eksplisit — proxy dibuat dengan path `/` sehingga mencegat semua request sebelum route lain diproses. Untuk menghindari ini, OAuth forwarding diimplementasi manual menggunakan `axios` yang lebih predictable dan mudah di-debug.

Upstream service lain (Citizen, Traffic, Env, ML) tetap menggunakan `http-proxy-middleware` karena path prefix-nya unik dan tidak bertabrakan.

---

## Sprint Progress

| Fitur | Status |
|-------|--------|
| Entry point & Express setup | ✅  |
| JWT Middleware | ✅  |
| Request Logger | ✅  |
| Standardized Response Helper | ✅  |
| Proxy Routes ke upstream | ✅  |
| OAuth Forward via axios | ✅ (fix) |
| Rate Limiting (global + per-token) | 🔲  |
| Health Aggregator (semua upstream) | 🔲 |
| IoT Routes | 🔲 |
| Dockerfile | 🔲 |
| Kubernetes Manifest | 🔲  |