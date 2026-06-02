<?php
// backend/shared/ai_engine.php
// USE MED AI utilities: self assessment + population scoring pipeline + reason generator

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/database/connect.php';

function ai_calculate_risk(array $data): array
{
    $score = 0;
    $factors = [];
    $recommendations = [];

    $age = (float) ($data['age'] ?? 0);
    $systolic = (float) ($data['systolic'] ?? 0);
    $diastolic = (float) ($data['diastolic'] ?? 0);
    $glucose = (float) ($data['glucose'] ?? 0);
    $hba1c = (float) ($data['hba1c'] ?? 0);
    $bmi = (float) ($data['bmi'] ?? 0);
    $cholesterol = (float) ($data['cholesterol'] ?? 0);

    if ($age >= 60) {
        $score += 10;
        $factors[] = 'อายุมากกว่า 60 ปี';
    }

    if ($systolic >= 160 || $diastolic >= 100) {
        $score += 25;
        $factors[] = 'ความดันโลหิตสูงมาก';
        $recommendations[] = 'ควรติดตามความดันอย่างใกล้ชิด';
    } elseif ($systolic >= 140 || $diastolic >= 90) {
        $score += 15;
        $factors[] = 'ความดันโลหิตสูง';
    }

    if ($glucose >= 200) {
        $score += 25;
        $factors[] = 'ระดับน้ำตาลสูงมาก';
        $recommendations[] = 'ควรประเมินการควบคุมเบาหวานและการใช้ยา';
    } elseif ($glucose >= 126) {
        $score += 15;
        $factors[] = 'ระดับน้ำตาลสูง';
    }

    if ($hba1c >= 9) {
        $score += 25;
        $factors[] = 'HbA1c สูงมาก';
        $recommendations[] = 'ควรปรับแผนการรักษาและติดตามในระยะสั้น';
    } elseif ($hba1c >= 7) {
        $score += 15;
        $factors[] = 'HbA1c สูงกว่าค่าเป้าหมาย';
    }

    if ($bmi >= 30) {
        $score += 15;
        $factors[] = 'BMI อยู่ในกลุ่มอ้วน';
        $recommendations[] = 'แนะนำควบคุมน้ำหนัก อาหาร และออกกำลังกาย';
    } elseif ($bmi >= 25) {
        $score += 8;
        $factors[] = 'BMI อยู่ในกลุ่มน้ำหนักเกิน';
    }

    if ($cholesterol >= 240) {
        $score += 10;
        $factors[] = 'ไขมันในเลือดสูง';
    }

    $score = max(0, min(100, $score));

    if ($score >= 70) {
        $level = 'High';
        $level_th = 'สูง';
        $color = 'red';
        $summary = 'ผู้ป่วยมีความเสี่ยงสูง ควรติดตามอย่างใกล้ชิด';
    } elseif ($score >= 40) {
        $level = 'Medium';
        $level_th = 'ปานกลาง';
        $color = 'orange';
        $summary = 'ผู้ป่วยมีความเสี่ยงปานกลาง ควรติดตามต่อเนื่อง';
    } else {
        $level = 'Low';
        $level_th = 'ต่ำ';
        $color = 'green';
        $summary = 'ผู้ป่วยมีความเสี่ยงต่ำ แต่ควรดูแลสุขภาพต่อเนื่อง';
    }

    if (empty($factors)) {
        $factors[] = 'ไม่พบปัจจัยเสี่ยงเด่นจากข้อมูลที่กรอก';
    }

    if (empty($recommendations)) {
        $recommendations[] = 'ติดตามผลตามนัด';
        $recommendations[] = 'ควบคุมอาหาร ออกกำลังกาย และรับประทานยาตามแพทย์สั่ง';
    }

    return [
        'score' => $score,
        'level' => $level,
        'level_th' => $level_th,
        'color' => $color,
        'summary' => $summary,
        'factors' => $factors,
        'recommendations' => $recommendations,
    ];
}

function ai_demo_result(): array
{
    return ai_calculate_risk([
        'age' => 58,
        'systolic' => 148,
        'diastolic' => 92,
        'glucose' => 142,
        'hba1c' => 7.8,
        'bmi' => 27.4,
        'cholesterol' => 218,
    ]);
}

function usemed_ai_ensure_population_schema(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $runMaintenance = function_exists('envv') ? (string) envv('USEMED_AUTO_SCHEMA', '0') : '0';
    $manualRun = isset($_GET['sync_schema']) && (string) $_GET['sync_schema'] === '1';
    if (!$manualRun && in_array(strtolower($runMaintenance), ['0', 'false', 'off', 'no'], true)) {
        $checked = true;
        return;
    }

    $lockFile = sys_get_temp_dir() . '/usemed_ai_schema_done.lock';
    if (file_exists($lockFile)) {
        $checked = true;
        return;
    }

    if (!db_is_connected()) {
        return;
    }

    db_execute("CREATE TABLE IF NOT EXISTS ai_population_scores (
        id SERIAL PRIMARY KEY,
        patient_id INT DEFAULT NULL REFERENCES patients(id) ON DELETE SET NULL,
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
        updated_at TIMESTAMP DEFAULT NULL,
        UNIQUE (patient_id)
    )");
    db_execute("CREATE INDEX IF NOT EXISTS idx_ai_population_priority ON ai_population_scores (priority_level)");
    db_execute("CREATE INDEX IF NOT EXISTS idx_ai_population_hn ON ai_population_scores (hn)");

    db_execute("CREATE TABLE IF NOT EXISTS ai_population_reasons (
        id SERIAL PRIMARY KEY,
        score_id INT DEFAULT NULL REFERENCES ai_population_scores(id) ON DELETE CASCADE,
        patient_id INT DEFAULT NULL REFERENCES patients(id) ON DELETE SET NULL,
        hn VARCHAR(50) DEFAULT NULL,
        reason_type VARCHAR(80) DEFAULT NULL,
        reason_text TEXT NOT NULL,
        source_feature VARCHAR(120) DEFAULT NULL,
        source_value VARCHAR(255) DEFAULT NULL,
        source_table VARCHAR(120) DEFAULT NULL,
        contribution INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    db_execute("CREATE INDEX IF NOT EXISTS idx_ai_reason_patient ON ai_population_reasons (patient_id)");

    db_execute("CREATE TABLE IF NOT EXISTS followup_tasks (
        id SERIAL PRIMARY KEY,
        patient_id INT DEFAULT NULL REFERENCES patients(id) ON DELETE SET NULL,
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
        updated_at TIMESTAMP DEFAULT NULL
    )");
    db_execute("CREATE INDEX IF NOT EXISTS idx_followup_patient ON followup_tasks (patient_id)");
    db_execute("CREATE INDEX IF NOT EXISTS idx_followup_status ON followup_tasks (status)");
    @file_put_contents($lockFile, '1');
    $checked = true;
}

function usemed_ai_float($value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }
    if (is_numeric($value)) {
        return (float) $value;
    }
    return null;
}

function usemed_ai_latest_visit(array $patient): ?array
{
    if (!db_is_connected() || empty($patient['id'])) {
        return null;
    }

    return db_fetch_one(
        'SELECT * FROM visits WHERE patient_id = :patient_id ORDER BY visit_date DESC, id DESC LIMIT 1',
        ['patient_id' => (int) $patient['id']]
    );
}

function usemed_ai_recent_visits(array $patient, int $limit = 5): array
{
    if (!db_is_connected() || empty($patient['id'])) {
        return [];
    }

    return db_fetch_all(
        'SELECT * FROM visits WHERE patient_id = :patient_id ORDER BY visit_date DESC, id DESC LIMIT ' . max(1, min(12, $limit)),
        ['patient_id' => (int) $patient['id']]
    );
}

function usemed_ai_latest_self_assessment(array $patient): ?array
{
    if (!db_is_connected()) {
        return null;
    }

    if (!empty($patient['id'])) {
        $row = db_fetch_one(
            'SELECT * FROM patient_self_assessments WHERE patient_id = :patient_id ORDER BY created_at DESC, id DESC LIMIT 1',
            ['patient_id' => (int) $patient['id']]
        );
        if ($row) {
            return $row;
        }
    }

    if (!empty($patient['hn'])) {
        return db_fetch_one(
            'SELECT * FROM patient_self_assessments WHERE hn = :hn ORDER BY created_at DESC, id DESC LIMIT 1',
            ['hn' => (string) $patient['hn']]
        );
    }

    return null;
}

function usemed_ai_count_recent_visits(array $patient, int $days = 180): int
{
    if (!db_is_connected() || empty($patient['id'])) {
        return 0;
    }

    $row = db_fetch_one(
        'SELECT COUNT(*) AS c FROM visits WHERE patient_id = :patient_id AND visit_date >= CURRENT_DATE - INTERVAL \'' . max(1, $days) . ' days\'',
        ['patient_id' => (int) $patient['id']]
    );

    return (int) ($row['c'] ?? 0);
}

function usemed_ai_extract_features(array $patient): array
{
    $latestVisit = usemed_ai_latest_visit($patient) ?: [];
    $recentVisits = usemed_ai_recent_visits($patient, 5);
    $self = usemed_ai_latest_self_assessment($patient) ?: [];

    $sbp = usemed_ai_float($latestVisit['systolic'] ?? null);
    $dbp = usemed_ai_float($latestVisit['diastolic'] ?? null);
    if ($sbp === null) { $sbp = usemed_ai_float($self['systolic'] ?? null); }
    if ($dbp === null) { $dbp = usemed_ai_float($self['diastolic'] ?? null); }

    $glucose = usemed_ai_float($latestVisit['glucose'] ?? null);
    if ($glucose === null) { $glucose = usemed_ai_float($self['fasting_glucose'] ?? null); }

    $hba1c = usemed_ai_float($latestVisit['hba1c'] ?? null);
    if ($hba1c === null) { $hba1c = usemed_ai_float($self['hba1c'] ?? null); }

    $bmi = usemed_ai_float($latestVisit['bmi'] ?? null);
    if ($bmi === null) { $bmi = usemed_ai_float($self['bmi'] ?? null); }
    if ($bmi === null) { $bmi = usemed_ai_float($patient['bmi'] ?? null); }

    $weight = usemed_ai_float($latestVisit['weight_kg'] ?? null);
    if ($weight === null) { $weight = usemed_ai_float($self['weight_kg'] ?? null); }
    $height = usemed_ai_float($latestVisit['height_cm'] ?? null);
    if ($height === null) { $height = usemed_ai_float($self['height_cm'] ?? null); }
    if ($bmi === null && $weight && $height) {
        $bmi = round($weight / (($height / 100) ** 2), 1);
    }

    $bpTrendHighCount = 0;
    $hba1cTrend = [];
    foreach ($recentVisits as $visit) {
        $vSbp = usemed_ai_float($visit['systolic'] ?? null);
        $vDbp = usemed_ai_float($visit['diastolic'] ?? null);
        if (($vSbp !== null && $vSbp >= 140) || ($vDbp !== null && $vDbp >= 90)) {
            $bpTrendHighCount++;
        }
        $vA1c = usemed_ai_float($visit['hba1c'] ?? null);
        if ($vA1c !== null) {
            $hba1cTrend[] = $vA1c;
        }
    }

    $admissionDate = (string) ($patient['admission_date'] ?? '');
    $losDays = null;
    if ($admissionDate !== '') {
        try {
            $start = new DateTime($admissionDate);
            $losDays = (int) $start->diff(new DateTime('today'))->format('%a');
        } catch (Throwable $e) {
            $losDays = null;
        }
    }

    $disease = mb_strtolower((string) ($patient['disease'] ?? ''), 'UTF-8');
    $area = (string) ($patient['care_area'] ?? 'OPD');
    $latestVisitDate = (string) ($latestVisit['visit_date'] ?? '');
    $selfDate = (string) ($self['created_at'] ?? '');

    return [
        'age' => (int) ($patient['age'] ?? 0),
        'gender' => (string) ($patient['gender'] ?? ''),
        'disease_text' => (string) ($patient['disease'] ?? ''),
        'disease_key' => $disease,
        'care_area' => $area,
        'high_watch' => !empty($patient['high_watch']),
        'patient_risk_score' => usemed_ai_float($patient['risk_score'] ?? null),
        'systolic' => $sbp,
        'diastolic' => $dbp,
        'glucose' => $glucose,
        'hba1c' => $hba1c,
        'bmi' => $bmi,
        'weight_kg' => $weight,
        'height_cm' => $height,
        'bp_high_recent_count' => $bpTrendHighCount,
        'hba1c_trend' => $hba1cTrend,
        'recent_visit_count' => count($recentVisits),
        'recent_visit_180d' => usemed_ai_count_recent_visits($patient, 180),
        'latest_visit_date' => $latestVisitDate,
        'latest_self_assessment_date' => $selfDate,
        'medication_adherence' => (string) ($self['medication_adherence'] ?? ''),
        'symptoms' => (string) ($self['symptoms'] ?? ''),
        'smoking_status' => (string) ($latestVisit['smoking_status'] ?? ''),
        'alcohol_use' => (string) ($latestVisit['alcohol_use'] ?? ''),
        'additional_medication' => (string) ($patient['additional_medication'] ?? ''),
        'admission_date' => $admissionDate,
        'los_days' => $losDays,
        'expected_discharge_date' => (string) ($patient['expected_discharge_date'] ?? ''),
        'icu_day' => (string) ($patient['icu_day'] ?? ''),
        'ventilator_status' => (string) ($patient['ventilator_status'] ?? ''),
        'operation_status' => (string) ($patient['operation_status'] ?? $patient['surgery_status'] ?? ''),
        'operation_size' => (string) ($patient['operation_size'] ?? ''),
        'source' => [
            'latest_visit_id' => $latestVisit['id'] ?? null,
            'latest_visit_date' => $latestVisitDate,
            'self_assessment_id' => $self['id'] ?? null,
            'self_assessment_date' => $selfDate,
        ],
    ];
}

function usemed_ai_add_reason(array &$reasons, string $type, string $text, string $feature, $value, int $weight, string $sourceTable = ''): void
{
    $reasons[] = [
        'type' => $type,
        'text' => $text,
        'source_feature' => $feature,
        'source_value' => ($value === null || $value === '') ? '-' : (string) $value,
        'source_table' => $sourceTable,
        'weight' => $weight,
    ];
}

function usemed_ai_priority_from_score(int $score, array $features): array
{
    $area = (string) ($features['care_area'] ?? 'OPD');
    if ($score >= 80 || $area === 'ICU' || !empty($features['high_watch'])) {
        return ['priority' => 'P1', 'level' => 'ต้องดูวันนี้', 'sla' => 'ภายในวันนี้', 'badge' => 'red'];
    }
    if ($score >= 65 || $area === 'IPD') {
        return ['priority' => 'P2', 'level' => 'ติดตามก่อน', 'sla' => 'ภายใน 3 วัน', 'badge' => 'orange'];
    }
    return ['priority' => 'P3', 'level' => 'ติดตามตามรอบ', 'sla' => 'ตามนัด/ภายใน 1–4 สัปดาห์', 'badge' => 'green'];
}

function usemed_ai_cohort_tags(array $features): array
{
    $tags = [];
    $disease = (string) ($features['disease_key'] ?? '');
    $area = (string) ($features['care_area'] ?? 'OPD');

    if (str_contains($disease, 'diabetes') || str_contains($disease, 'gdm') || str_contains($disease, 'เบาหวาน')) { $tags[] = 'เบาหวาน'; }
    if (str_contains($disease, 'hypertension') || str_contains($disease, 'ความดัน')) { $tags[] = 'ความดัน'; }
    if (str_contains($disease, 'ckd') || str_contains($disease, 'ไต')) { $tags[] = 'ไต'; }
    if ((int) ($features['age'] ?? 0) >= 60) { $tags[] = 'อายุ 60+'; }
    if ((float) ($features['bmi'] ?? 0) >= 25) { $tags[] = 'BMI สูง'; }
    if ($area !== '') { $tags[] = $area; }
    if (!empty($features['high_watch'])) { $tags[] = 'เฝ้าระวังสูง'; }

    return array_values(array_unique($tags));
}

function usemed_ai_trajectory_status(array $features, int $score): string
{
    $trend = (array) ($features['hba1c_trend'] ?? []);
    $bpHighCount = (int) ($features['bp_high_recent_count'] ?? 0);
    $adherence = mb_strtolower((string) ($features['medication_adherence'] ?? ''), 'UTF-8');

    if (count($trend) >= 2 && $trend[0] > end($trend)) {
        return 'แย่ลงจาก HbA1c ล่าสุดสูงกว่าครั้งก่อน';
    }
    if ($bpHighCount >= 3) {
        return 'ควบคุมความดันไม่สม่ำเสมอจาก visits ล่าสุด';
    }
    if (str_contains($adherence, 'ลืม') || str_contains($adherence, 'ไม่')) {
        return 'เสี่ยงจาก medication adherence ต่ำ';
    }
    if ($score >= 80) {
        return 'อยู่ในระดับต้องเร่งติดตาม';
    }
    if ($score >= 65) {
        return 'เสี่ยงปานกลางถึงสูง ควรติดตามระยะสั้น';
    }
    return 'คงที่/ติดตามตามรอบ';
}

function usemed_ai_call_ml_service(array $patient, array $features): ?array
{
    static $temporarilyUnavailable = false;

    if ($temporarilyUnavailable || !function_exists('curl_init')) {
        return null;
    }

    $disabled = function_exists('envv') ? (string) envv('USEMED_ML_ENABLED', '1') : '1';
    if (in_array(strtolower($disabled), ['0', 'false', 'off', 'no'], true)) {
        return null;
    }

    // Mapping features to the Python ML API format
    $payload = [
        'hn' => (string) ($patient['hn'] ?? 'Unknown'),
        'age' => (int) ($features['age'] ?? 0),
        'gender' => (string) ($features['gender'] ?? 'Unknown'),
        'disease_type' => (string) ($features['disease_text'] ?? 'Unknown'),
        'systolic' => $features['systolic'],
        'diastolic' => $features['diastolic'],
        'hba1c' => $features['hba1c'],
        'c_peptide' => null,
        'history_systolic' => [], // Would be populated from patient_longitudinal_records
        'history_hba1c' => $features['hba1c_trend'] ?? [],
        'med_metformin' => strpos(strtolower($features['additional_medication'] ?? ''), 'metformin') !== false ? 1 : 0,
        'med_insulin' => strpos(strtolower($features['additional_medication'] ?? ''), 'insulin') !== false ? 1 : 0,
        'med_ccb' => strpos(strtolower($features['additional_medication'] ?? ''), 'amlodipine') !== false ? 1 : 0,
        'med_arb' => strpos(strtolower($features['additional_medication'] ?? ''), 'losartan') !== false ? 1 : 0,
        'med_acei' => strpos(strtolower($features['additional_medication'] ?? ''), 'enalapril') !== false ? 1 : 0,
    ];

    $url = function_exists('envv') ? (string) envv('USEMED_ML_URL', 'http://127.0.0.1:8000/predict') : 'http://127.0.0.1:8000/predict';
    $ch = curl_init($url);
    if ($ch === false) {
        $temporarilyUnavailable = true;
        return null;
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_NOSIGNAL, true);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 450);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 150);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_errno($ch);
    curl_close($ch);

    if ($curlError !== 0 || $httpCode < 200 || $httpCode >= 300) {
        $temporarilyUnavailable = true;
        return null;
    }

    if ($response) {
        $decoded = json_decode($response, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    return null;
}

function usemed_ai_score_patient(array $patient, bool $persist = true): array
{
    usemed_ai_ensure_population_schema();

    $features = usemed_ai_extract_features($patient);
    $score = 20;
    $reasons = [];
    $actions = [];

    $age = (int) ($features['age'] ?? 0);
    $area = (string) ($features['care_area'] ?? 'OPD');
    $disease = (string) ($features['disease_key'] ?? '');

    if ($age >= 75) { $score += 18; usemed_ai_add_reason($reasons, 'demographic', 'อายุ ≥ 75 ปี เพิ่มความเสี่ยงต่อภาวะแทรกซ้อนและ readmission', 'age', $age, 18, 'patients'); }
    elseif ($age >= 60) { $score += 10; usemed_ai_add_reason($reasons, 'demographic', 'อายุ ≥ 60 ปี ต้องติดตามโรคเรื้อรังใกล้ชิดขึ้น', 'age', $age, 10, 'patients'); }

    if (str_contains($disease, 'diabetes') || str_contains($disease, 'gdm') || str_contains($disease, 'เบาหวาน')) {
        $score += 10;
        usemed_ai_add_reason($reasons, 'condition', 'มีโรคเบาหวาน/ภาวะน้ำตาลสูง ต้องติดตาม HbA1c, FBS และ adherence', 'disease', $features['disease_text'], 10, 'patients');
        $actions[] = 'จัดเข้ากลุ่ม NCD เบาหวาน และทบทวนอาหาร/ยา/การตรวจ HbA1c';
    }
    if (str_contains($disease, 'hypertension') || str_contains($disease, 'ความดัน')) {
        $score += 10;
        usemed_ai_add_reason($reasons, 'condition', 'มีโรคความดัน ควรติดตาม BP trend และการกินยา', 'disease', $features['disease_text'], 10, 'patients');
        $actions[] = 'ติดตาม BP ที่บ้าน/คลินิก และทบทวนยา antihypertensive';
    }
    if (str_contains($disease, 'ckd') || str_contains($disease, 'ไต')) {
        $score += 12;
        usemed_ai_add_reason($reasons, 'condition', 'มีโรคไต/CKD ต้องติดตาม Cr/eGFR, urine และยา nephrotoxic', 'disease', $features['disease_text'], 12, 'patients');
        $actions[] = 'นัดตรวจ renal function และทบทวนยาที่กระทบไต';
    }
    if (str_contains($disease, 'stroke') || str_contains($disease, 'nstemi') || str_contains($disease, 'copd')) {
        $score += 14;
        usemed_ai_add_reason($reasons, 'condition', 'มีโรคสำคัญที่เสี่ยง readmission/acute deterioration', 'disease', $features['disease_text'], 14, 'patients');
    }

    $sbp = $features['systolic'];
    $dbp = $features['diastolic'];
    if ($sbp !== null || $dbp !== null) {
        $bpText = ($sbp !== null ? (int) $sbp : '-') . '/' . ($dbp !== null ? (int) $dbp : '-') . ' mmHg';
        if (($sbp !== null && $sbp >= 180) || ($dbp !== null && $dbp >= 120)) {
            $score += 24;
            usemed_ai_add_reason($reasons, 'vital', 'ความดันล่าสุดอยู่ในช่วงสูงมาก ต้อง review เร่งด่วน', 'BP', $bpText, 24, ($features['source']['latest_visit_id'] ?? null) ? 'visits' : 'patient_self_assessments');
            $actions[] = 'ตรวจซ้ำ/ประเมินอาการ danger signs และพิจารณาเร่งพบแพทย์';
        } elseif (($sbp !== null && $sbp >= 160) || ($dbp !== null && $dbp >= 100)) {
            $score += 18;
            usemed_ai_add_reason($reasons, 'vital', 'ความดันล่าสุดสูง ควรติดตามระยะสั้น', 'BP', $bpText, 18, ($features['source']['latest_visit_id'] ?? null) ? 'visits' : 'patient_self_assessments');
            $actions[] = 'นัดติดตาม BP ภายใน 1–2 สัปดาห์ หรือปรับแผนยา';
        } elseif (($sbp !== null && $sbp >= 140) || ($dbp !== null && $dbp >= 90)) {
            $score += 10;
            usemed_ai_add_reason($reasons, 'vital', 'ความดันล่าสุดสูงกว่าเป้าหมาย', 'BP', $bpText, 10, ($features['source']['latest_visit_id'] ?? null) ? 'visits' : 'patient_self_assessments');
        }
    }

    if ((int) ($features['bp_high_recent_count'] ?? 0) >= 3) {
        $score += 10;
        usemed_ai_add_reason($reasons, 'trajectory', 'ความดันสูงซ้ำใน visits ล่าสุด ≥ 3 ครั้ง สะท้อน trend คุม BP ไม่ดี', 'bp_high_recent_count', $features['bp_high_recent_count'], 10, 'visits');
    }

    $glucose = $features['glucose'];
    if ($glucose !== null) {
        if ($glucose >= 250) {
            $score += 22;
            usemed_ai_add_reason($reasons, 'lab', 'น้ำตาลล่าสุดสูงมาก เสี่ยง acute complication/คุมเบาหวานไม่ได้', 'glucose', $glucose, 22, ($features['source']['latest_visit_id'] ?? null) ? 'visits' : 'patient_self_assessments');
            $actions[] = 'ประเมินอาการน้ำตาลสูง ทบทวนยา และตรวจ glucose ซ้ำ';
        } elseif ($glucose >= 180) {
            $score += 14;
            usemed_ai_add_reason($reasons, 'lab', 'น้ำตาลล่าสุดสูง ควรปรับแผนติดตามเบาหวาน', 'glucose', $glucose, 14, ($features['source']['latest_visit_id'] ?? null) ? 'visits' : 'patient_self_assessments');
        } elseif ($glucose >= 126) {
            $score += 8;
            usemed_ai_add_reason($reasons, 'lab', 'น้ำตาลสูงกว่าช่วงเป้าหมาย', 'glucose', $glucose, 8, ($features['source']['latest_visit_id'] ?? null) ? 'visits' : 'patient_self_assessments');
        }
    }

    $hba1c = $features['hba1c'];
    if ($hba1c !== null) {
        if ($hba1c >= 9) {
            $score += 22;
            usemed_ai_add_reason($reasons, 'lab', 'HbA1c ล่าสุด ≥ 9% สะท้อนการควบคุมเบาหวานระยะยาวไม่ดี', 'HbA1c', $hba1c . '%', 22, ($features['source']['latest_visit_id'] ?? null) ? 'visits' : 'patient_self_assessments');
            $actions[] = 'ควรนัด NCD/เภสัช/โภชนาการเพื่อทบทวนยาและ adherence';
        } elseif ($hba1c >= 7) {
            $score += 12;
            usemed_ai_add_reason($reasons, 'lab', 'HbA1c สูงกว่าเป้าหมายทั่วไป ควรติดตามต่อเนื่อง', 'HbA1c', $hba1c . '%', 12, ($features['source']['latest_visit_id'] ?? null) ? 'visits' : 'patient_self_assessments');
        }
    }

    $bmi = $features['bmi'];
    if ($bmi !== null) {
        if ($bmi >= 30) {
            $score += 12;
            usemed_ai_add_reason($reasons, 'behavior', 'BMI อยู่ในกลุ่มอ้วน เพิ่มภาระโรคเบาหวาน/ความดัน', 'BMI', $bmi, 12, 'visits/self_assessment');
            $actions[] = 'ให้คำแนะนำ weight management และติดตาม BMI';
        } elseif ($bmi >= 25) {
            $score += 6;
            usemed_ai_add_reason($reasons, 'behavior', 'BMI อยู่ในกลุ่มน้ำหนักเกิน', 'BMI', $bmi, 6, 'visits/self_assessment');
        }
    }

    $adherence = mb_strtolower((string) ($features['medication_adherence'] ?? ''), 'UTF-8');
    if ($adherence !== '' && (str_contains($adherence, 'ลืม') || str_contains($adherence, 'ไม่') || str_contains($adherence, 'miss'))) {
        $score += 12;
        usemed_ai_add_reason($reasons, 'adherence', 'ข้อมูล self-assessment ระบุว่าการกินยาไม่สม่ำเสมอ', 'medication_adherence', $features['medication_adherence'], 12, 'patient_self_assessments');
        $actions[] = 'โทรติดตาม adherence และปรับแผนการกินยาให้เหมาะกับผู้ป่วย';
    }

    if ($area === 'ICU') {
        $score += 24;
        usemed_ai_add_reason($reasons, 'care_area', 'อยู่ ICU ต้องติดตาม daily note, ventilator, I/O และ line/tube ทุกวัน', 'care_area', $area, 24, 'patients');
        $actions[] = 'review ICU daily note, ventilator/vasopressor และ line/tube status วันนี้';
    } elseif ($area === 'IPD') {
        $score += 14;
        usemed_ai_add_reason($reasons, 'care_area', 'กำลังนอนโรงพยาบาล ต้องดู LOS, discharge plan และ medication change', 'care_area', $area, 14, 'patients');
        $actions[] = 'ตรวจ discharge plan, expected discharge และคำสั่งยาเพิ่ม';
    } elseif (str_contains($area, 'ผ่าตัด')) {
        $score += 10;
        usemed_ai_add_reason($reasons, 'care_area', 'มีบริบทผ่าตัด/คิวผ่าตัด ต้องดู OR plan และ post-op risk', 'care_area', $area, 10, 'patients');
    }

    if (!empty($features['high_watch'])) {
        $score += 18;
        usemed_ai_add_reason($reasons, 'flag', 'ถูก mark เป็นคนไข้เฝ้าระวังสูงในระบบ', 'high_watch', '1', 18, 'patients');
        $actions[] = 'ดึงเข้าคิว review ก่อน และกำหนดเจ้าของเคส';
    }

    if (($features['los_days'] ?? null) !== null && (int) $features['los_days'] >= 7) {
        $score += 8;
        usemed_ai_add_reason($reasons, 'utilization', 'นอนโรงพยาบาล ≥ 7 วัน ควรทบทวน discharge barrier', 'length_of_stay', $features['los_days'] . ' วัน', 8, 'patients');
    }

    if ((string) ($features['additional_medication'] ?? '') !== '') {
        $score += 5;
        usemed_ai_add_reason($reasons, 'medication', 'มีข้อมูลสั่งยา/ปรับยาเพิ่ม ต้องทบทวน medication reconciliation', 'additional_medication', $features['additional_medication'], 5, 'patients');
    }

    $manualScore = $features['patient_risk_score'];
    if ($manualScore !== null && $manualScore > 0) {
        $score = (int) round(($score * 0.72) + ($manualScore * 0.28));
        usemed_ai_add_reason($reasons, 'baseline', 'นำ risk score เดิมจากเวชระเบียนมาประกอบการจัดลำดับ', 'patients.risk_score', $manualScore . '/100', 4, 'patients');
    }

    $score = max(0, min(100, $score));
    $priority = usemed_ai_priority_from_score($score, $features);
    $tags = usemed_ai_cohort_tags($features);
    $trajectory = usemed_ai_trajectory_status($features, $score);

    if ($priority['priority'] === 'P1') {
        array_unshift($actions, 'สร้าง follow-up task วันนี้ และมอบหมายผู้รับผิดชอบ');
    } elseif ($priority['priority'] === 'P2') {
        array_unshift($actions, 'นัด/โทรติดตามภายใน 3 วัน พร้อมตรวจข้อมูลล่าสุด');
    } else {
        array_unshift($actions, 'ติดตามตามรอบและส่งความรู้สุขภาพแบบรายบุคคล');
    }

    if (empty($reasons)) {
        usemed_ai_add_reason($reasons, 'default', 'ยังไม่พบสัญญาณเสี่ยงเด่นจากข้อมูลล่าสุด จัดอยู่ในกลุ่มติดตามตามรอบ', 'available_data', 'limited', 0, 'patients/visits');
    }

    // --- ML Service Integration (Phase 3) ---
    $mlResult = usemed_ai_call_ml_service($patient, $features);
    if ($mlResult) {
        $score += (int) ($mlResult['overall_score_modifier'] ?? 0);
        
        if (!empty($mlResult['diabetes_recommendation'])) {
            $actions[] = "🤖 ML Suggestion [Diabetes]: " . $mlResult['diabetes_recommendation'];
        }
        if (!empty($mlResult['hypertension_recommendation'])) {
            $actions[] = "🤖 ML Suggestion [Hypertension]: " . $mlResult['hypertension_recommendation'];
        }
        
        $predStrs = [];
        if (!empty($mlResult['predicted_hba1c_60d'])) $predStrs[] = "HbA1c ~" . $mlResult['predicted_hba1c_60d'] . "%";
        if (!empty($mlResult['predicted_systolic_60d'])) $predStrs[] = "BP ~" . $mlResult['predicted_systolic_60d'] . " mmHg";
        
        if (!empty($predStrs)) {
            $trajectory = "AI Predicts in 60 Days: " . implode(', ', $predStrs);
        }
    }
    // ----------------------------------------

    $result = [
        'score' => $score,
        'priority' => $priority['priority'],
        'level' => $priority['level'],
        'sla' => $priority['sla'],
        'badge' => $priority['badge'],
        'reasons' => $reasons,
        'reason_texts' => array_map(fn($r) => (string) $r['text'], $reasons),
        'actions' => array_values(array_unique($actions)),
        'features' => $features,
        'cohort_tags' => $tags,
        'trajectory_status' => $trajectory,
        'model_version' => 'usemed-pop-health-rule-v1',
    ];

    if ($persist && db_is_connected() && !empty($patient['id'])) {
        usemed_ai_persist_population_score($patient, $result);
    }

    return $result;
}

function usemed_ai_persist_population_score(array $patient, array $result): void
{
    usemed_ai_ensure_population_schema();

    $patientId = (int) ($patient['id'] ?? 0);
    if ($patientId <= 0) {
        return;
    }

    $params = [
        'patient_id' => $patientId,
        'hn' => (string) ($patient['hn'] ?? ''),
        'model_version' => (string) ($result['model_version'] ?? 'rule-v1'),
        'risk_score' => (int) ($result['score'] ?? 0),
        'priority_level' => (string) ($result['priority'] ?? 'P3'),
        'priority_label' => (string) ($result['level'] ?? ''),
        'recommended_sla' => (string) ($result['sla'] ?? ''),
        'trajectory_status' => (string) ($result['trajectory_status'] ?? ''),
        'cohort_tags' => implode(', ', (array) ($result['cohort_tags'] ?? [])),
        'feature_snapshot' => json_encode($result['features'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'recommendation_summary' => implode("\n", (array) ($result['actions'] ?? [])),
    ];

    db_execute(
        'INSERT INTO ai_population_scores
            (patient_id, hn, model_version, risk_score, priority_level, priority_label, recommended_sla, trajectory_status, cohort_tags, feature_snapshot, recommendation_summary, calculated_at)
         VALUES
            (:patient_id, :hn, :model_version, :risk_score, :priority_level, :priority_label, :recommended_sla, :trajectory_status, :cohort_tags, :feature_snapshot, :recommendation_summary, CURRENT_TIMESTAMP)
         ON CONFLICT (patient_id) DO UPDATE SET
            hn = EXCLUDED.hn, model_version = EXCLUDED.model_version, risk_score = EXCLUDED.risk_score, priority_level = EXCLUDED.priority_level,
            priority_label = EXCLUDED.priority_label, recommended_sla = EXCLUDED.recommended_sla, trajectory_status = EXCLUDED.trajectory_status,
            cohort_tags = EXCLUDED.cohort_tags, feature_snapshot = EXCLUDED.feature_snapshot, recommendation_summary = EXCLUDED.recommendation_summary,
            calculated_at = CURRENT_TIMESTAMP',
        $params
    );

    $scoreRow = db_fetch_one('SELECT id FROM ai_population_scores WHERE patient_id = :patient_id LIMIT 1', ['patient_id' => $patientId]);
    $scoreId = (int) ($scoreRow['id'] ?? 0);

    db_execute('DELETE FROM ai_population_reasons WHERE patient_id = :patient_id', ['patient_id' => $patientId]);

    foreach ((array) ($result['reasons'] ?? []) as $reason) {
        db_execute(
            'INSERT INTO ai_population_reasons
                (score_id, patient_id, hn, reason_type, reason_text, source_feature, source_value, source_table, contribution)
             VALUES
                (:score_id, :patient_id, :hn, :reason_type, :reason_text, :source_feature, :source_value, :source_table, :contribution)',
            [
                'score_id' => $scoreId ?: null,
                'patient_id' => $patientId,
                'hn' => (string) ($patient['hn'] ?? ''),
                'reason_type' => (string) ($reason['type'] ?? ''),
                'reason_text' => (string) ($reason['text'] ?? ''),
                'source_feature' => (string) ($reason['source_feature'] ?? ''),
                'source_value' => (string) ($reason['source_value'] ?? ''),
                'source_table' => (string) ($reason['source_table'] ?? ''),
                'contribution' => (int) ($reason['weight'] ?? 0),
            ]
        );
    }

    $priority = (string) ($result['priority'] ?? 'P3');
    if (in_array($priority, ['P1', 'P2'], true)) {
        $existing = db_fetch_one(
            "SELECT id FROM followup_tasks WHERE patient_id = :patient_id AND source = 'AI Population' AND status IN ('รอติดตาม','กำลังติดตาม') LIMIT 1",
            ['patient_id' => $patientId]
        );
        if (!$existing) {
            $days = $priority === 'P1' ? 0 : 3;
            db_execute(
                'INSERT INTO followup_tasks
                    (patient_id, hn, priority_level, task_type, task_title, task_detail, due_date, status, source)
                 VALUES
                    (:patient_id, :hn, :priority_level, :task_type, :task_title, :task_detail, CURRENT_DATE + INTERVAL \'' . $days . ' days\', :status, :source)',
                [
                    'patient_id' => $patientId,
                    'hn' => (string) ($patient['hn'] ?? ''),
                    'priority_level' => $priority,
                    'task_type' => 'Population follow-up',
                    'task_title' => $priority . ' ติดตาม ' . (string) ($patient['full_name'] ?? $patient['hn'] ?? ''),
                    'task_detail' => implode("\n", array_slice((array) ($result['actions'] ?? []), 0, 4)),
                    'status' => 'รอติดตาม',
                    'source' => 'AI Population',
                ]
            );
        }
    }
}

function usemed_ai_run_population(array $patients): array
{
    $items = [];
    foreach ($patients as $patient) {
        $assessment = usemed_ai_score_patient($patient, true);
        $items[] = [
            'patient' => $patient,
            'assessment' => $assessment,
        ];
    }

    usort($items, function (array $a, array $b): int {
        $order = ['P1' => 1, 'P2' => 2, 'P3' => 3];
        $aa = $a['assessment'];
        $bb = $b['assessment'];
        return [
            $order[$aa['priority']] ?? 9,
            -(int) $aa['score'],
            (string) ($a['patient']['hn'] ?? ''),
        ] <=> [
            $order[$bb['priority']] ?? 9,
            -(int) $bb['score'],
            (string) ($b['patient']['hn'] ?? ''),
        ];
    });

    return $items;
}
