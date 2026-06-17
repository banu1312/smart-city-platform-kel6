import os
import pandas as pd
from sklearn import pipeline
import joblib
import logging
from sklearn.model_selection import train_test_split
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import StandardScaler
from sklearn.ensemble import GradientBoostingClassifier
from sklearn.metrics import classification_report

logging.basicConfig(level=logging.INFO, format='%(asctime)s - Model 2 (Priority) - %(message)s')

def train_priority_model(data_path="./data/trash_data.csv", model_output_path="./models/priority_model.pkl") -> None:
    logging.info("Loading dataset...")
    df = pd.read_csv(data_path)
    
    # Fitur yang dipakai (hanya yang relevan dengan perhitungan prioritas)
    x = df[['volume_sekarang','kadar_metana','laporan_warga']]
    y = df['pickup_priority']

    x_train, x_test, y_train, y_test = train_test_split(x, y, test_size=0.2, random_state=42)

    # Pastikan folder 'models' tersedia agar tidak error saat menyimpan model
    os.makedirs(os.path.dirname(model_output_path), exist_ok=True)
    
    # Catatan: Kita tidak butuh ColumnTransformer/OneHotEncoder di sini 
    # karena semua fitur penentu prioritas adalah angka (numerik), bukan teks.
    pipeline = Pipeline([
        ('scaler', StandardScaler()),
        ('classifier', GradientBoostingClassifier(n_estimators=100, learning_rate=0.1, random_state=42))
    ])
    
    logging.info("Training pipeline...")
    pipeline.fit(x_train, y_train)

    #Evaluation
    logging.info("Evaluating model...")
    predictions = pipeline.predict(x_test)

    # Print rapor evaluasi klasifikasi
    report = classification_report(y_test, predictions)
    logging.info(f"Model Evaluation:\n{report}")

    joblib.dump(pipeline, model_output_path)
    logging.info(f"Model saved to {model_output_path}")

if __name__ == "__main__":
    train_priority_model()