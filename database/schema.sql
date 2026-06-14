CREATE DATABASE IF NOT EXISTS smartcity CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smartcity;

CREATE TABLE IF NOT EXISTS zones (
  id            INT PRIMARY KEY AUTO_INCREMENT,
  name          VARCHAR(100) NOT NULL,
  city_district VARCHAR(100),
  coordinates   VARCHAR(100),
  area_km2      FLOAT,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS oauth_clients (
  id            INT PRIMARY KEY AUTO_INCREMENT,
  client_id     VARCHAR(100) UNIQUE NOT NULL,
  client_secret VARCHAR(255) NOT NULL,
  grant_types   VARCHAR(255),
  redirect_uris TEXT
);

CREATE TABLE IF NOT EXISTS oauth_tokens (
  id            INT PRIMARY KEY AUTO_INCREMENT,
  client_id     VARCHAR(100),
  user_id       INT,
  access_token  VARCHAR(500),
  refresh_token VARCHAR(500),
  expires_at    TIMESTAMP,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TABEL CITIZEN SERVICE (port 8000)

CREATE TABLE IF NOT EXISTS citizen_citizens (
  id         INT PRIMARY KEY AUTO_INCREMENT,
  nik        VARCHAR(20) UNIQUE NOT NULL,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(100) UNIQUE,
  phone      VARCHAR(20),
  zone_id    INT,
  role       ENUM('citizen','admin') DEFAULT 'citizen',
  password   VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_citizen_zone (zone_id),
  INDEX idx_citizen_role (role)
);

CREATE TABLE IF NOT EXISTS citizen_reports (
  id          INT PRIMARY KEY AUTO_INCREMENT,
  citizen_id  INT NOT NULL,
  category    VARCHAR(50),   -- 'overflow', 'fire_hazard', 'missed_pickup', 'illegal_dumping'
  description TEXT,
  zone_id     INT,
  status      ENUM('pending','in_progress','resolved') DEFAULT 'pending',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_report_status (status),
  INDEX idx_report_zone (zone_id),
  INDEX idx_report_citizen (citizen_id)
);

CREATE TABLE IF NOT EXISTS citizen_notifications (
  id         INT PRIMARY KEY AUTO_INCREMENT,
  citizen_id INT,
  title      VARCHAR(200),
  body       TEXT,
  is_read    TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_notif_citizen (citizen_id),
  INDEX idx_notif_read (is_read)
);

-- TABEL WASTE SERVICE (port 8001) 

CREATE TABLE IF NOT EXISTS waste_bin_readings (
  id             INT PRIMARY KEY AUTO_INCREMENT,
  zone_id        INT NOT NULL,
  fill_level     FLOAT NOT NULL,     -- persen kepenuhan (0-100)
  gas_level      FLOAT,              -- nilai sensor gas MQ-2
  temperature    FLOAT,              -- suhu dalam tong (Celsius)
  incident_flag  TINYINT(1) DEFAULT 0,
  recorded_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_waste_zone (zone_id),
  INDEX idx_waste_recorded (recorded_at)
);

CREATE TABLE IF NOT EXISTS waste_incidents (
  id          INT PRIMARY KEY AUTO_INCREMENT,
  zone_id     INT,
  type        VARCHAR(50),      -- 'overflow', 'fire_risk', 'gas_leak', 'missed_pickup'
  severity    ENUM('low','medium','high','critical'),
  description TEXT,
  resolved_at TIMESTAMP NULL,
  reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_incident_zone (zone_id),
  INDEX idx_incident_severity (severity)
);

-- TABEL ENVIRONMENT SERVICE (port 8002)

CREATE TABLE IF NOT EXISTS env_sensor_readings (
  id          INT PRIMARY KEY AUTO_INCREMENT,
  zone_id     INT NOT NULL,
  pm25        FLOAT,
  pm10        FLOAT,
  no2         FLOAT,
  co          FLOAT,
  o3          FLOAT,
  temperature FLOAT,
  humidity    FLOAT,
  recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_env_zone (zone_id),
  INDEX idx_env_recorded (recorded_at)
);

CREATE TABLE IF NOT EXISTS env_alerts (
  id          INT PRIMARY KEY AUTO_INCREMENT,
  zone_id     INT,
  alert_type  VARCHAR(50),
  severity    ENUM('low','medium','high','critical'),
  value       FLOAT,
  threshold   FLOAT,
  resolved_at TIMESTAMP NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_env_alert_zone (zone_id)
);