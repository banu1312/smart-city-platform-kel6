import pandas as pd
import numpy as np
import logging
from pathlib import Path

logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

# Koordinat zona Jakarta — ML akan belajar pola berdasarkan lokasi geografis
ZONE_COORDS = {
    'Perumahan': [(-6.1944, 106.8318), (-6.1950, 106.8320), (-6.2390, 106.8010), (-6.2010, 106.9510)],
    'Pasar':     [(-6.1200, 106.8100), (-6.1510, 106.7410)],
    'Taman':     [(-6.2383, 106.8000), (-6.1210, 106.8110), (-6.2000, 106.9500)],
}

def coords_for_lokasi(tipe: str) -> tuple[float, float]:
    coords = ZONE_COORDS[tipe]
    base = coords[np.random.randint(len(coords))]
    return (
        base[0] + np.random.normal(0, 0.005),
        base[1] + np.random.normal(0, 0.005),
    )

def generate_synthetic_data(num_samples: int = 1000000) -> None:
    logging.info(f"Generating {num_samples} samples of synthetic data...")
    np.random.seed(42)

    jam = np.random.randint(0, 24, num_samples)
    suhu_cuaca = np.random.uniform(25.0, 38.0, num_samples)
    volume_sekarang = np.random.uniform(0.0, 100.0, num_samples)

    tipe_lokasi = np.random.choice(['Perumahan', 'Taman', 'Pasar'], num_samples, p=[0.5, 0.3, 0.2])
    is_weekend = np.random.choice([0, 1], num_samples, p=[0.7, 0.3])

    latitudes = np.zeros(num_samples)
    longitudes = np.zeros(num_samples)
    for i in range(num_samples):
        lat, lon = coords_for_lokasi(tipe_lokasi[i])
        latitudes[i] = round(lat, 6)
        longitudes[i] = round(lon, 6)

    kadar_metana = np.random.uniform(0.0, 50.0, num_samples)
    laporan_warga = np.random.randint(0, 10, num_samples)
    jarak_ultrasonik = 100 - volume_sekarang + np.random.normal(0, 2, num_samples)

    delta_volume_sec = np.random.normal(0, 0.5, num_samples)
    jumlah_anomali = int(num_samples * 0.01)
    index_anomali = np.random.choice(num_samples, jumlah_anomali, replace=False)
    delta_volume_sec[index_anomali] = np.random.uniform(-10.0, -5.0, jumlah_anomali)

    # Target Model 1: Fill Rate — tipe_lokasi tetap pengaruh, tapi ML belajar dari koordinat
    waktu_dasar_jam = np.select(
        [tipe_lokasi == 'Pasar', tipe_lokasi == 'Taman'],
        [12, 24],
        default=48
    )
    pengali_weekend = np.where(is_weekend == 1, 0.6, 1.0)
    persen_sisa = (100 - volume_sekarang) / 100
    jam_sampai_penuh = waktu_dasar_jam * persen_sisa * pengali_weekend
    jam_sampai_penuh += np.random.normal(0, 1.5, num_samples)
    jam_sampai_penuh = np.maximum(0.1, jam_sampai_penuh)

    # Target Model 2: Priority Route
    kondisi = volume_sekarang + (kadar_metana * 0.5) + (laporan_warga * 5)
    pickup_priority = np.select(
        [kondisi > 120, kondisi > 80, kondisi > 40],
        ['Critical', 'Urgent', 'Medium'],
        default='Low'
    )

    # Target Model 3: Anomaly
    is_anomaly = np.where((suhu_cuaca > 36.5) | (delta_volume_sec < -3.0), 1, 0)

    df = pd.DataFrame({
        'jam': jam,
        'suhu_cuaca': suhu_cuaca,
        'volume_sekarang': volume_sekarang,
        'latitude': latitudes,
        'longitude': longitudes,
        'is_weekend': is_weekend,
        'kadar_metana': kadar_metana,
        'laporan_warga': laporan_warga,
        'jarak_ultrasonik': jarak_ultrasonik,
        'delta_volume_sec': delta_volume_sec,
        'jam_sampai_penuh': jam_sampai_penuh,
        'pickup_priority': pickup_priority,
        'is_anomaly': is_anomaly
    })

    output_path = Path("data/trash_data.csv")
    output_path.parent.mkdir(parents=True, exist_ok=True)
    df.to_csv(output_path, index=False)
    logging.info(f"Data successfully generated and saved to {output_path.absolute()}")

if __name__ == "__main__":
    generate_synthetic_data()
