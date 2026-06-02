from __future__ import annotations

import json
import os
import threading
from pathlib import Path
from typing import Any, Dict, List, Optional

import numpy as np
import pandas as pd
from fastapi import FastAPI
from pydantic import BaseModel, Field

try:
    import joblib
except Exception:  # pragma: no cover - handled at runtime
    joblib = None


APP_DIR = Path(__file__).resolve().parent
ARTIFACT_DIR = APP_DIR / "models" / "artifacts"
METADATA_PATH = ARTIFACT_DIR / "model_metadata.json"

app = FastAPI(title="USE MED - XGBoost Predictive ML API", version="2.0.0")


class PatientFeatures(BaseModel):
    hn: str
    age: int = 0
    gender: str = "Unknown"
    disease_type: str = "Unknown"
    systolic: Optional[float] = None
    diastolic: Optional[float] = None
    hba1c: Optional[float] = None
    c_peptide: Optional[float] = None
    bmi: Optional[float] = None
    glucose: Optional[float] = None
    ldl: Optional[float] = None
    cholesterol: Optional[float] = None
    history_systolic: List[float] = Field(default_factory=list)
    history_hba1c: List[float] = Field(default_factory=list)
    med_metformin: int = 0
    med_insulin: int = 0
    med_ccb: int = 0
    med_arb: int = 0
    med_acei: int = 0
    med_diuretics: int = 0
    med_beta_blocker: int = 0
    co_dm: int = 0
    co_ht: int = 0
    co_ckd: int = 0
    co_stroke: int = 0
    co_hf: int = 0
    co_cad: int = 0
    co_arrhythmias: int = 0


class PredictionResponse(BaseModel):
    hn: str
    model_available: bool
    model_version: str = "unavailable"
    primary_condition: str
    risk_score: int = 0
    overall_priority: str = "P3"
    confidence: float = 0.0
    predicted_hba1c_60d: Optional[float] = None
    hba1c_uncontrolled_probability: Optional[float] = None
    predicted_systolic_60d: Optional[float] = None
    bp_uncontrolled_probability: Optional[float] = None
    top_factors: List[str] = Field(default_factory=list)
    recommendation: str = ""


MODELS: Dict[str, Dict[str, Any]] = {}
METADATA: Dict[str, Any] = {}
_MODELS_LOADED = False
_MODEL_LOAD_ERROR = ""
_MODEL_LOAD_LOCK = threading.Lock()


def load_models(force: bool = False) -> None:
    """Load ML artifacts lazily so free-tier cold starts stay lightweight."""
    global MODELS, METADATA, _MODELS_LOADED, _MODEL_LOAD_ERROR
    if _MODELS_LOADED and not force:
        return

    with _MODEL_LOAD_LOCK:
        if _MODELS_LOADED and not force:
            return

        MODELS = {}
        METADATA = {}
        _MODEL_LOAD_ERROR = ""

        if joblib is None:
            _MODELS_LOADED = True
            _MODEL_LOAD_ERROR = "joblib is unavailable"
            return
        if not METADATA_PATH.exists():
            _MODELS_LOADED = True
            _MODEL_LOAD_ERROR = "model metadata is missing"
            return

        try:
            METADATA = json.loads(METADATA_PATH.read_text(encoding="utf-8"))
            for model_info in METADATA.get("models", []):
                artifact = Path(model_info["artifact"])
                if not artifact.is_absolute():
                    artifact = ARTIFACT_DIR / artifact.name
                if artifact.exists():
                    MODELS[model_info["name"]] = joblib.load(artifact)
        except Exception as exc:  # pragma: no cover - defensive runtime fallback
            MODELS = {}
            METADATA = {}
            _MODEL_LOAD_ERROR = str(exc)
        finally:
            _MODELS_LOADED = True


@app.on_event("startup")
def startup_event() -> None:
    if os.getenv("USEMED_ML_PRELOAD", "0").lower() in {"1", "true", "on", "yes"}:
        load_models()


def disease_group(patient: PatientFeatures) -> str:
    text = patient.disease_type.lower()
    if "diabetes" in text or "gdm" in text or patient.hba1c is not None or patient.glucose is not None:
        return "diabetes"
    if "hypertension" in text or patient.systolic is not None or patient.diastolic is not None:
        return "hypertension"
    return "unknown"


def trend(current: Optional[float], history: List[float]) -> Optional[float]:
    values = [float(v) for v in history if v is not None]
    if current is None or not values:
        return None
    return float(current) - values[-1]


def make_feature_row(patient: PatientFeatures) -> Dict[str, Any]:
    group = disease_group(patient)
    disease = patient.disease_type.lower()
    gender = patient.gender.upper()
    return {
        "age": patient.age,
        "sex": "FEMALE" if gender in ["F", "FEMALE", "หญิง"] else "MALE" if gender in ["M", "MALE", "ชาย"] else "Unknown",
        "identify_by": "runtime",
        "disease_group": group,
        "current_sbp": patient.systolic,
        "current_dbp": patient.diastolic,
        "current_hr": None,
        "current_bmi": patient.bmi,
        "current_fpg": patient.glucose,
        "current_hba1c": patient.hba1c,
        "current_ldl": patient.ldl,
        "current_chol": patient.cholesterol,
        "trend_sbp": trend(patient.systolic, patient.history_systolic),
        "trend_dbp": None,
        "trend_hba1c": trend(patient.hba1c, patient.history_hba1c),
        "trend_fpg": None,
        "type1": 1 if "type 1" in disease else 0,
        "type2": 1 if "type 2" in disease or "diabetes" in disease else 0,
        "gdm": 1 if "gdm" in disease or "gestational" in disease else 0,
        "co_dm": patient.co_dm or (1 if "diabetes" in disease else 0),
        "co_ht": patient.co_ht or (1 if "hypertension" in disease else 0),
        "co_ckd": patient.co_ckd or (1 if "ckd" in disease else 0),
        "co_stroke": patient.co_stroke or (1 if "stroke" in disease else 0),
        "co_hf": patient.co_hf,
        "co_cad": patient.co_cad or (1 if "cad" in disease or "nstemi" in disease else 0),
        "co_arrhythmias": patient.co_arrhythmias,
        "med_metformin": patient.med_metformin,
        "med_insulin": patient.med_insulin,
        "med_ccb": patient.med_ccb,
        "med_arb": patient.med_arb,
        "med_acei": patient.med_acei,
        "med_diuretics": patient.med_diuretics,
        "med_beta_blocker": patient.med_beta_blocker,
    }


def matrix_for(payload: Dict[str, Any], bundle: Dict[str, Any]) -> pd.DataFrame:
    feature_columns = bundle["feature_columns"]
    frame = pd.DataFrame([payload])
    categorical_columns = frame.select_dtypes(include=["object", "string", "category"]).columns.tolist()
    frame = pd.get_dummies(frame, columns=categorical_columns, dummy_na=True)
    frame = frame.replace([np.inf, -np.inf], np.nan).fillna(-1)
    for column in frame.columns:
        frame[column] = pd.to_numeric(frame[column], errors="coerce").fillna(-1)
    return frame.reindex(columns=feature_columns, fill_value=0)


def probability(bundle_name: str, payload: Dict[str, Any]) -> Optional[float]:
    bundle = MODELS.get(bundle_name)
    if not bundle:
        return None
    model = bundle["model"]
    X = matrix_for(payload, bundle)
    if hasattr(model, "predict_proba"):
        return float(model.predict_proba(X)[0][1])
    return float(model.predict(X)[0])


def regression(bundle_name: str, payload: Dict[str, Any]) -> Optional[float]:
    bundle = MODELS.get(bundle_name)
    if not bundle:
        return None
    value = float(bundle["model"].predict(matrix_for(payload, bundle))[0])
    return round(value, 2)


def priority_prediction(payload: Dict[str, Any]) -> tuple[str, float]:
    bundle = MODELS.get("priority_classifier")
    if not bundle:
        return "P3", 0.0
    model = bundle["model"]
    X = matrix_for(payload, bundle)
    inverse = {v: k for k, v in bundle.get("label_map", {"P1": 0, "P2": 1, "P3": 2}).items()}
    if hasattr(model, "predict_proba"):
        probs = model.predict_proba(X)[0]
        idx = int(np.argmax(probs))
        return inverse.get(idx, "P3"), float(probs[idx])
    idx = int(model.predict(X)[0])
    return inverse.get(idx, "P3"), 0.0


def top_factors(payload: Dict[str, Any], limit: int = 5) -> List[str]:
    priority_bundle = MODELS.get("priority_classifier")
    if not priority_bundle:
        return []
    model = priority_bundle["model"]
    feature_columns = priority_bundle["feature_columns"]
    importances = getattr(model, "feature_importances_", None)
    if importances is None:
        return []
    X = matrix_for(payload, priority_bundle).iloc[0]
    ranked = []
    for col, importance in zip(feature_columns, importances):
        value = X.get(col, 0)
        if value != 0 and importance > 0:
            ranked.append((float(importance), col))
    return [name for _, name in sorted(ranked, reverse=True)[:limit]]


def recommendation(priority: str, hba1c_prob: Optional[float], bp_prob: Optional[float]) -> str:
    if priority == "P1":
        return "ML recommends same-day clinical review and follow-up task creation."
    if hba1c_prob is not None and hba1c_prob >= 0.65:
        return "ML predicts uncontrolled HbA1c risk; review diabetes medication, adherence, and lab follow-up."
    if bp_prob is not None and bp_prob >= 0.65:
        return "ML predicts uncontrolled BP risk; review home BP log and antihypertensive plan."
    if priority == "P2":
        return "ML recommends short-interval follow-up."
    return "ML recommends routine follow-up with continued monitoring."


@app.get("/health")
def health() -> Dict[str, Any]:
    return {
        "ok": True,
        "service": "ml",
        "model_loaded": _MODELS_LOADED,
        "model_available": bool(MODELS),
        "model_version": METADATA.get("model_version", "unavailable"),
        "models": list(MODELS.keys()),
        "load_error": _MODEL_LOAD_ERROR,
    }


@app.get("/model-info")
def model_info() -> Dict[str, Any]:
    load_models()
    return {"metadata": METADATA, "loaded_models": list(MODELS.keys()), "load_error": _MODEL_LOAD_ERROR}


@app.post("/predict", response_model=PredictionResponse)
def predict_risk(patient: PatientFeatures) -> PredictionResponse:
    payload = make_feature_row(patient)
    load_models()
    if not MODELS:
        return PredictionResponse(
            hn=patient.hn,
            model_available=False,
            primary_condition=patient.disease_type,
            recommendation="ML model artifacts are not available. Run ml_service/models/train.py and restart the service.",
        )

    group = payload["disease_group"]
    hba1c_prob = probability("hba1c_uncontrolled_classifier", payload) if group in ["diabetes", "unknown"] else None
    bp_prob = probability("bp_uncontrolled_classifier", payload) if group in ["hypertension", "unknown"] else None
    future_hba1c = regression("future_hba1c_regressor", payload) if group in ["diabetes", "unknown"] else None
    future_sbp = regression("future_sbp_regressor", payload) if group in ["hypertension", "unknown"] else None
    priority, confidence = priority_prediction(payload)

    risk_parts = [p for p in [hba1c_prob, bp_prob, confidence] if p is not None]
    risk_score = int(round(max(risk_parts or [0.0]) * 100))
    if priority == "P1":
        risk_score = max(risk_score, 80)
    elif priority == "P2":
        risk_score = max(risk_score, 55)

    return PredictionResponse(
        hn=patient.hn,
        model_available=True,
        model_version=METADATA.get("model_version", "usemed-xgb-agent-v1"),
        primary_condition=patient.disease_type,
        risk_score=min(100, risk_score),
        overall_priority=priority,
        confidence=round(float(confidence), 4),
        predicted_hba1c_60d=future_hba1c,
        hba1c_uncontrolled_probability=round(hba1c_prob, 4) if hba1c_prob is not None else None,
        predicted_systolic_60d=future_sbp,
        bp_uncontrolled_probability=round(bp_prob, 4) if bp_prob is not None else None,
        top_factors=top_factors(payload),
        recommendation=recommendation(priority, hba1c_prob, bp_prob),
    )


@app.post("/batch-predict", response_model=List[PredictionResponse])
def batch_predict(patients: List[PatientFeatures]) -> List[PredictionResponse]:
    return [predict_risk(patient) for patient in patients]
