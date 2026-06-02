#!/usr/bin/env python3
"""Train USE MED XGBoost models from imported clinical longitudinal datasets."""

from __future__ import annotations

import argparse
import json
import re
import sys
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

import numpy as np
import pandas as pd

ROOT = Path(__file__).resolve().parents[2]
if str(ROOT) not in sys.path:
    sys.path.insert(0, str(ROOT))

from ml_service.data.import_agent_datasets import (  # noqa: E402
    DEFAULT_DATASET_DIR,
    build_features,
    build_labels,
    iter_workbook_rows,
    period_columns,
    risk_from_labels,
)


ARTIFACT_DIR = Path(__file__).resolve().parent / "artifacts"
DEFAULT_SEED_SQL = ROOT / "backend" / "database" / "agent_dataset_seed.sql"


def require_ml_dependencies() -> tuple[Any, Any, Any, Any, Any]:
    try:
        import joblib
        from sklearn.metrics import accuracy_score, mean_absolute_error, roc_auc_score
        from sklearn.model_selection import train_test_split
        from xgboost import XGBClassifier, XGBRegressor
    except ImportError as exc:
        raise SystemExit(
            "Missing ML dependency. Install project requirements first: "
            "pip install -r ml_service/requirements.txt"
        ) from exc
    return joblib, train_test_split, (accuracy_score, mean_absolute_error, roc_auc_score), XGBClassifier, XGBRegressor


def split_sql_values(values_sql: str) -> list[str | None]:
    values: list[str | None] = []
    current: list[str] = []
    in_string = False
    i = 0
    while i < len(values_sql):
        char = values_sql[i]
        if char == "'":
            if in_string and i + 1 < len(values_sql) and values_sql[i + 1] == "'":
                current.append("'")
                i += 2
                continue
            in_string = not in_string
            i += 1
            continue
        if char == "," and not in_string:
            raw = "".join(current).strip()
            values.append(None if raw.upper() == "NULL" else raw)
            current = []
            i += 1
            continue
        current.append(char)
        i += 1
    raw = "".join(current).strip()
    values.append(None if raw.upper() == "NULL" else raw)
    return values


def load_training_rows_from_seed(seed_sql: Path) -> pd.DataFrame:
    if not seed_sql.exists():
        raise FileNotFoundError(f"Training data not found: {seed_sql}")

    rows: list[dict[str, Any]] = []
    pattern = re.compile(r"INSERT INTO ml_training_snapshots .* VALUES \((.*)\);$")
    for idx, line in enumerate(seed_sql.read_text(encoding="utf-8").splitlines(), 1):
        match = pattern.match(line)
        if not match:
            continue
        values = split_sql_values(match.group(1))
        if len(values) < 5 or values[3] is None or values[4] is None:
            continue
        source_dataset, hn, disease_group, feature_json, label_json = values[:5]
        features = json.loads(feature_json)
        labels = json.loads(label_json)
        score, level, _ = risk_from_labels((disease_group or "unknown"), labels, features)
        priority = "P1" if score >= 80 else "P2" if score >= 55 else "P3"
        rows.append(
            {
                "source_dataset": source_dataset,
                "source_row": idx,
                "hn": hn,
                "disease_group": disease_group,
                **features,
                "future_hba1c": labels.get("future_hba1c"),
                "future_sbp": labels.get("future_sbp"),
                "future_dbp": labels.get("future_dbp"),
                "hba1c_uncontrolled": labels.get("hba1c_uncontrolled"),
                "bp_uncontrolled": labels.get("bp_uncontrolled"),
                "needs_followup": labels.get("needs_followup"),
                "priority_label": priority,
                "derived_risk_score": score,
                "derived_risk_level": level,
            }
        )
    return pd.DataFrame(rows)


def load_training_rows(dataset_dir: Path, seed_sql: Path = DEFAULT_SEED_SQL) -> pd.DataFrame:
    configs = [
        ("diabetes", dataset_dir / "data_dictionary_diabetes_example.xlsx"),
        ("hypertension", dataset_dir / "data_dictionary_hypertension_example.xlsx"),
    ]
    if not all(path.exists() for _, path in configs):
        return load_training_rows_from_seed(seed_sql)

    rows: list[dict[str, Any]] = []
    for disease_group, path in configs:
        for idx, source_row in enumerate(iter_workbook_rows(path), 1):
            periods = period_columns(source_row)
            features = build_features(source_row, periods, disease_group)
            labels = build_labels(periods, disease_group)
            score, level, _ = risk_from_labels(disease_group, labels, source_row)
            priority = "P1" if score >= 80 else "P2" if score >= 55 else "P3"
            rows.append(
                {
                    "source_dataset": path.name,
                    "source_row": idx,
                    "disease_group": disease_group,
                    **features,
                    "future_hba1c": labels.get("future_hba1c"),
                    "future_sbp": labels.get("future_sbp"),
                    "future_dbp": labels.get("future_dbp"),
                    "hba1c_uncontrolled": labels.get("hba1c_uncontrolled"),
                    "bp_uncontrolled": labels.get("bp_uncontrolled"),
                    "needs_followup": labels.get("needs_followup"),
                    "priority_label": priority,
                    "derived_risk_score": score,
                    "derived_risk_level": level,
                }
            )
    return pd.DataFrame(rows)


def encode_features(df: pd.DataFrame, feature_columns: list[str] | None = None) -> tuple[pd.DataFrame, list[str]]:
    drop_columns = {
        "source_dataset",
        "source_row",
        "future_hba1c",
        "future_sbp",
        "future_dbp",
        "hba1c_uncontrolled",
        "bp_uncontrolled",
        "needs_followup",
        "priority_label",
        "derived_risk_score",
        "derived_risk_level",
    }
    feature_df = df[[col for col in df.columns if col not in drop_columns]].copy()
    categorical_columns = feature_df.select_dtypes(include=["object", "string", "category"]).columns.tolist()
    feature_df = pd.get_dummies(feature_df, columns=categorical_columns, dummy_na=True)
    feature_df = feature_df.replace([np.inf, -np.inf], np.nan).fillna(-1)
    for column in feature_df.columns:
        feature_df[column] = pd.to_numeric(feature_df[column], errors="coerce").fillna(-1)
    if feature_columns is None:
        feature_columns = list(feature_df.columns)
    return feature_df.reindex(columns=feature_columns, fill_value=0), feature_columns


def split_data(X: pd.DataFrame, y: pd.Series, train_test_split: Any, stratify: bool = False):
    if len(X) < 12:
        return X, X, y, y
    stratify_arg = y if stratify and y.nunique() > 1 and y.value_counts().min() >= 2 else None
    return train_test_split(X, y, test_size=0.25, random_state=42, stratify=stratify_arg)


def classification_metrics(model: Any, X_test: pd.DataFrame, y_test: pd.Series, metrics: tuple[Any, Any, Any]) -> dict[str, Any]:
    accuracy_score, _, roc_auc_score = metrics
    pred = model.predict(X_test)
    result: dict[str, Any] = {"accuracy": float(accuracy_score(y_test, pred)), "rows": int(len(y_test))}
    if len(set(y_test)) == 2 and hasattr(model, "predict_proba"):
        try:
            result["roc_auc"] = float(roc_auc_score(y_test, model.predict_proba(X_test)[:, 1]))
        except Exception:
            pass
    return result


def regression_metrics(model: Any, X_test: pd.DataFrame, y_test: pd.Series, metrics: tuple[Any, Any, Any]) -> dict[str, Any]:
    _, mean_absolute_error, _ = metrics
    pred = model.predict(X_test)
    return {"mae": float(mean_absolute_error(y_test, pred)), "rows": int(len(y_test))}


def train_classifier(
    name: str,
    df: pd.DataFrame,
    target: str,
    artifact_dir: Path,
    joblib: Any,
    train_test_split: Any,
    metrics: tuple[Any, Any, Any],
    XGBClassifier: Any,
) -> dict[str, Any] | None:
    work = df[df[target].notna()].copy()
    if work.empty or work[target].nunique() < 2:
        return None
    y = work[target].astype(int)
    X, feature_columns = encode_features(work)
    X_train, X_test, y_train, y_test = split_data(X, y, train_test_split, stratify=True)
    model = XGBClassifier(
        n_estimators=120,
        max_depth=3,
        learning_rate=0.06,
        subsample=0.9,
        colsample_bytree=0.9,
        objective="binary:logistic",
        eval_metric="logloss",
        random_state=42,
        n_jobs=1,
    )
    model.fit(X_train, y_train)
    artifact_path = artifact_dir / f"{name}.joblib"
    joblib.dump({"model": model, "feature_columns": feature_columns, "target": target, "kind": "classifier"}, artifact_path)
    return {"name": name, "target": target, "kind": "classifier", "artifact": artifact_path.name, "metrics": classification_metrics(model, X_test, y_test, metrics)}


def train_priority_classifier(
    df: pd.DataFrame,
    artifact_dir: Path,
    joblib: Any,
    train_test_split: Any,
    metrics: tuple[Any, Any, Any],
    XGBClassifier: Any,
) -> dict[str, Any] | None:
    label_map = {"P1": 0, "P2": 1, "P3": 2}
    work = df[df["priority_label"].isin(label_map)].copy()
    if work.empty or work["priority_label"].nunique() < 2:
        return None
    y = work["priority_label"].map(label_map).astype(int)
    X, feature_columns = encode_features(work)
    X_train, X_test, y_train, y_test = split_data(X, y, train_test_split, stratify=True)
    model = XGBClassifier(
        n_estimators=160,
        max_depth=3,
        learning_rate=0.05,
        subsample=0.9,
        colsample_bytree=0.9,
        objective="multi:softprob",
        num_class=3,
        eval_metric="mlogloss",
        random_state=42,
        n_jobs=1,
    )
    model.fit(X_train, y_train)
    artifact_path = artifact_dir / "priority_classifier.joblib"
    joblib.dump(
        {"model": model, "feature_columns": feature_columns, "target": "priority_label", "kind": "priority_classifier", "label_map": label_map},
        artifact_path,
    )
    result = classification_metrics(model, X_test, y_test, metrics)
    return {"name": "priority_classifier", "target": "priority_label", "kind": "priority_classifier", "artifact": artifact_path.name, "metrics": result, "label_map": label_map}


def train_regressor(
    name: str,
    df: pd.DataFrame,
    target: str,
    artifact_dir: Path,
    joblib: Any,
    train_test_split: Any,
    metrics: tuple[Any, Any, Any],
    XGBRegressor: Any,
) -> dict[str, Any] | None:
    work = df[df[target].notna()].copy()
    if len(work) < 8:
        return None
    y = work[target].astype(float)
    X, feature_columns = encode_features(work)
    X_train, X_test, y_train, y_test = split_data(X, y, train_test_split)
    model = XGBRegressor(
        n_estimators=160,
        max_depth=3,
        learning_rate=0.05,
        subsample=0.9,
        colsample_bytree=0.9,
        objective="reg:squarederror",
        random_state=42,
        n_jobs=1,
    )
    model.fit(X_train, y_train)
    artifact_path = artifact_dir / f"{name}.joblib"
    joblib.dump({"model": model, "feature_columns": feature_columns, "target": target, "kind": "regressor"}, artifact_path)
    return {"name": name, "target": target, "kind": "regressor", "artifact": artifact_path.name, "metrics": regression_metrics(model, X_test, y_test, metrics)}


def main() -> None:
    parser = argparse.ArgumentParser(description="Train USE MED XGBoost models.")
    parser.add_argument("--dataset-dir", type=Path, default=DEFAULT_DATASET_DIR)
    parser.add_argument("--seed-sql", type=Path, default=DEFAULT_SEED_SQL)
    parser.add_argument("--artifact-dir", type=Path, default=ARTIFACT_DIR)
    args = parser.parse_args()

    joblib, train_test_split, metrics, XGBClassifier, XGBRegressor = require_ml_dependencies()
    args.artifact_dir.mkdir(parents=True, exist_ok=True)
    df = load_training_rows(args.dataset_dir, args.seed_sql)
    if df.empty:
        raise SystemExit("No training rows found. Check dataset Excel files or backend/database/agent_dataset_seed.sql.")

    trained = []
    trained.append(train_classifier("hba1c_uncontrolled_classifier", df[df["disease_group"] == "diabetes"], "hba1c_uncontrolled", args.artifact_dir, joblib, train_test_split, metrics, XGBClassifier))
    trained.append(train_regressor("future_hba1c_regressor", df[df["disease_group"] == "diabetes"], "future_hba1c", args.artifact_dir, joblib, train_test_split, metrics, XGBRegressor))
    trained.append(train_classifier("bp_uncontrolled_classifier", df[df["disease_group"] == "hypertension"], "bp_uncontrolled", args.artifact_dir, joblib, train_test_split, metrics, XGBClassifier))
    trained.append(train_regressor("future_sbp_regressor", df[df["disease_group"] == "hypertension"], "future_sbp", args.artifact_dir, joblib, train_test_split, metrics, XGBRegressor))
    trained.append(train_priority_classifier(df, args.artifact_dir, joblib, train_test_split, metrics, XGBClassifier))
    trained = [item for item in trained if item is not None]

    metadata = {
        "model_version": "usemed-xgb-agent-v1",
        "trained_at": datetime.now(timezone.utc).isoformat(),
        "training_rows": int(len(df)),
        "models": trained,
    }
    metadata_path = args.artifact_dir / "model_metadata.json"
    metadata_path.write_text(json.dumps(metadata, ensure_ascii=False, indent=2), encoding="utf-8")
    print(json.dumps(metadata, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
