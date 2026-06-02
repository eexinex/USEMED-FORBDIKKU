-- Reset USE MED application tables in Supabase public schema.
-- This keeps Supabase auth/storage/settings intact.

DROP TABLE IF EXISTS
  ml_prediction_logs,
  ml_model_registry,
  ml_training_snapshots,
  ml_patient_features,
  patient_longitudinal_records,
  followup_tasks,
  ai_population_reasons,
  ai_population_scores,
  ems_cases,
  prescription_items,
  prescriptions,
  patient_self_assessments,
  support_tickets,
  referrals,
  documents,
  visits,
  admin_users,
  doctors,
  patients
CASCADE;
