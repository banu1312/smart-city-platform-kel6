# oauth-server

OAuth 2.0 Authorization Server untuk Smart City Integrated Platform. Menerbitkan dan memvalidasi token JWT yang digunakan oleh seluruh service dalam ekosistem microservice.

---

## Arsitektur

```
Client / Gateway / IoT Device
        │
        ▼
┌──────────────────────────────┐
│    OAuth Server :3002        │
│                              │
│  POST /oauth/token           │
│  ├── password grant          │──→ validasi user → issue JWT + refresh token
│  ├── client_credentials      │──→ validasi client → issue JWT
│  └── refresh_token           │──→ rotate token → issue JWT baru
│                              │
│  POST /oauth/introspect      │──→ validasi token (dipakai Gateway internal)
│  POST /oauth/revoke          │──→ cabut token dari store
│                              │
│  ┌──────────────────────┐    │
│  │   In-Memory Store    │    │
│  │  (Map: tokens/users) │    │
│  └──────────────────────┘    │
└──────────────────────────────┘
```

---

## Struktur Folder

```
oauth-server/
├── src/
│   ├── index.js              # Entry point — setup Express, mount routes
│   ├── routes/
│   │   └── oauth.js          # Semua endpoint OAuth (/token, /introspect, /revoke)
│   └── models/
│       └── tokenStore.js     # In-memory store: token, client, user registry
├── .env.example              # Template environment variable
├── .env                      # Environment lokal (tidak di-commit)
├── package.json
└── Dockerfile                # (Sprint 2)
```

---

## Prasyarat

- Node.js >= 18
- npm >= 9

---

## Instalasi

```bash
cd oauth-server

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
# JWT — harus sama persis dengan JWT_SECRET di express-gateway
JWT_SECRET=supersecretkeyjwtpilihanyangpanjangdanaman
JWT_EXPIRES_IN=1h
JWT_REFRESH_EXPIRES_IN=7d

# Port OAuth Server
OAUTH_PORT=3002
```

> **Penting:** `JWT_SECRET` harus **identik** dengan yang ada di `express-gateway/.env`. Token yang di-issue di sini harus bisa diverifikasi oleh Gateway.

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
[OAuth Server] Running on port 3002
```

---

## Endpoint

### `POST /oauth/token`

Menerbitkan access token berdasarkan grant type.

**Headers:**
```
Content-Type: application/json
```

#### Grant Type: `password`

Untuk login citizen app. Menghasilkan `access_token` + `refresh_token`.

**Request Body:**
```json
{
  "grant_type": "password",
  "client_id": "smartcity-app",
  "client_secret": "smartcity-secret",
  "username": "admin",
  "password": "admin123"
}
```

**Response `200`:**
```json
{
  "status": "success",
  "code": 200,
  "data": {
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "refresh_token": "uuid-uuid-uuid...",
    "scope": "read write"
  },
  "message": "Token issued successfully",
  "service": "oauth-server",
  "timestamp": "2026-06-13T00:00:00.000Z"
}
```

#### Grant Type: `client_credentials`

Untuk komunikasi antar service dan IoT device. Hanya menghasilkan `access_token`, tanpa `refresh_token`.

**Request Body:**
```json
{
  "grant_type": "client_credentials",
  "client_id": "iot-device",
  "client_secret": "iot-secret"
}
```

**Response `200`:**
```json
{
  "status": "success",
  "code": 200,
  "data": {
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "scope": "service"
  },
  "message": "Token issued successfully",
  "service": "oauth-server",
  "timestamp": "2026-06-13T00:00:00.000Z"
}
```

#### Grant Type: `refresh_token`

Perpanjang sesi tanpa login ulang. Token lama di-rotate (dihapus), pasangan token baru diterbitkan.

**Request Body:**
```json
{
  "grant_type": "refresh_token",
  "client_id": "smartcity-app",
  "client_secret": "smartcity-secret",
  "refresh_token": "uuid-uuid-dari-password-grant..."
}
```

**Response `200`:**
```json
{
  "status": "success",
  "code": 200,
  "data": {
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...(baru)",
    "token_type": "Bearer",
    "expires_in": 3600,
    "refresh_token": "uuid-baru...",
    "scope": "read write"
  },
  "message": "Token refreshed successfully",
  "service": "oauth-server",
  "timestamp": "2026-06-13T00:00:00.000Z"
}
```

---

### `POST /oauth/introspect`

Memvalidasi token. Dipakai oleh Gateway secara internal.

**Headers:**
```
Content-Type: application/json
Authorization: Basic <base64(clientId:clientSecret)>
```

Nilai header Authorization untuk client `smartcity-app`:
```
Basic c21hcnRjaXR5LWFwcDpzbWFydGNpdHktc2VjcmV0
```
> Dihitung dari: `base64("smartcity-app:smartcity-secret")`

**Request Body:**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Response — Token Aktif:**
```json
{
  "active": true,
  "sub": "1",
  "username": "admin",
  "role": "admin",
  "client_id": "smartcity-app",
  "scope": "read write",
  "exp": 1781334654
}
```

**Response — Token Tidak Aktif / Tidak Ditemukan:**
```json
{
  "active": false
}
```

---

### `POST /oauth/revoke`

Mencabut token dari store. Mendukung pencabutan access token maupun refresh token.

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Response `200`:**
```json
{
  "status": "success",
  "code": 200,
  "message": "Token revoked successfully",
  "service": "oauth-server",
  "timestamp": "2026-06-13T00:00:00.000Z"
}
```

---

### `GET /health`

Cek status server.

**Response `200`:**
```json
{
  "status": "success",
  "code": 200,
  "message": "OAuth Server is running",
  "service": "oauth-server",
  "timestamp": "2026-06-13T00:00:00.000Z"
}
```

---

## Client & User Registry (Sprint 1)

Saat ini client dan user disimpan in-memory di `src/models/tokenStore.js`. Akan diganti dengan query MySQL di Sprint 2.

### Registered Clients

| Client ID | Client Secret | Grant Types yang Diizinkan |
|-----------|--------------|---------------------------|
| `smartcity-app` | `smartcity-secret` | `password`, `client_credentials`, `refresh_token` |
| `iot-device` | `iot-secret` | `client_credentials` |

### Registered Users (dummy)

| Username | Password | Role |
|----------|----------|------|
| `admin` | `admin123` | `admin` |
| `warga1` | `warga123` | `citizen` |

> User dummy ini akan digantikan dengan query ke tabel `citizens` di MySQL pada Sprint 2.

---

## Struktur JWT Payload

Token yang di-issue memiliki payload berikut:

```json
{
  "sub": "1",
  "username": "admin",
  "role": "admin",
  "client_id": "smartcity-app",
  "scope": "read write",
  "jti": "uuid-unik-per-token",
  "iat": 1781331054,
  "exp": 1781334654
}
```

| Field | Deskripsi |
|-------|-----------|
| `sub` | ID user (atau clientId untuk client_credentials) |
| `username` | Username (null untuk client_credentials) |
| `role` | Role user: `admin`, `citizen`, atau `service` |
| `client_id` | Client yang merequest token |
| `scope` | Scope yang diberikan |
| `jti` | JWT ID unik — mencegah replay attack |
| `iat` | Waktu token dibuat (Unix timestamp) |
| `exp` | Waktu token expired (Unix timestamp) |

---

## Testing Lokal

```bash
# 1. Health check
curl http://localhost:3002/health

# 2. Password grant
curl -X POST http://localhost:3002/oauth/token \
  -H "Content-Type: application/json" \
  -d '{
    "grant_type": "password",
    "client_id": "smartcity-app",
    "client_secret": "smartcity-secret",
    "username": "admin",
    "password": "admin123"
  }'

# 3. Client credentials (IoT)
curl -X POST http://localhost:3002/oauth/token \
  -H "Content-Type: application/json" \
  -d '{
    "grant_type": "client_credentials",
    "client_id": "iot-device",
    "client_secret": "iot-secret"
  }'

# 4. Introspect token
TOKEN=<access_token_dari_langkah_2>
curl -X POST http://localhost:3002/oauth/introspect \
  -H "Authorization: Basic c21hcnRjaXR5LWFwcDpzbWFydGNpdHktc2VjcmV0" \
  -H "Content-Type: application/json" \
  -d "{\"token\": \"$TOKEN\"}"

# 5. Revoke token
curl -X POST http://localhost:3002/oauth/revoke \
  -H "Content-Type: application/json" \
  -d "{\"token\": \"$TOKEN\"}"
```

Atau gunakan Postman Collection yang sudah tersedia di root repo:
```
postman/smartcity-postman-collection.json
postman/y-local.postman_environment.json
```

---

## Error Responses

| HTTP Code | Kondisi |
|-----------|---------|
| `400` | `grant_type` tidak diisi, field wajib kurang, atau grant type tidak didukung client |
| `401` | `client_id`/`client_secret` salah, username/password salah, atau refresh token tidak valid |

Semua error mengikuti format standar:
```json
{
  "status": "error",
  "code": 401,
  "message": "Invalid username or password",
  "service": "oauth-server",
  "timestamp": "2026-06-13T00:00:00.000Z"
}
```

---

## Catatan Implementasi

**Token Storage:** Saat ini menggunakan `Map` JavaScript (in-memory). Artinya semua token **hilang saat server di-restart**. Ini acceptable untuk Sprint 1 / development. Persistence ke MySQL akan diimplementasi di Sprint 2.

**Token Rotation:** Setiap kali `refresh_token` grant digunakan, refresh token lama langsung dihapus dari store dan pasangan token baru diterbitkan. Ini mencegah refresh token dipakai lebih dari sekali.

**Access Token:** Berbentuk JWT yang bisa diverifikasi secara lokal di Gateway tanpa perlu request ke OAuth Server (stateless verification). Introspect endpoint tetap tersedia untuk kasus dimana Gateway perlu cek apakah token sudah di-revoke.

**Refresh Token:** Berbentuk opaque token (UUID random), bukan JWT. Hanya bisa divalidasi dengan lookup ke store.

---

## Sprint Progress

| Fitur | Status |
|-------|--------|
| Entry point & Express setup | ✅  |
| In-memory token store | ✅  |
| Grant type: `password` | ✅  |
| Grant type: `client_credentials` | ✅  |
| Grant type: `refresh_token` | ✅  |
| `/oauth/introspect` | ✅ |
| `/oauth/revoke` | ✅  |
| Koneksi MySQL untuk token persistence | 🔲  |
| Query user dari tabel `citizens` | 🔲  |
| Dockerfile | 🔲  |
| Kubernetes Manifest | 🔲  |