# USE MED XGBoost ML Setup

This project now uses the Excel datasets in `.agents/dataset_for_agent` to seed patients and train XGBoost models.

## 1. Reset and import the dataset into Supabase

For a clean Supabase test database, run:

```text
backend/database/reset_public_app_tables.sql
```

Then run:

```text
backend/database/schema.sql
backend/database/ml_schema.sql
```

The full dataset SQL is too large for the Supabase SQL Editor, so run the chunk files in order:

```text
backend/database/agent_dataset_seed_chunks/chunk_01.sql
...
backend/database/agent_dataset_seed_chunks/chunk_20.sql
```

The chunks insert:

- 200 sample patients
- 3,179 visits
- 3,179 longitudinal records
- 200 ML training snapshots

## 2. Train the XGBoost models

```powershell
pip install -r ml_service/requirements.txt
python ml_service/models/train.py
```

Artifacts are written to:

```text
ml_service/models/artifacts/
```

Do not commit `.joblib` artifacts. Hugging Face Spaces rejects normal Git pushes containing binary model files unless Xet is used. The Docker image trains the artifacts during build from `backend/database/agent_dataset_seed.sql`.

## 3. Start the ML service

```powershell
uvicorn ml_service.main:app --host 127.0.0.1 --port 8000
```

The PHP app reads `USEMED_ML_URL`; default is:

```text
http://127.0.0.1:8000/predict
```

## 4. Refresh cached population predictions

On Hugging Face, log in as admin and open:

```text
/admin/ml-refresh.php
```

Click through the batches until `Cached ML` equals `Patients`.

If you have shell access, you can also run:

```powershell
php backend/database/ml_refresh_predictions.php
```

Population Health reads cached XGBoost scores first, so page loads stay fast.
