import pandas as pd
import joblib
import logging
from sklearn.model_selection import train_test_split
from sklearn.pipeline import Pipeline
from sklearn.compose import ColumnTransformer
from sklearn.preprocessing import StandardScaler, OneHotEncoder
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error, r2_score

logging.basicConfig(level=logging.INFO, format='%(asctime)s - Model 1 (Fill Rate) - %(message)s')

def train_fill_rate_model(data_path="./data/trash_data.csv", model_output_path="./models/fill_rate_model.pkl") -> None:
    logging.info("Loading dataset...")
    df = pd.read_csv(data_path)
    
    # 1. Masukan semua fitur yang relevan ke X
    X = df[['jam', 'suhu_cuaca', 'volume_sekarang', 'tipe_lokasi', 'is_weekend', 'ada_event']]
    y = df['jam_sampai_penuh']
    
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)
    
    # 2. Pisahkan perlakuan untuk kolom angka dan kolom teks
    numeric_features = ['jam', 'suhu_cuaca', 'volume_sekarang', 'is_weekend', 'ada_event']
    categorical_features = ['tipe_lokasi']
    
    #Gunakan ColumnTransformer untuk preprocessing yang berbeda
    preprocessor = ColumnTransformer(
        transformers=[
            ('num', StandardScaler(), numeric_features),
            ('cat', OneHotEncoder(handle_unknown='ignore'), categorical_features)
        ])
    
    # 3. Bungkus dalam Pipeline
    pipeline = Pipeline([
        ('preprocessor', preprocessor),
        # n_jobs=-1 bikin proses training jauh lebih ngebut!
        ('regressor', RandomForestRegressor(n_estimators=100, max_depth=10, random_state=42, n_jobs=-1))
    ])
    
    logging.info("Training pipeline...")
    pipeline.fit(X_train, y_train)
    
    # 4. Evaluation
    predictions = pipeline.predict(X_test)
    mae = mean_absolute_error(y_test, predictions)
    r2 = r2_score(y_test, predictions)
    
    logging.info(f"Model Evaluation - MAE: {mae:.2f} Jam, R2 Score: {r2:.3f}")
    
    joblib.dump(pipeline, model_output_path)
    logging.info(f"Model saved to {model_output_path}")

if __name__ == "__main__":
    train_fill_rate_model()