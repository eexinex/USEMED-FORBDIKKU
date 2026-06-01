-- backend/database/schema.sql
-- USE MED demo data: 10 patients, 3 doctors, patient flow, referrals

START TRANSACTION;
SET TIME ZONE 'UTC';

CREATE TABLE IF NOT EXISTS patients (
    id SERIAL PRIMARY KEY,
    hn VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    gender VARCHAR(20) DEFAULT NULL,
    age INT DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    id_card VARCHAR(30) DEFAULT NULL,
    birth_date DATE DEFAULT NULL,
    disease VARCHAR(255) DEFAULT NULL,
    allergy_history TEXT DEFAULT NULL,
    address TEXT DEFAULT NULL,
    care_area VARCHAR(80) DEFAULT 'OPD',
    hospital VARCHAR(255) DEFAULT NULL,
    ward VARCHAR(255) DEFAULT NULL,
    surgery_status VARCHAR(255) DEFAULT NULL,
    high_watch SMALLINT DEFAULT 0,
    blood_group VARCHAR(20) DEFAULT NULL,
    payment_method VARCHAR(100) DEFAULT NULL,
    insurance_detail VARCHAR(255) DEFAULT NULL,
    department VARCHAR(255) DEFAULT NULL,
    risk_level VARCHAR(50) DEFAULT NULL,
    risk_score INT DEFAULT NULL,
    admission_date DATE DEFAULT NULL,
    expected_discharge_date VARCHAR(50) DEFAULT NULL,
    discharge_date DATE DEFAULT NULL,
    additional_medication TEXT DEFAULT NULL,
    operation_name VARCHAR(255) DEFAULT NULL,
    operation_date DATE DEFAULT NULL,
    operation_status VARCHAR(255) DEFAULT NULL,
    operation_size VARCHAR(100) DEFAULT NULL,
    icu_day VARCHAR(80) DEFAULT NULL,
    icu_daily_note TEXT DEFAULT NULL,
    ventilator_status VARCHAR(255) DEFAULT NULL,
    vasopressor_status VARCHAR(255) DEFAULT NULL,
    fluid_balance VARCHAR(100) DEFAULT NULL,
    line_tube_status VARCHAR(255) DEFAULT NULL,
    followup_plan TEXT DEFAULT NULL,
    discharge_plan TEXT DEFAULT NULL,
    daily_note TEXT DEFAULT NULL,
    monitoring_frequency VARCHAR(120) DEFAULT NULL,
    escalation_plan TEXT DEFAULT NULL,
    last_round_date TIMESTAMP DEFAULT NULL,
    registration_source VARCHAR(80) DEFAULT NULL,
    registration_status VARCHAR(80) DEFAULT 'active',
    consent_accepted_at TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL 
);

CREATE TABLE IF NOT EXISTS doctors (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    license_no VARCHAR(100) DEFAULT NULL,
    department VARCHAR(255) DEFAULT NULL,
    hospital VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin_users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS visits (
    id SERIAL PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT DEFAULT NULL,
    visit_date DATE NOT NULL,
    title VARCHAR(255) NOT NULL,
    diagnosis TEXT DEFAULT NULL,
    treatment_plan TEXT DEFAULT NULL,
    systolic INT DEFAULT NULL,
    diastolic INT DEFAULT NULL,
    pulse INT DEFAULT NULL,
    glucose DECIMAL(8,2) DEFAULT NULL,
    hba1c DECIMAL(5,2) DEFAULT NULL,
    bmi DECIMAL(5,2) DEFAULT NULL,
    cholesterol DECIMAL(8,2) DEFAULT NULL,
    visit_type VARCHAR(80) DEFAULT NULL,
    visit_reason TEXT DEFAULT NULL,
    care_area VARCHAR(80) DEFAULT NULL,
    hospital VARCHAR(255) DEFAULT NULL,
    payment_method VARCHAR(100) DEFAULT NULL,
    insurance_detail VARCHAR(255) DEFAULT NULL,
    blood_group VARCHAR(20) DEFAULT NULL,
    weight_kg DECIMAL(6,2) DEFAULT NULL,
    height_cm DECIMAL(6,2) DEFAULT NULL,
    temperature DECIMAL(4,1) DEFAULT NULL,
    respiratory_rate INT DEFAULT NULL,
    oxygen_saturation INT DEFAULT NULL,
    alcohol_use VARCHAR(80) DEFAULT NULL,
    smoking_status VARCHAR(80) DEFAULT NULL,
    has_surgery VARCHAR(30) DEFAULT NULL,
    surgery_type VARCHAR(80) DEFAULT NULL,
    surgery_note TEXT DEFAULT NULL,
    has_menstruation VARCHAR(50) DEFAULT NULL,
    last_menstrual_period DATE DEFAULT NULL,
    investigations TEXT DEFAULT NULL,
    lab_results TEXT DEFAULT NULL,
    urine_results TEXT DEFAULT NULL,
    xray_results TEXT DEFAULT NULL,
    mri_results TEXT DEFAULT NULL,
    imaging_results TEXT DEFAULT NULL,
    doctor_education TEXT DEFAULT NULL,
    next_appointment_detail TEXT DEFAULT NULL,
    followup_date DATE DEFAULT NULL,
    chief_complaint TEXT DEFAULT NULL,
    present_illness TEXT DEFAULT NULL,
    review_of_systems TEXT DEFAULT NULL,
    past_history TEXT DEFAULT NULL,
    past_surgical_history TEXT DEFAULT NULL,
    allergy_type VARCHAR(120) DEFAULT NULL,
    allergy_history TEXT DEFAULT NULL,
    current_medications TEXT DEFAULT NULL,
    family_history TEXT DEFAULT NULL,
    social_history TEXT DEFAULT NULL,
    immunization_history TEXT DEFAULT NULL,
    pregnancy_status VARCHAR(120) DEFAULT NULL,
    physical_exam TEXT DEFAULT NULL,
    physical_exam_general TEXT DEFAULT NULL,
    physical_exam_heent TEXT DEFAULT NULL,
    physical_exam_chest_lung TEXT DEFAULT NULL,
    physical_exam_cvs TEXT DEFAULT NULL,
    physical_exam_abdomen TEXT DEFAULT NULL,
    physical_exam_neuro TEXT DEFAULT NULL,
    physical_exam_extremity TEXT DEFAULT NULL,
    physical_exam_skin TEXT DEFAULT NULL,
    provisional_diagnosis TEXT DEFAULT NULL,
    differential_diagnosis TEXT DEFAULT NULL,
    final_diagnosis TEXT DEFAULT NULL,
    icd10_code VARCHAR(50) DEFAULT NULL,
    assessment TEXT DEFAULT NULL,
    procedure_name VARCHAR(255) DEFAULT NULL,
    procedure_note TEXT DEFAULT NULL,
    anesthesia_type VARCHAR(120) DEFAULT NULL,
    medication_orders TEXT DEFAULT NULL,
    nursing_instructions TEXT DEFAULT NULL,
    consult_request TEXT DEFAULT NULL,
    disposition VARCHAR(120) DEFAULT NULL,
    admission_ward VARCHAR(255) DEFAULT NULL,
    triage_level VARCHAR(80) DEFAULT NULL,
    red_flags TEXT DEFAULT NULL,
    consent_status VARCHAR(120) DEFAULT NULL,
    risk_score INT DEFAULT NULL,
    risk_level VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_visits_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    CONSTRAINT fk_visits_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS documents (
    id SERIAL PRIMARY KEY,
    patient_id INT NOT NULL,
    visit_id INT DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    document_type VARCHAR(100) DEFAULT 'PDF',
    file_path VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_documents_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    CONSTRAINT fk_documents_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS referrals (
    id SERIAL PRIMARY KEY,
    patient_id INT DEFAULT NULL,
    doctor_id INT DEFAULT NULL,
    from_department VARCHAR(255) DEFAULT NULL,
    to_department VARCHAR(255) NOT NULL,
    to_doctor VARCHAR(255) DEFAULT NULL,
    to_hospital VARCHAR(255) NOT NULL,
    urgency VARCHAR(50) DEFAULT 'ปกติ',
    reason TEXT NOT NULL,
    status VARCHAR(80) DEFAULT 'รอรับเคส',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_referrals_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL,
    CONSTRAINT fk_referrals_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS support_tickets (
    id SERIAL PRIMARY KEY,
    user_role VARCHAR(50) DEFAULT NULL,
    user_name VARCHAR(255) DEFAULT NULL,
    subject VARCHAR(255) NOT NULL,
    problem_type VARCHAR(100) DEFAULT NULL,
    menu_path VARCHAR(255) DEFAULT NULL,
    message TEXT NOT NULL,
    status VARCHAR(50) DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO patients (hn, password, full_name, gender, age, phone, disease, address, care_area, hospital, ward, surgery_status, high_watch)
VALUES
('HN0001','123456','สมชาย ใจดี','ชาย',58,'081-234-5678','Type 2 Diabetes Mellitus','อ.เมือง จ.ขอนแก่น','OPD','โรงพยาบาลขอนแก่น','OPD เบาหวาน','-',0),
('HN0002','123456','สมหญิง สุขใจ','หญิง',64,'082-111-2233','Hypertension / CKD stage 3','อ.บ้านไผ่ จ.ขอนแก่น','IPD','โรงพยาบาลศรีนครินทร์','อายุรกรรมหญิง 5A','รอประเมิน',1),
('HN0003','123456','อนันต์ แสงทอง','ชาย',42,'083-456-7890','Acute appendicitis','เขตลาดกระบัง กรุงเทพฯ','ผ่าตัด','โรงพยาบาลพระจอมเกล้าเจ้าคุณทหาร','OR 2','กำลังผ่าตัด',0),
('HN0004','123456','ปรียา วงศ์สวัสดิ์','หญิง',36,'084-777-9000','Gallstone','เขตราชเทวี กรุงเทพฯ','คิวผ่าตัด','โรงพยาบาลราชวิถี','Surgical Queue','คิวผ่าตัด 18 มิ.ย. 2026',0),
('HN0005','123456','วิชัย มั่นคง','ชาย',71,'085-222-4411','COPD exacerbation','เขตปทุมวัน กรุงเทพฯ','ICU','โรงพยาบาลจุฬาลงกรณ์ สภากาชาดไทย','ICU เตียง 7','-',1),
('HN0006','123456','กมลชนก รัตนสุข','หญิง',29,'086-333-5566','Pregnancy with GDM','อ.น้ำพอง จ.ขอนแก่น','OPD','โรงพยาบาลขอนแก่น','ANC Clinic','-',0),
('HN0007','123456','ธนกร เพชรดี','ชาย',55,'087-666-1200','NSTEMI post PCI','อ.เมือง จ.ขอนแก่น','IPD','โรงพยาบาลศรีนครินทร์','CCU Stepdown','-',1),
('HN0008','123456','รัชนี พูลผล','หญิง',49,'088-989-3311','Breast mass workup','กรุงเทพมหานคร','คิวผ่าตัด','โรงพยาบาลจุฬาลงกรณ์ สภากาชาดไทย','Surgical Queue','รอคิวผ่าตัด',0),
('HN0009','123456','ศุภชัย คำดี','ชาย',33,'089-100-2000','Trauma observation','เขตลาดกระบัง กรุงเทพฯ','คนไข้เฝ้าระวังสูง','โรงพยาบาลพระจอมเกล้าเจ้าคุณทหาร','Observation Unit','รอ CT',1),
('HN0010','123456','มลฤดี ศรีสุข','หญิง',61,'080-444-8181','Stroke rehabilitation','จ.ขอนแก่น','IPD','โรงพยาบาลขอนแก่น','Rehab Ward','-',0)
ON CONFLICT (hn) DO UPDATE SET
password=EXCLUDED.password, full_name=EXCLUDED.full_name, gender=EXCLUDED.gender, age=EXCLUDED.age, phone=EXCLUDED.phone, disease=EXCLUDED.disease, address=EXCLUDED.address, care_area=EXCLUDED.care_area, hospital=EXCLUDED.hospital, ward=EXCLUDED.ward, surgery_status=EXCLUDED.surgery_status, high_watch=EXCLUDED.high_watch;

INSERT INTO doctors (username, password, full_name, license_no, department, hospital)
VALUES
('doctor1','123456','นพ.กิตติ ภัทรเวช','MD-1026588','อายุรกรรม','โรงพยาบาลขอนแก่น'),
('doctor2','123456','พญ.ณิชา ศรีแพทย์','MD-2047712','ศัลยกรรมทั่วไป','โรงพยาบาลศรีนครินทร์'),
('doctor3','123456','นพ.ธนดล วัฒนกุล','MD-3091188','เวชบำบัดวิกฤต','โรงพยาบาลพระจอมเกล้าเจ้าคุณทหาร')
ON CONFLICT (username) DO UPDATE SET
password=EXCLUDED.password, full_name=EXCLUDED.full_name, license_no=EXCLUDED.license_no, department=EXCLUDED.department, hospital=EXCLUDED.hospital;

INSERT INTO admin_users (username, password, full_name)
VALUES ('admin', 'admin123', 'USE MED Admin')
ON CONFLICT (username) DO UPDATE SET password=EXCLUDED.password, full_name=EXCLUDED.full_name;

INSERT INTO visits (patient_id, doctor_id, visit_date, title, diagnosis, treatment_plan, systolic, diastolic, pulse, glucose, hba1c, bmi, cholesterol, visit_type, visit_reason, care_area, hospital, payment_method, insurance_detail, blood_group, weight_kg, height_cm, temperature, respiratory_rate, oxygen_saturation, alcohol_use, smoking_status, has_surgery, surgery_type, surgery_note, has_menstruation, last_menstrual_period, investigations, lab_results, urine_results, xray_results, mri_results, imaging_results, doctor_education, next_appointment_detail, followup_date, risk_score, risk_level)
SELECT p.id, d.id, '2026-05-27', 'ตรวจติดตาม / ประเมินอาการล่าสุด', p.disease, CONCAT('สถานะ ', p.care_area, ' แผนก ', COALESCE(p.ward, '-')), 148, 92, 78, 142.00, 7.80, 27.40, 218.00, 'OPD', 'ติดตามอาการและประเมินผลการรักษาล่าสุด', p.care_area, p.hospital, 'บัตร 30 บาท / UC', 'สิทธิหลักประกันสุขภาพแห่งชาติ', 'O+', 68.00, 165.00, 36.8, 18, 98, 'ไม่ดื่ม', 'ไม่สูบ', 'ไม่มี', '-', '-', CASE WHEN p.gender='หญิง' THEN 'มี/สอบถามแล้ว' ELSE 'ไม่เกี่ยวข้อง' END, CASE WHEN p.gender='หญิง' THEN CAST('2026-05-01' AS DATE) ELSE NULL END, 'ตรวจเลือด, ตรวจปัสสาวะ, X-ray', 'CBC/FBS/HbA1c/Lipid profile', 'Urinalysis', 'X-ray ตามข้อบ่งชี้', '-', 'ผลตรวจภาพถ่ายประกอบการวินิจฉัย', 'ให้ความรู้เรื่องยา อาการผิดปกติ และการมาตามนัด', 'นัดติดตามตามแผนรักษา หากอาการแย่ลงให้มาก่อนนัด', '2026-06-12', CASE WHEN p.high_watch=1 THEN 82 ELSE 58 END, CASE WHEN p.high_watch=1 THEN 'High' ELSE 'Medium' END
FROM patients p LEFT JOIN doctors d ON d.username = 'doctor1'
WHERE NOT EXISTS (SELECT 1 FROM visits v WHERE v.patient_id = p.id AND v.visit_date = '2026-05-27');

INSERT INTO documents (patient_id, visit_id, title, document_type, file_path)
SELECT p.id, v.id, CONCAT('สรุปการรักษา - ', p.hn), 'PDF', NULL
FROM patients p LEFT JOIN visits v ON v.patient_id = p.id
WHERE NOT EXISTS (SELECT 1 FROM documents d WHERE d.patient_id = p.id AND d.title = CONCAT('สรุปการรักษา - ', p.hn));

INSERT INTO documents (patient_id, visit_id, title, document_type, file_path)
SELECT p.id, v.id, 'ผลตรวจเลือด', 'PDF', NULL
FROM patients p LEFT JOIN visits v ON v.patient_id = p.id
WHERE p.hn IN ('HN0001','HN0002','HN0005')
AND NOT EXISTS (SELECT 1 FROM documents d WHERE d.patient_id = p.id AND d.title = 'ผลตรวจเลือด');

INSERT INTO referrals (patient_id, doctor_id, from_department, to_department, to_doctor, to_hospital, urgency, reason, status)
SELECT p.id, d.id, 'อายุรกรรมโรคไต', 'เวชบำบัดวิกฤต', 'นพ.ธนดล วัฒนกุล', 'โรงพยาบาลศรีนครินทร์', 'ด่วน', 'ไตวายเฉียบพลันร่วมกับความดันสูง ต้องประเมิน ICU', 'รอรับเคส'
FROM patients p LEFT JOIN doctors d ON d.username = 'doctor1'
WHERE p.hn = 'HN0002'
AND NOT EXISTS (SELECT 1 FROM referrals r WHERE r.patient_id = p.id AND r.reason = 'ไตวายเฉียบพลันร่วมกับความดันสูง ต้องประเมิน ICU');

INSERT INTO referrals (patient_id, doctor_id, from_department, to_department, to_doctor, to_hospital, urgency, reason, status)
SELECT p.id, d.id, 'ศัลยกรรมทั่วไป', 'ศัลยกรรมทางเดินอาหาร', 'พญ.ณิชา ศรีแพทย์', 'โรงพยาบาลราชวิถี', 'ปกติ', 'ส่งต่อเพื่อวางแผนผ่าตัดถุงน้ำดี', 'นัดหมายแล้ว'
FROM patients p LEFT JOIN doctors d ON d.username = 'doctor2'
WHERE p.hn = 'HN0004'
AND NOT EXISTS (SELECT 1 FROM referrals r WHERE r.patient_id = p.id AND r.reason = 'ส่งต่อเพื่อวางแผนผ่าตัดถุงน้ำดี');

INSERT INTO support_tickets (user_role, user_name, subject, problem_type, menu_path, message, status)
SELECT 'patient', 'สมชาย ใจดี', 'วันนัดไม่ตรงกับเอกสาร', 'ข้อมูลไม่ถูกต้อง', 'patient/portal.php', 'ขอให้ตรวจสอบวันนัดหมายในระบบ', 'open'
WHERE NOT EXISTS (SELECT 1 FROM support_tickets WHERE subject = 'วันนัดไม่ตรงกับเอกสาร');

INSERT INTO support_tickets (user_role, user_name, subject, problem_type, menu_path, message, status)
SELECT 'doctor', 'นพ.กิตติ ภัทรเวช', 'เปิดเอกสาร PDF ไม่ได้', 'เข้าเมนูไม่ได้', 'doctor/documents.php', 'ไม่สามารถเปิดไฟล์เอกสารผู้ป่วยได้', 'closed'
WHERE NOT EXISTS (SELECT 1 FROM support_tickets WHERE subject = 'เปิดเอกสาร PDF ไม่ได้');

COMMIT;

-- Step 13 additions: patient self assessment, prescriptions, EMS handover
CREATE TABLE IF NOT EXISTS patient_self_assessments (
    id SERIAL PRIMARY KEY,
    patient_id INT DEFAULT NULL,
    hn VARCHAR(50) DEFAULT NULL,
    systolic INT DEFAULT NULL,
    diastolic INT DEFAULT NULL,
    fasting_glucose DECIMAL(8,2) DEFAULT NULL,
    hba1c DECIMAL(5,2) DEFAULT NULL,
    weight_kg DECIMAL(6,2) DEFAULT NULL,
    height_cm DECIMAL(6,2) DEFAULT NULL,
    bmi DECIMAL(6,2) DEFAULT NULL,
    symptoms TEXT DEFAULT NULL,
    medication_adherence VARCHAR(80) DEFAULT NULL,
    risk_score INT DEFAULT NULL,
    risk_level VARCHAR(50) DEFAULT NULL,
    advice TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_self_assessment_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS prescriptions (
    id SERIAL PRIMARY KEY,
    patient_id INT DEFAULT NULL,
    doctor_id INT DEFAULT NULL,
    visit_id INT DEFAULT NULL,
    rx_no VARCHAR(80) DEFAULT NULL,
    diagnosis TEXT DEFAULT NULL,
    payment_method VARCHAR(100) DEFAULT NULL,
    note TEXT DEFAULT NULL,
    status VARCHAR(80) DEFAULT 'จ่ายยาแล้ว',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_prescriptions_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL,
    CONSTRAINT fk_prescriptions_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL,
    CONSTRAINT fk_prescriptions_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS prescription_items (
    id SERIAL PRIMARY KEY,
    prescription_id INT NOT NULL,
    medication_name VARCHAR(255) NOT NULL,
    strength VARCHAR(100) DEFAULT NULL,
    dose VARCHAR(255) DEFAULT NULL,
    route VARCHAR(80) DEFAULT NULL,
    frequency VARCHAR(255) DEFAULT NULL,
    duration VARCHAR(120) DEFAULT NULL,
    quantity VARCHAR(80) DEFAULT NULL,
    instruction TEXT DEFAULT NULL,
    CONSTRAINT fk_prescription_items_rx FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ems_cases (
    id SERIAL PRIMARY KEY,
    patient_id INT DEFAULT NULL,
    doctor_id INT DEFAULT NULL,
    case_type VARCHAR(80) DEFAULT 'medical',
    ems_unit VARCHAR(255) DEFAULT NULL,
    arrival_time TIMESTAMP DEFAULT NULL,
    chief_complaint TEXT DEFAULT NULL,
    mechanism TEXT DEFAULT NULL,
    injuries TEXT DEFAULT NULL,
    signs_vitals TEXT DEFAULT NULL,
    treatment_given TEXT DEFAULT NULL,
    sbar_situation TEXT DEFAULT NULL,
    sbar_background TEXT DEFAULT NULL,
    sbar_assessment TEXT DEFAULT NULL,
    sbar_recommendation TEXT DEFAULT NULL,
    height_cm DECIMAL(6,2) DEFAULT NULL,
    weight_kg DECIMAL(6,2) DEFAULT NULL,
    bp VARCHAR(50) DEFAULT NULL,
    pulse INT DEFAULT NULL,
    rr INT DEFAULT NULL,
    spo2 INT DEFAULT NULL,
    temp DECIMAL(4,1) DEFAULT NULL,
    gcs VARCHAR(20) DEFAULT NULL,
    progress_note TEXT DEFAULT NULL,
    status VARCHAR(80) DEFAULT 'รับเคสใหม่',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ems_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL,
    CONSTRAINT fk_ems_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS ai_population_scores (
    id SERIAL PRIMARY KEY,
    patient_id INT DEFAULT NULL,
    hn VARCHAR(50) DEFAULT NULL,
    model_version VARCHAR(80) DEFAULT 'rule-v1',
    risk_score INT NOT NULL DEFAULT 0,
    priority_level VARCHAR(20) NOT NULL DEFAULT 'P3',
    priority_label VARCHAR(120) DEFAULT NULL,
    recommended_sla VARCHAR(120) DEFAULT NULL,
    trajectory_status VARCHAR(120) DEFAULT NULL,
    cohort_tags TEXT DEFAULT NULL,
    feature_snapshot TEXT DEFAULT NULL,
    recommendation_summary TEXT DEFAULT NULL,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ,
    CONSTRAINT uq_ai_population_patient UNIQUE (patient_id),
    CONSTRAINT fk_ai_population_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS ai_population_reasons (
    id SERIAL PRIMARY KEY,
    score_id INT DEFAULT NULL,
    patient_id INT DEFAULT NULL,
    hn VARCHAR(50) DEFAULT NULL,
    reason_type VARCHAR(80) DEFAULT NULL,
    reason_text TEXT NOT NULL,
    source_feature VARCHAR(120) DEFAULT NULL,
    source_value VARCHAR(255) DEFAULT NULL,
    source_table VARCHAR(120) DEFAULT NULL,
    contribution INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ai_reason_score FOREIGN KEY (score_id) REFERENCES ai_population_scores(id) ON DELETE CASCADE,
    CONSTRAINT fk_ai_reason_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS followup_tasks (
    id SERIAL PRIMARY KEY,
    patient_id INT DEFAULT NULL,
    hn VARCHAR(50) DEFAULT NULL,
    priority_level VARCHAR(20) DEFAULT NULL,
    task_type VARCHAR(120) DEFAULT NULL,
    task_title VARCHAR(255) NOT NULL,
    task_detail TEXT DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    assigned_to VARCHAR(255) DEFAULT NULL,
    status VARCHAR(80) DEFAULT 'รอติดตาม',
    source VARCHAR(80) DEFAULT 'AI Population',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ,
    CONSTRAINT fk_followup_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS patient_longitudinal_records (
    id SERIAL PRIMARY KEY,
    patient_id INT DEFAULT NULL,
    hn VARCHAR(50) DEFAULT NULL,
    period_offset INT NOT NULL,
    systolic INT DEFAULT NULL,
    diastolic INT DEFAULT NULL,
    pulse INT DEFAULT NULL,
    respiratory_rate INT DEFAULT NULL,
    hba1c DECIMAL(5,2) DEFAULT NULL,
    c_peptide DECIMAL(8,2) DEFAULT NULL,
    lipid_ldl DECIMAL(8,2) DEFAULT NULL,
    med_metformin SMALLINT DEFAULT 0,
    med_insulin SMALLINT DEFAULT 0,
    med_glp1 SMALLINT DEFAULT 0,
    med_arb SMALLINT DEFAULT 0,
    med_ccb SMALLINT DEFAULT 0,
    med_acei SMALLINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_longitudinal_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);


CREATE INDEX idx_ai_population_priority ON ai_population_scores (priority_level);
CREATE INDEX idx_ai_population_hn ON ai_population_scores (hn);
CREATE INDEX idx_ai_reason_patient ON ai_population_reasons (patient_id);
CREATE INDEX idx_followup_patient ON followup_tasks (patient_id);
CREATE INDEX idx_followup_status ON followup_tasks (status);
CREATE INDEX idx_longitudinal_patient ON patient_longitudinal_records (patient_id);