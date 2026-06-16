import os
import pandas as pd
import joblib
import logging
from sklearn.model_selection import train_test_split
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import StandardScaler
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import classification_report

logging.basicConfig(level=logging.INFO, format='%(asctime)s - Model 3 (Anomaly) - %(message)s')

def train_anomaly_model(data_path="./data/trash_data.csv", model_output_path="./models/anomaly_model.pkl") -> None:
    logging.info("Loading dataset...")
    df = pd.read_csv(data_path)
    
    # X = Input dari sensor Wokwi nantinya (Ultrasonik & Suhu)
    X = df[['jarak_ultrasonik', 'delta_volume_sec', 'suhu_cuaca']]
    # y = Kita wajibkan model belajar dari kunci jawaban langsung!
    y = df['is_anomaly']
    
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)
    
    os.makedirs(os.path.dirname(model_output_path), exist_ok=True)
    
    pipeline = Pipeline([
        ('scaler', StandardScaler()),
        # class_weight='balanced' memastikan model tetap waspada terhadap anomali 
        # meskipun persentase kejadiannya lebih kecil dari kondisi normal.
        ('classifier', RandomForestClassifier(n_estimators=100, max_depth=10, random_state=42, n_jobs=-1, class_weight='balanced'))
    ])
    
    logging.info("Training pipeline (Supervised Learning)...")
    pipeline.fit(X_train, y_train)
    
    logging.info("Evaluating model...")
    predictions = pipeline.predict(X_test)
    
    # Rapor Evaluasi
    report = classification_report(y_test, predictions, target_names=['Normal (0)', 'Anomali (1)'])
    logging.info(f"\nAnomaly Detection Report:\n{report}")
    
    joblib.dump(pipeline, model_output_path)
    logging.info(f"Model saved to {model_output_path}")

if __name__ == "__main__":
    train_anomaly_model()