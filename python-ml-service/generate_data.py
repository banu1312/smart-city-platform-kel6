import pandas as pd
import numpy as np
import logging
from pathlib import Path

# Setup standard logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

def generate_synthetic_data(num_samples: int = 1000000) -> None:
    """Generate comprehensive synthetic data for 3 TrashTrack ML models."""
    logging.info(f"Generating {num_samples} samples of synthetic data...")
    np.random.seed(42)
    
    # ==========================================
    # 1. FITUR / KONDISI LAPANGAN (SOAL)
    # ==========================================
    jam = np.random.randint(0, 24, num_samples)
    suhu_cuaca = np.random.uniform(25.0, 38.0, num_samples)
    volume_sekarang = np.random.uniform(0.0, 100.0, num_samples)
    
    # Fitur Kategori Baru (Feature Engineering)
    tipe_lokasi = np.random.choice(['Perumahan', 'Taman', 'Pasar'], num_samples, p=[0.5, 0.3, 0.2])
    is_weekend = np.random.choice([0, 1], num_samples, p=[0.7, 0.3]) # 30% kemungkinan weekend
    ada_event = np.random.choice([0, 1], num_samples, p=[0.95, 0.05]) # 5% kemungkinan ada event
    
    # Fitur Sensor
    kadar_metana = np.random.uniform(0.0, 50.0, num_samples)
    laporan_warga = np.random.randint(0, 10, num_samples)
    jarak_ultrasonik = 100 - volume_sekarang + np.random.normal(0, 2, num_samples) # in cm
    
    # Fitur Delta Volume (dengan perbaikan Bug Anomali / Injeksi Vandalism)
    delta_volume_sec = np.random.normal(0, 0.5, num_samples)
    jumlah_anomali = int(num_samples * 0.01) # 1% dari data sengaja disabotase
    index_anomali = np.random.choice(num_samples, jumlah_anomali, replace=False)
    delta_volume_sec[index_anomali] = np.random.uniform(-10.0, -5.0, jumlah_anomali)


    # ==========================================
    # 2. TARGET ML
    # ==========================================
    
    # Target Model 1: Fill Rate (Regression) dengan Logika Bisnis
    waktu_dasar_jam = np.select(
        [tipe_lokasi == 'Pasar', tipe_lokasi == 'Taman'],
        [12, 24], 
        default=48
    )
    pengali_weekend = np.where(is_weekend == 1, 0.6, 1.0)
    pengali_event = np.where(ada_event == 1, 0.3, 1.0)
    
    persen_sisa = (100 - volume_sekarang) / 100
    jam_sampai_penuh = waktu_dasar_jam * persen_sisa * pengali_weekend * pengali_event
    
    # Tambahkan sedikit 'noise' (gangguan alam) biar ML tidak overfitting
    jam_sampai_penuh += np.random.normal(0, 1.5, num_samples) 
    jam_sampai_penuh = np.maximum(0.1, jam_sampai_penuh) # Pastikan ga ada nilai waktu minus
    
    # Target Model 2: Priority Route (Classification)
    kondisi = volume_sekarang + (kadar_metana * 0.5) + (laporan_warga * 5)
    pickup_priority = np.select(
        [kondisi > 120, kondisi > 80, kondisi > 40], 
        ['Critical', 'Urgent', 'Medium'], 
        default='Low'
    )
    
    # Target Model 3: Anomaly (Vandalism/Fire)
    # Logika anomali sekarang berjalan sempurna karena data vandalism sudah disuntikkan
    is_anomaly = np.where((suhu_cuaca > 36.5) | (delta_volume_sec < -3.0), 1, 0)
    
    
    # ==========================================
    # 3. KOMPILASI & PENYIMPANAN DATASET
    # ==========================================
    df = pd.DataFrame({
        'jam': jam,
        'suhu_cuaca': suhu_cuaca,
        'volume_sekarang': volume_sekarang,
        'tipe_lokasi': tipe_lokasi,
        'is_weekend': is_weekend,
        'ada_event': ada_event,
        'kadar_metana': kadar_metana,
        'laporan_warga': laporan_warga,
        'jarak_ultrasonik': jarak_ultrasonik,
        'delta_volume_sec': delta_volume_sec,
        'jam_sampai_penuh': jam_sampai_penuh,
        'pickup_priority': pickup_priority,
        'is_anomaly': is_anomaly
    })
    
    output_path = Path("data/trash_data.csv")
    df.to_csv(output_path, index=False)
    logging.info(f"Data successfully generated and saved to {output_path.absolute()}")

if __name__ == "__main__":
    generate_synthetic_data()