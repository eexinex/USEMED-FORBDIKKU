-- USE MED ML schema
-- Run this once in Supabase before importing agent datasets or writing ML predictions.

CREATE TABLE IF NOT EXISTS ml_model_registry (
    id SERIAL PRIMARY KEY,
    model_name VARCHAR(120) NOT NULL,
    model_version VARCHAR(120) NOT NULL,
    model_type VARCHAR(80) NOT NULL,
    target_name VARCHAR(120) NOT NULL,
    artifact_path VARCHAR(500) NOT NULL,
    metrics_json TEXT DEFAULT NULL,
    feature_names_json TEXT DEFAULT NULL,
    training_rows INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active SMALLINT DEFAULT 1,
    UNIQUE (model_name, model_version)
);

CREATE TABLE IF NOT EXISTS ml_prediction_logs (
    id SERIAL PRIMARY KEY,
    patient_id INT DEFAULT NULL REFERENCES patients(id) ON DELETE SET NULL,
    hn VARCHAR(50) DEFAULT NULL,
    model_version VARCHAR(120) NOT NULL,
    input_snapshot TEXT DEFAULT NULL,
    prediction_json TEXT NOT NULL,
    confidence DECIMAL(8,4) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ml_training_snapshots (
    id SERIAL PRIMARY KEY,
    source_dataset VARCHAR(120) NOT NULL,
    hn VARCHAR(50) DEFAULT NULL,
    disease_group VARCHAR(80) DEFAULT NULL,
    feature_snapshot TEXT NOT NULL,
    label_snapshot TEXT NOT NULL,
    split_name VARCHAR(40) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ml_patient_features (
    id SERIAL PRIMARY KEY,
    patient_id INT DEFAULT NULL REFERENCES patients(id) ON DELETE CASCADE,
    hn VARCHAR(50) NOT NULL,
    disease_group VARCHAR(80) NOT NULL,
    source_dataset VARCHAR(120) NOT NULL,
    baseline_period INT DEFAULT 0,
    feature_snapshot TEXT NOT NULL,
    label_snapshot TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (hn, disease_group)
);

CREATE INDEX IF NOT EXISTS idx_ml_prediction_hn ON ml_prediction_logs (hn);
CREATE INDEX IF NOT EXISTS idx_ml_prediction_model ON ml_prediction_logs (model_version);
CREATE INDEX IF NOT EXISTS idx_ml_training_source ON ml_training_snapshots (source_dataset);
CREATE INDEX IF NOT EXISTS idx_ml_patient_features_hn ON ml_patient_features (hn);
