"""
iot/simulator.py
TrashTrack - Smart Waste & Eco-Sanitation Management
Sprint 1 - IoT & Messaging Engineer

Simulator sensor tong sampah pintar:
- fill_level   : tingkat kepenuhan tong (0-100%), berbasis fungsi sinus
                 (pola harian) ditambah akumulasi bertahap, reset saat
                 "diangkut" oleh truk.
- gas_level    : sensor MQ-2 (ppm relatif), indikasi pembusukan/asap.
- temperature  : suhu dalam tong (°C), indikasi risiko kebakaran.

Data dipublish ke topik MQTT: city/{zone}/waste setiap PUBLISH_INTERVAL detik.
"""

import json
import math
import os
import random
import time
from datetime import datetime

import paho.mqtt.client as mqtt
from dotenv import load_dotenv

# KONFIGURASI
load_dotenv()

MQTT_HOST = os.getenv("MQTT_HOST", "localhost")
MQTT_PORT = int(os.getenv("MQTT_PORT", "1883"))
MQTT_USERNAME = os.getenv("MQTT_USERNAME", "iot_device")
MQTT_PASSWORD = os.getenv("MQTT_PASSWORD", "")
PUBLISH_INTERVAL = int(os.getenv("PUBLISH_INTERVAL", "30"))

ZONES = ["zone1", "zone2", "zone3", "zone4"]

# Koordinat tetap per zona — disamakan persis dengan kolom location_coordinate
# di backend/database/seeders/TrashBinSeeder.php (zone1->bin id 1, dst),
# supaya titik di MQTT payload konsisten dengan data yang sudah di DB.
ZONE_COORDS = {
    "zone1": (-6.1944, 106.8318),  # BIN-MNT-001
    "zone2": (-6.1950, 106.8320),  # BIN-MNT-002
    "zone3": (-6.2383, 106.8000),  # BIN-KBY-001
    "zone4": (-6.2390, 106.8010),  # BIN-KBY-002
}

# Threshold untuk menentukan status tong sampah
GAS_WARNING_THRESHOLD = 300
GAS_CRITICAL_THRESHOLD = 500
TEMP_WARNING_THRESHOLD = 40
TEMP_CRITICAL_THRESHOLD = 50
FILL_WARNING_THRESHOLD = 80
FILL_CRITICAL_THRESHOLD = 95

# Probabilitas terjadinya "anomali" (gas/suhu ekstrem) per siklus, per zona
ANOMALY_PROBABILITY = 0.05  # 5%

# State fill_level per zona (in-memory, reset setiap simulator restart)
zone_state = {
    zone: {
        "fill_level": random.uniform(5, 25),
        "bin_height": random.uniform(80, 150),  # tinggi tong random 80-150cm (IOT-3)
    }
    for zone in ZONES
}


# FUNGSI SIMULASI
def bin_id_for(zone: str) -> str:
    """Contoh: zone1 -> BIN-Z1-01"""
    zone_code = zone.replace("zone", "Z")
    return f"BIN-{zone_code}-01"


def simulate_fill_level(zone: str, hour: int) -> float:
    """
    Simulasikan kenaikan fill_level tong sampah.

    Menggunakan fungsi sinus untuk merepresentasikan pola aktivitas
    harian warga (lebih banyak sampah dibuang siang-sore dibanding
    tengah malam). Fill level bertambah setiap siklus, dan akan
    "reset" (disimulasikan sebagai truk mengangkut sampah) ketika
    mencapai ambang kritis.
    """
    state = zone_state[zone]

    # Laju penambahan dasar dipengaruhi waktu (sinus, peak sekitar jam 12-18)
    daily_factor = 1 + math.sin((hour - 6) * math.pi / 12)  # range 0..2
    base_increment = 0.5  # persen per siklus (kondisi minimum)
    increment = base_increment * daily_factor + random.gauss(0, 0.3)
    increment = max(0, increment)

    new_level = state["fill_level"] + increment

    if new_level >= FILL_CRITICAL_THRESHOLD:
        # Simulasikan truk sudah mengangkut sampah
        new_level = random.uniform(5, 15)
        print(f"[{zone}] Tong sampah ({bin_id_for(zone)}) telah diangkut, fill_level direset.")

    state["fill_level"] = new_level
    return round(new_level, 1)


def simulate_gas_and_temp(zone: str) -> tuple[float, float]:
    """
    Simulasikan pembacaan sensor gas (MQ-2) dan suhu.
    Sesekali (ANOMALY_PROBABILITY) menghasilkan nilai ekstrem untuk
    menguji skenario deteksi anomali (Sprint 3 - S6).
    """
    if random.random() < ANOMALY_PROBABILITY:
        gas_level = random.uniform(550, 900)
        temperature = random.uniform(50, 65)
        print(f"[{zone}] ANOMALI disimulasikan: gas={gas_level:.1f}, suhu={temperature:.1f}")
    else:
        gas_level = random.uniform(50, 200) + random.gauss(0, 15)
        temperature = random.uniform(25, 35) + random.gauss(0, 1)

    return round(max(0, gas_level), 1), round(temperature, 1)


def determine_status(fill_level: float, gas_level: float, temperature: float) -> str:
    """Tentukan status tong sampah berdasarkan threshold."""
    if (
        fill_level >= FILL_CRITICAL_THRESHOLD
        or gas_level >= GAS_CRITICAL_THRESHOLD
        or temperature >= TEMP_CRITICAL_THRESHOLD
    ):
        return "critical"
    if (
        fill_level >= FILL_WARNING_THRESHOLD
        or gas_level >= GAS_WARNING_THRESHOLD
        or temperature >= TEMP_WARNING_THRESHOLD
    ):
        return "warning"
    return "normal"


def build_payload(zone: str) -> dict:
    now = datetime.now()
    fill_level = simulate_fill_level(zone, now.hour)
    gas_level, temperature = simulate_gas_and_temp(zone)
    status = determine_status(fill_level, gas_level, temperature)
    calibrated_height = round(zone_state[zone]["bin_height"], 1)
    lat, lon = ZONE_COORDS[zone]

    return {
        "zone": zone,
        "bin_id": bin_id_for(zone),
        "fill_level": fill_level,
        "gas_level": gas_level,
        "temperature": temperature,
        "calibrated_height": calibrated_height,
        "is_calibration": False,
        "latitude": lat,
        "longitude": lon,
        "status": status,
        "timestamp": now.isoformat(),
    }


# MQTT CALLBACKS
def on_connect(client, userdata, flags, reason_code, properties):
    if reason_code == 0:
        print(f"[MQTT] Terhubung ke broker {MQTT_HOST}:{MQTT_PORT} sebagai '{MQTT_USERNAME}'")
    else:
        print(f"[MQTT] Gagal terhubung, reason_code={reason_code}")


def on_disconnect(client, userdata, flags, reason_code, properties):
    print(f"[MQTT] Terputus dari broker (reason_code={reason_code})")


# MAIN LOOP
def main():
    client = mqtt.Client(mqtt.CallbackAPIVersion.VERSION2, client_id="trashtrack-simulator")
    client.username_pw_set(MQTT_USERNAME, MQTT_PASSWORD)
    client.on_connect = on_connect
    client.on_disconnect = on_disconnect

    client.connect(MQTT_HOST, MQTT_PORT)
    client.loop_start()

    print(f"TrashTrack Sensor Simulator dimulai. Publish setiap {PUBLISH_INTERVAL} detik...")
    print(f"Zona aktif: {', '.join(ZONES)}")
    print("Tekan Ctrl+C untuk berhenti.\n")

    try:
        while True:
            for zone in ZONES:
                payload = build_payload(zone)
                topic = f"city/{zone}/waste"
                client.publish(topic, json.dumps(payload), qos=1)
                print(f"[PUBLISH] {topic} -> {payload}")

            time.sleep(PUBLISH_INTERVAL)
    except KeyboardInterrupt:
        print("\nSimulator dihentikan oleh user.")
    finally:
        client.loop_stop()
        client.disconnect()


if __name__ == "__main__":
    main()