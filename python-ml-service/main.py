import logging
from contextlib import asynccontextmanager
from pathlib import Path
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field
import joblib
import pandas as pd

BASE_DIR = Path(__file__).resolve().parent

# Setup Logger
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("TrashTrack-ML-API")

# Global dict to store models in memory
ml_models = {}

@asynccontextmanager
async def lifespan(app: FastAPI):
    """Lifespan event to load models on startup and clear on shutdown."""
    try:
        logger.info("Loading ML models into memory...")
        # UPDATE 1: Sesuaikan nama file dengan hasil training terakhir kita
        ml_models['fill_rate'] = joblib.load(BASE_DIR / 'models' / 'fill_rate_model.pkl')
        ml_models['priority'] = joblib.load(BASE_DIR / 'models' / 'priority_model.pkl')
        ml_models['anomaly'] = joblib.load(BASE_DIR / 'models' / 'anomaly_model.pkl')
        logger.info("All models loaded successfully!")
        yield
    except Exception as e:
        logger.error(f"Failed to load models. Did you run the training scripts? Error: {e}")
        yield
    finally:
        ml_models.clear()

app = FastAPI(title="TrashTrack AI Inference Service", lifespan=lifespan)

# --- Pydantic Data Schemas ---

class FillRateRequest(BaseModel):
    jam: int = Field(..., ge=0, le=23, description="Jam saat ini (0-23)")
    suhu_cuaca: float = Field(..., description="Suhu cuaca di luar (Celcius)")
    volume_sekarang: float = Field(..., ge=0, le=100, description="Persentase volume (0-100)")
    latitude: float = Field(..., description="Latitude lokasi tong sampah")
    longitude: float = Field(..., description="Longitude lokasi tong sampah")
    is_weekend: int = Field(..., ge=0, le=1, description="1 jika Sabtu/Minggu, 0 jika hari biasa")

class PriorityRequest(BaseModel):
    volume_sekarang: float = Field(..., ge=0, le=100)
    kadar_metana: float = Field(..., ge=0)
    laporan_warga: int = Field(..., ge=0)

class AnomalyRequest(BaseModel):
    jarak_ultrasonik: float = Field(..., description="Jarak sensor ultrasonik ke sampah (cm)")
    delta_volume_sec: float = Field(..., description="Perubahan volume dalam 1 detik terakhir")
    suhu_cuaca: float = Field(..., description="Suhu internal tong sampah (Celcius)")

# --- Endpoints ---
@app.get("/health")
def health_check():
    return {"status": "healthy", "models_loaded": len(ml_models)}

@app.post("/predict/fill-rate")
def predict_fill_rate(payload: FillRateRequest):
    if 'fill_rate' not in ml_models:
        raise HTTPException(status_code=503, detail="Model not loaded")
    
    input_df = pd.DataFrame([payload.model_dump()])
    prediction = ml_models['fill_rate'].predict(input_df)[0]
    return {
        "service": "fill-rate-predictor",
        "hours_until_full": round(prediction, 2)
    }

@app.post("/predict/priority")
def predict_priority(payload: PriorityRequest):
    if 'priority' not in ml_models:
        raise HTTPException(status_code=503, detail="Model not loaded")
    
    input_df = pd.DataFrame([payload.model_dump()])
    prediction = ml_models['priority'].predict(input_df)[0]
    return {
        "service": "priority-classifier",
        "pickup_priority": prediction
    }

@app.post("/detect/anomaly")
def detect_anomaly(payload: AnomalyRequest):
    if 'anomaly' not in ml_models:
        raise HTTPException(status_code=503, detail="Model not loaded")
    
    input_df = pd.DataFrame([payload.model_dump()])
    prediction = ml_models['anomaly'].predict(input_df)[0]
    
    # UPDATE 3: Logika Supervised Learning (RandomForest) -> 1 adalah Anomali
    is_anomaly = True if prediction == 1 else False
    
    return {
        "service": "anomaly-detector",
        "is_anomaly": is_anomaly
    }