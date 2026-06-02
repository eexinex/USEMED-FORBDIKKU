<?php
// public/doctor/add-treatment.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';
require_once __DIR__ . '/../../backend/shared/ai_engine.php';

require_login('doctor');
usemed_ensure_extended_schema();
usemed_seed_demo_data();

$patientOptions = demo_patients();

if (db_is_connected() && function_exists('db_fetch_all')) {
    try {
        $dbPatientOptions = db_fetch_all('SELECT * FROM patients ORDER BY hn ASC, id ASC');
        if (!empty($dbPatientOptions)) {
            $patientOptions = array_map(static function (array $row): array {
                return array_merge([
                    'hn' => '',
                    'full_name' => '',
                    'gender' => '',
                    'age' => '',
                    'disease' => '',
                    'care_area' => 'OPD',
                    'hospital' => '',
                ], $row);
            }, $dbPatientOptions);
        }
    } catch (Throwable $e) {
        // ถ้า database อ่านไม่ได้ ให้กลับไปใช้ demo patients เพื่อให้หน้าไม่พัง
    }
}

$hnDefault = trim((string) ($_POST['hn'] ?? $_GET['hn'] ?? DEMO_PATIENT_HN));
if ($hnDefault === '') {
    $hnDefault = DEMO_PATIENT_HN;
}

$patient = demo_patient($hnDefault);

foreach ($patientOptions as $optionPatient) {
    if (strcasecmp((string) ($optionPatient['hn'] ?? ''), $hnDefault) === 0) {
        $patient = array_merge($patient, $optionPatient);
        break;
    }
}

if (db_is_connected()) {
    $patientRowForForm = db_fetch_one('SELECT * FROM patients WHERE hn = :hn LIMIT 1', ['hn' => $hnDefault]);
    if ($patientRowForForm) {
        $patient = array_merge($patient, $patientRowForForm);
    }
}

$investigationOptions = demo_investigation_options();
$paymentMethods = demo_payment_methods();
$bloodGroups = demo_blood_groups();
$visitTypes = demo_visit_types();
$careAreas = demo_care_areas();
$hospitals = demo_hospitals();
$triageLevels = function_exists('demo_triage_levels') ? demo_triage_levels() : ['ไม่เร่งด่วน','เร่งด่วน','ฉุกเฉิน'];
$dispositions = function_exists('demo_emr_dispositions') ? demo_emr_dispositions() : ['กลับบ้าน','นัดติดตาม','Admit IPD'];
$allergyTypes = function_exists('demo_allergy_types') ? demo_allergy_types() : ['ไม่มีประวัติแพ้','แพ้ยา','แพ้อาหาร','แพ้อื่น ๆ'];

function post_text(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

if (is_post()) {
    $hn = post_text('hn', $hnDefault);
    $visitDate = post_text('visit_date', date('Y-m-d'));
    $title = post_text('title');

    // Encounter / Visit
    $visitType = post_text('visit_type', 'OPD');
    $careArea = post_text('care_area', 'OPD');
    $triageLevel = post_text('triage_level', 'ไม่เร่งด่วน');
    $hospital = post_text('hospital', 'โรงพยาบาลขอนแก่น');
    $admissionWard = post_text('admission_ward');
    $disposition = post_text('disposition', 'นัดติดตาม');
    $visitReason = post_text('visit_reason');
    $chiefComplaint = post_text('chief_complaint', $visitReason);
    $presentIllness = post_text('present_illness');

    // History
    $reviewOfSystems = post_text('review_of_systems');
    $pastHistory = post_text('past_history');
    $pastSurgicalHistory = post_text('past_surgical_history');
    $allergyType = post_text('allergy_type', 'ไม่มีประวัติแพ้');
    $allergyHistory = post_text('allergy_history');
    $currentMedications = post_text('current_medications');
    $familyHistory = post_text('family_history');
    $socialHistory = post_text('social_history');
    $immunizationHistory = post_text('immunization_history');
    $pregnancyStatus = post_text('pregnancy_status', 'ไม่เกี่ยวข้อง');

    // Payment / personal
    $paymentMethod = post_text('payment_method', 'บัตร 30 บาท / UC');
    $insuranceDetail = post_text('insurance_detail');
    $bloodGroup = post_text('blood_group', 'ไม่ทราบ');

    // Objective / vitals
    $weightKg = (float) ($_POST['weight_kg'] ?? 0);
    $heightCm = (float) ($_POST['height_cm'] ?? 0);
    $temperature = (float) ($_POST['temperature'] ?? 0);
    $respiratoryRate = (int) ($_POST['respiratory_rate'] ?? 0);
    $oxygenSaturation = (int) ($_POST['oxygen_saturation'] ?? 0);
    $systolic = (int) ($_POST['systolic'] ?? 0);
    $diastolic = (int) ($_POST['diastolic'] ?? 0);
    $pulse = (int) ($_POST['pulse'] ?? 0);
    $glucose = (float) ($_POST['glucose'] ?? 0);
    $hba1c = (float) ($_POST['hba1c'] ?? 0);
    $bmi = (float) ($_POST['bmi'] ?? 0);
    $cholesterol = (float) ($_POST['cholesterol'] ?? 0);

    if ($bmi <= 0 && $weightKg > 0 && $heightCm > 0) {
        $heightM = $heightCm / 100;
        $bmi = round($weightKg / ($heightM * $heightM), 2);
    }

    $physicalExamGeneral = post_text('physical_exam_general');
    $physicalExamHeent = post_text('physical_exam_heent');
    $physicalExamChestLung = post_text('physical_exam_chest_lung');
    $physicalExamCvs = post_text('physical_exam_cvs');
    $physicalExamAbdomen = post_text('physical_exam_abdomen');
    $physicalExamNeuro = post_text('physical_exam_neuro');
    $physicalExamExtremity = post_text('physical_exam_extremity');
    $physicalExamSkin = post_text('physical_exam_skin');
    $physicalExam = implode("\n", array_filter([
        'General: ' . $physicalExamGeneral,
        'HEENT: ' . $physicalExamHeent,
        'Chest/Lung: ' . $physicalExamChestLung,
        'CVS: ' . $physicalExamCvs,
        'Abdomen: ' . $physicalExamAbdomen,
        'Neuro: ' . $physicalExamNeuro,
        'Extremity: ' . $physicalExamExtremity,
        'Skin: ' . $physicalExamSkin,
    ]));

    // Assessment / Plan
    $diagnosis = post_text('diagnosis');
    $provisionalDiagnosis = post_text('provisional_diagnosis', $diagnosis);
    $differentialDiagnosis = post_text('differential_diagnosis');
    $finalDiagnosis = post_text('final_diagnosis', $diagnosis);
    $icd10Code = post_text('icd10_code');
    $assessment = post_text('assessment', $diagnosis);
    $treatmentPlan = post_text('treatment_plan');
    $medicationOrders = post_text('medication_orders');
    $nursingInstructions = post_text('nursing_instructions');
    $consultRequest = post_text('consult_request');
    $redFlags = post_text('red_flags');
    $consentStatus = post_text('consent_status', 'ยังไม่ต้องใช้ consent');

    // Procedure / surgery / reproductive
    $alcoholUse = post_text('alcohol_use', 'ไม่ดื่ม');
    $smokingStatus = post_text('smoking_status', 'ไม่สูบ');
    $hasSurgery = post_text('has_surgery', 'ไม่มี');
    $surgeryType = post_text('surgery_type', '-');
    $surgeryNote = post_text('surgery_note');
    $procedureName = post_text('procedure_name');
    $procedureNote = post_text('procedure_note');
    $anesthesiaType = post_text('anesthesia_type', '-');
    $hasMenstruation = post_text('has_menstruation', 'ไม่เกี่ยวข้อง');
    $lastMenstrualPeriod = post_text('last_menstrual_period');

    $investigations = $_POST['investigations'] ?? [];
    if (!is_array($investigations)) {
        $investigations = [];
    }
    $investigations = array_values(array_filter(array_map('trim', $investigations)));
    $investigationText = implode(', ', $investigations);

    $labResults = post_text('lab_results');
    $urineResults = post_text('urine_results');
    $xrayResults = post_text('xray_results');
    $mriResults = post_text('mri_results');
    $imagingResults = post_text('imaging_results');
    $doctorEducation = post_text('doctor_education');
    $nextAppointmentDetail = post_text('next_appointment_detail');
    $followupDate = post_text('followup_date');

    if ($hn === '' || $title === '' || $diagnosis === '') {
        flash_set('danger', 'กรุณากรอก HN, หัวข้อการรักษา และวินิจฉัย');
        redirect_to('doctor/add-treatment.php?hn=' . urlencode($hn !== '' ? $hn : $hnDefault));
    }

    $patientForRisk = demo_patient($hn);
    if (db_is_connected()) {
        $patientDbForRisk = db_fetch_one('SELECT * FROM patients WHERE hn = :hn LIMIT 1', ['hn' => $hn]);
        if ($patientDbForRisk) {
            $patientForRisk = array_merge($patientForRisk, $patientDbForRisk);
        }
    }

    $risk = ai_predict_risk_with_ml([
        'age' => (int) ($patientForRisk['age'] ?? 0),
        'systolic' => $systolic,
        'diastolic' => $diastolic,
        'glucose' => $glucose,
        'hba1c' => $hba1c,
        'bmi' => $bmi,
        'cholesterol' => $cholesterol,
    ], $patientForRisk);

    $savedToDb = false;
    $visitId = null;

    if (db_is_connected()) {
        $patientRow = db_fetch_one('SELECT * FROM patients WHERE hn = :hn LIMIT 1', ['hn' => $hn]);

        if (!$patientRow) {
            flash_set('danger', 'ไม่พบผู้ป่วย HN นี้ กรุณาลงทะเบียนผู้ป่วยก่อน');
            redirect_to('doctor/register-patient.php');
        }

        $data = [
            'patient_id' => (int) $patientRow['id'],
            'doctor_id' => (int) (current_user()['id'] ?? 1),
            'visit_date' => $visitDate,
            'title' => $title,
            'diagnosis' => $diagnosis,
            'treatment_plan' => $treatmentPlan,
            'visit_type' => $visitType,
            'visit_reason' => $visitReason,
            'care_area' => $careArea,
            'hospital' => $hospital,
            'payment_method' => $paymentMethod,
            'insurance_detail' => $insuranceDetail,
            'blood_group' => $bloodGroup,
            'weight_kg' => $weightKg ?: null,
            'height_cm' => $heightCm ?: null,
            'temperature' => $temperature ?: null,
            'respiratory_rate' => $respiratoryRate ?: null,
            'oxygen_saturation' => $oxygenSaturation ?: null,
            'systolic' => $systolic ?: null,
            'diastolic' => $diastolic ?: null,
            'pulse' => $pulse ?: null,
            'glucose' => $glucose ?: null,
            'hba1c' => $hba1c ?: null,
            'bmi' => $bmi ?: null,
            'cholesterol' => $cholesterol ?: null,
            'alcohol_use' => $alcoholUse,
            'smoking_status' => $smokingStatus,
            'has_surgery' => $hasSurgery,
            'surgery_type' => $surgeryType,
            'surgery_note' => $surgeryNote,
            'has_menstruation' => $hasMenstruation,
            'last_menstrual_period' => $lastMenstrualPeriod !== '' ? $lastMenstrualPeriod : null,
            'investigations' => $investigationText,
            'lab_results' => $labResults,
            'urine_results' => $urineResults,
            'xray_results' => $xrayResults,
            'mri_results' => $mriResults,
            'imaging_results' => $imagingResults,
            'doctor_education' => $doctorEducation,
            'next_appointment_detail' => $nextAppointmentDetail,
            'followup_date' => $followupDate !== '' ? $followupDate : null,
            'chief_complaint' => $chiefComplaint,
            'present_illness' => $presentIllness,
            'review_of_systems' => $reviewOfSystems,
            'past_history' => $pastHistory,
            'past_surgical_history' => $pastSurgicalHistory,
            'allergy_type' => $allergyType,
            'allergy_history' => $allergyHistory,
            'current_medications' => $currentMedications,
            'family_history' => $familyHistory,
            'social_history' => $socialHistory,
            'immunization_history' => $immunizationHistory,
            'pregnancy_status' => $pregnancyStatus,
            'physical_exam' => $physicalExam,
            'physical_exam_general' => $physicalExamGeneral,
            'physical_exam_heent' => $physicalExamHeent,
            'physical_exam_chest_lung' => $physicalExamChestLung,
            'physical_exam_cvs' => $physicalExamCvs,
            'physical_exam_abdomen' => $physicalExamAbdomen,
            'physical_exam_neuro' => $physicalExamNeuro,
            'physical_exam_extremity' => $physicalExamExtremity,
            'physical_exam_skin' => $physicalExamSkin,
            'provisional_diagnosis' => $provisionalDiagnosis,
            'differential_diagnosis' => $differentialDiagnosis,
            'final_diagnosis' => $finalDiagnosis,
            'icd10_code' => $icd10Code,
            'assessment' => $assessment,
            'procedure_name' => $procedureName,
            'procedure_note' => $procedureNote,
            'anesthesia_type' => $anesthesiaType,
            'medication_orders' => $medicationOrders,
            'nursing_instructions' => $nursingInstructions,
            'consult_request' => $consultRequest,
            'disposition' => $disposition,
            'admission_ward' => $admissionWard,
            'triage_level' => $triageLevel,
            'red_flags' => $redFlags,
            'consent_status' => $consentStatus,
            'risk_score' => (int) $risk['score'],
            'risk_level' => $risk['level'],
        ];

        $savedToDb = usemed_insert_available('visits', $data);
        if ($savedToDb) {
            $visitId = (int) db_last_id();

            db_execute(
                'UPDATE patients SET care_area = :care_area, hospital = :hospital, blood_group = :blood_group, payment_method = :payment_method, insurance_detail = :insurance_detail, high_watch = :high_watch WHERE id = :id',
                [
                    'care_area' => $careArea,
                    'hospital' => $hospital,
                    'blood_group' => $bloodGroup,
                    'payment_method' => $paymentMethod,
                    'insurance_detail' => $insuranceDetail,
                    'high_watch' => ($careArea === 'ICU' || $careArea === 'คนไข้เฝ้าระวังสูง' || $triageLevel === 'วิกฤต' || $risk['level'] === 'High') ? 1 : 0,
                    'id' => (int) $patientRow['id'],
                ]
            );

            $docMap = [
                'CBC' => $labResults, 'Blood chemistry' => $labResults, 'HbA1c / Lipid' => $labResults,
                'Coagulation' => $labResults, 'Cardiac marker' => $labResults,
                'ตรวจปัสสาวะ' => $urineResults, 'Pregnancy test' => $urineResults,
                'X-ray' => $xrayResults, 'MRI' => $mriResults,
                'CT Scan' => $imagingResults, 'Ultrasound' => $imagingResults, 'EKG' => $imagingResults,
                'Echo' => $imagingResults, 'Endoscopy' => $imagingResults,
                'Pathology' => $labResults, 'Culture' => $labResults, 'Other' => $imagingResults,
            ];

            foreach ($investigations as $investigation) {
                $resultText = $docMap[$investigation] ?? '';
                usemed_insert_available('documents', [
                    'patient_id' => (int) $patientRow['id'],
                    'visit_id' => $visitId > 0 ? $visitId : null,
                    'title' => 'ผล' . $investigation . ' - ' . $hn,
                    'document_type' => $investigation,
                    'file_path' => $resultText,
                ]);
            }
        }
    }

    if (!$savedToDb) {
        $_SESSION['demo_saved_visits'] = $_SESSION['demo_saved_visits'] ?? [];
        $_SESSION['demo_saved_visits'][] = [
            'id' => time(),
            'hn' => $hn,
            'full_name' => $patientForRisk['full_name'] ?? '-',
            'date' => $visitDate,
            'visit_date' => $visitDate,
            'title' => $title,
            'doctor' => current_user()['name'] ?? 'Doctor',
            'doctor_name' => current_user()['name'] ?? 'Doctor',
            'diagnosis' => $diagnosis,
            'treatment_plan' => $treatmentPlan,
            'summary' => $visitReason,
            'risk' => $risk['level'],
            'risk_level' => $risk['level'],
            'risk_score' => (int) $risk['score'],
            'visit_type' => $visitType,
            'visit_reason' => $visitReason,
            'care_area' => $careArea,
            'hospital' => $hospital,
            'payment_method' => $paymentMethod,
            'insurance_detail' => $insuranceDetail,
            'blood_group' => $bloodGroup,
            'weight_kg' => $weightKg,
            'height_cm' => $heightCm,
            'temperature' => $temperature,
            'respiratory_rate' => $respiratoryRate,
            'oxygen_saturation' => $oxygenSaturation,
            'systolic' => $systolic,
            'diastolic' => $diastolic,
            'pulse' => $pulse,
            'glucose' => $glucose,
            'hba1c' => $hba1c,
            'bmi' => $bmi,
            'cholesterol' => $cholesterol,
            'alcohol_use' => $alcoholUse,
            'smoking_status' => $smokingStatus,
            'has_surgery' => $hasSurgery,
            'surgery_type' => $surgeryType,
            'surgery_note' => $surgeryNote,
            'has_menstruation' => $hasMenstruation,
            'last_menstrual_period' => $lastMenstrualPeriod,
            'investigations' => $investigationText,
            'lab_results' => $labResults,
            'urine_results' => $urineResults,
            'xray_results' => $xrayResults,
            'mri_results' => $mriResults,
            'imaging_results' => $imagingResults,
            'doctor_education' => $doctorEducation,
            'next_appointment_detail' => $nextAppointmentDetail,
            'followup_date' => $followupDate,
            'chief_complaint' => $chiefComplaint,
            'present_illness' => $presentIllness,
            'review_of_systems' => $reviewOfSystems,
            'past_history' => $pastHistory,
            'past_surgical_history' => $pastSurgicalHistory,
            'allergy_type' => $allergyType,
            'allergy_history' => $allergyHistory,
            'current_medications' => $currentMedications,
            'family_history' => $familyHistory,
            'social_history' => $socialHistory,
            'immunization_history' => $immunizationHistory,
            'pregnancy_status' => $pregnancyStatus,
            'physical_exam' => $physicalExam,
            'physical_exam_general' => $physicalExamGeneral,
            'physical_exam_heent' => $physicalExamHeent,
            'physical_exam_chest_lung' => $physicalExamChestLung,
            'physical_exam_cvs' => $physicalExamCvs,
            'physical_exam_abdomen' => $physicalExamAbdomen,
            'physical_exam_neuro' => $physicalExamNeuro,
            'physical_exam_extremity' => $physicalExamExtremity,
            'physical_exam_skin' => $physicalExamSkin,
            'provisional_diagnosis' => $provisionalDiagnosis,
            'differential_diagnosis' => $differentialDiagnosis,
            'final_diagnosis' => $finalDiagnosis,
            'icd10_code' => $icd10Code,
            'assessment' => $assessment,
            'procedure_name' => $procedureName,
            'procedure_note' => $procedureNote,
            'anesthesia_type' => $anesthesiaType,
            'medication_orders' => $medicationOrders,
            'nursing_instructions' => $nursingInstructions,
            'consult_request' => $consultRequest,
            'disposition' => $disposition,
            'admission_ward' => $admissionWard,
            'triage_level' => $triageLevel,
            'red_flags' => $redFlags,
            'consent_status' => $consentStatus,
        ];
    }

    flash_set('success', ($savedToDb ? 'บันทึกเวชระเบียน EMR ลงฐานข้อมูลจริงแล้ว' : 'บันทึกเวชระเบียน EMR ใน Demo Session แล้ว') . ' · Risk Score: ' . $risk['score'] . '/100');
    redirect_to('doctor/visit-detail.php?id=' . ($visitId ?: 1) . '&hn=' . urlencode($hn));
}

page_start('ซักประวัติและเพิ่มการรักษา EMR', 'doctor', 'treatment');

topbar(
    'ซักประวัติ / เพิ่มการรักษาแบบ HIS-EMR',
    'เลือกผู้ป่วยแล้วข้อมูลจะเปลี่ยนตาม HN ทันที · ฟอร์มย่อให้ compact ขึ้น'
);
?>

<section class="emr-hero">
    <div class="emr-panel">
        <h2>EMR Clinical Note</h2>
        <p class="emr-subtitle">
            ฟอร์มนี้จัดเป็นโครงเวชระเบียนแบบใช้งานจริง: Visit/Encounter → ซักประวัติ → ตรวจร่างกาย → วินิจฉัย → สั่งตรวจ/สั่งยา → แผนจำหน่ายและนัดหมาย
        </p>
        <div class="emr-steps">
            <span class="emr-chip">Encounter</span><span class="emr-chip">Subjective</span><span class="emr-chip">Objective</span><span class="emr-chip">Assessment</span><span class="emr-chip">Plan</span><span class="emr-chip">Orders</span><span class="emr-chip">Disposition</span>
        </div>
    </div>
    <aside class="emr-panel">
        <h2><?= e($patient['hn'] ?? $hnDefault) ?></h2>
        <p class="emr-subtitle"><?= e($patient['full_name'] ?? '-') ?> · <?= e($patient['gender'] ?? '-') ?> · <?= e($patient['age'] ?? '-') ?> ปี</p>
        <div class="emr-note">สถานะปัจจุบัน: <strong><?= e($patient['care_area'] ?? 'OPD') ?></strong><br>โรงพยาบาล: <?= e($patient['hospital'] ?? '-') ?></div>
    </aside>
</section>

<form method="post" class="form-card full-width medical-form compact-emr-form" autocomplete="off" data-loading-title="กำลังบันทึกเวชระเบียน" data-loading-detail="ระบบกำลังประเมินความเสี่ยงแบบเร็วและบันทึกข้อมูล EMR">
    <section class="emr-section">
        <h2><small>1</small> Encounter / Visit</h2>
        <p class="emr-subtitle">ข้อมูลครั้งที่มารับบริการ ใช้แยก OPD/IPD/Follow up/ER และสิทธิการรักษา</p>
        <div class="form-grid compact-grid">
            <div class="field span-2 patient-picker-field">
                <label for="patient_picker">เลือกผู้ป่วย</label>
                <select id="patient_picker" class="patient-picker" onchange="if(this.value){window.location='<?= e(app_url('doctor/add-treatment.php')) ?>?hn='+encodeURIComponent(this.value)}">
                    <?php foreach ($patientOptions as $optionPatient): ?>
                        <?php
                        $optionHn = (string) ($optionPatient['hn'] ?? '');
                        if ($optionHn === '') {
                            continue;
                        }
                        $optionLabel = $optionHn . ' · ' . (string) ($optionPatient['full_name'] ?? '-');
                        if (!empty($optionPatient['care_area'])) {
                            $optionLabel .= ' · ' . (string) $optionPatient['care_area'];
                        }
                        ?>
                        <option value="<?= e($optionHn) ?>" <?= strcasecmp($optionHn, (string) ($patient['hn'] ?? $hnDefault)) === 0 ? 'selected' : '' ?>><?= e($optionLabel) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="field-hint">เลือกชื่อใหม่แล้วหน้า EMR จะ reload พร้อมข้อมูลคนไข้คนนั้น</small>
            </div>
            <div class="field"><label for="hn">HN</label><input id="hn" name="hn" type="text" value="<?= e($patient['hn'] ?? $hnDefault) ?>" readonly required></div>
            <div class="field"><label for="visit_date">วันที่ตรวจ</label><input id="visit_date" name="visit_date" type="date" value="<?= e(date('Y-m-d')) ?>" required></div>
            <div class="field"><label for="visit_type">ประเภท Encounter</label><select id="visit_type" name="visit_type"><?php foreach ($visitTypes as $type): ?><option value="<?= e($type) ?>"><?= e($type) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="care_area">สถานะผู้ป่วย</label><select id="care_area" name="care_area"><?php foreach ($careAreas as $area): ?><option value="<?= e($area) ?>" <?= ($patient['care_area'] ?? 'OPD') === $area ? 'selected' : '' ?>><?= e($area) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="triage_level">ระดับความเร่งด่วน</label><select id="triage_level" name="triage_level"><?php foreach ($triageLevels as $level): ?><option value="<?= e($level) ?>"><?= e($level) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="hospital">โรงพยาบาล</label><select id="hospital" name="hospital"><?php foreach ($hospitals as $hospitalName): ?><option value="<?= e($hospitalName) ?>" <?= ($patient['hospital'] ?? '') === $hospitalName ? 'selected' : '' ?>><?= e($hospitalName) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="admission_ward">Ward / Clinic / ห้องตรวจ</label><input id="admission_ward" name="admission_ward" type="text" placeholder="เช่น OPD อายุรกรรม, ICU เตียง 7, OR 2"></div>
            <div class="field"><label for="disposition">Disposition</label><select id="disposition" name="disposition"><?php foreach ($dispositions as $item): ?><option value="<?= e($item) ?>"><?= e($item) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="payment_method">สิทธิ/การจ่าย</label><select id="payment_method" name="payment_method"><?php foreach ($paymentMethods as $method): ?><option value="<?= e($method) ?>"><?= e($method) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="insurance_detail">รายละเอียดสิทธิ/ประกัน</label><input id="insurance_detail" name="insurance_detail" type="text" placeholder="เช่น UC หน่วยบริการประจำ, ประกันบริษัท, เงินสด"></div>
            <div class="field"><label for="blood_group">กรุ๊ปเลือด</label><select id="blood_group" name="blood_group"><?php foreach ($bloodGroups as $group): ?><option value="<?= e($group) ?>"><?= e($group) ?></option><?php endforeach; ?></select></div>
        </div>
    </section>

    <section class="emr-section">
        <h2><small>2</small> Subjective / ซักประวัติ</h2>
        <p class="emr-subtitle">Chief Complaint, HPI, ROS, ประวัติเก่า, ยา, แพ้ยา, ครอบครัว, สังคม</p>
        <div class="form-grid">
            <div class="field span-2"><label for="chief_complaint">Chief Complaint / อาการสำคัญ</label><textarea id="chief_complaint" name="chief_complaint" required placeholder="เช่น ไข้ 2 วัน, เจ็บหน้าอก 1 ชั่วโมง, มาตามนัดเบาหวาน"></textarea></div>
            <div class="field span-2"><label for="present_illness">Present Illness / ประวัติอาการปัจจุบัน</label><textarea id="present_illness" name="present_illness" placeholder="ลำดับอาการ ระยะเวลา ความรุนแรง ปัจจัยกระตุ้น/บรรเทา อาการร่วม"></textarea></div>
            <div class="field span-2"><label for="review_of_systems">Review of Systems</label><textarea id="review_of_systems" name="review_of_systems" placeholder="ทั่วไป/หัวใจ/ปอด/ทางเดินอาหาร/ปัสสาวะ/ระบบประสาท/ผิวหนัง"></textarea></div>
            <div class="field span-2"><label for="past_history">Past Medical History</label><textarea id="past_history" name="past_history"><?= e($patient['disease'] ?? '') ?></textarea></div>
            <div class="field"><label for="allergy_type">ประเภทประวัติแพ้</label><select id="allergy_type" name="allergy_type"><?php foreach ($allergyTypes as $type): ?><option value="<?= e($type) ?>"><?= e($type) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="allergy_history">รายละเอียดการแพ้</label><input id="allergy_history" name="allergy_history" type="text" placeholder="ชื่อยา/อาหาร + อาการแพ้"></div>
            <div class="field span-2"><label for="current_medications">Medication History / ยาที่ใช้อยู่</label><textarea id="current_medications" name="current_medications" placeholder="ชื่อยา ขนาด วิธีใช้ ความร่วมมือในการใช้ยา"></textarea></div>
            <div class="field"><label for="past_surgical_history">Past Surgical History</label><textarea id="past_surgical_history" name="past_surgical_history" placeholder="ประวัติผ่าตัดเดิม/หัตถการเดิม"></textarea></div>
            <div class="field"><label for="family_history">Family History</label><textarea id="family_history" name="family_history" placeholder="โรคกรรมพันธุ์ เบาหวาน ความดัน มะเร็ง หัวใจ"></textarea></div>
            <div class="field"><label for="social_history">Social History</label><textarea id="social_history" name="social_history" placeholder="อาชีพ การดูแลตนเอง ผู้ดูแล อาหาร การออกกำลังกาย"></textarea></div>
            <div class="field"><label for="immunization_history">Immunization History</label><textarea id="immunization_history" name="immunization_history" placeholder="วัคซีนสำคัญ/วัคซีนล่าสุด"></textarea></div>
        </div>
        <div class="form-grid mt-2">
            <div class="field"><label for="alcohol_use">ดื่มสุรา</label><select id="alcohol_use" name="alcohol_use"><option>ไม่ดื่ม</option><option>ดื่มเป็นครั้งคราว</option><option>ดื่มประจำ</option><option>เลิกแล้ว</option></select></div>
            <div class="field"><label for="smoking_status">สูบบุหรี่</label><select id="smoking_status" name="smoking_status"><option>ไม่สูบ</option><option>สูบเป็นครั้งคราว</option><option>สูบประจำ</option><option>เลิกแล้ว</option></select></div>
            <div class="field"><label for="pregnancy_status">Pregnancy / OB Status</label><select id="pregnancy_status" name="pregnancy_status"><option>ไม่เกี่ยวข้อง</option><option>ไม่ตั้งครรภ์</option><option>ตั้งครรภ์</option><option>หลังคลอด</option><option>ไม่แน่ใจ</option></select></div>
            <div class="field"><label for="has_menstruation">ประจำเดือน</label><select id="has_menstruation" name="has_menstruation"><option>ไม่เกี่ยวข้อง</option><option>มี</option><option>ไม่มี/หมดประจำเดือน</option><option>ไม่แน่ใจ</option></select></div>
            <div class="field"><label for="last_menstrual_period">LMP / ประจำเดือนมาล่าสุด</label><input id="last_menstrual_period" name="last_menstrual_period" type="date"></div>
        </div>
    </section>

    <section class="emr-section">
        <h2><small>3</small> Objective / Vital Signs + Physical Exam</h2>
        <div class="form-grid">
            <div class="field"><label for="weight_kg">น้ำหนัก (kg)</label><input id="weight_kg" name="weight_kg" type="number" step="0.01" value="68"></div>
            <div class="field"><label for="height_cm">ส่วนสูง (cm)</label><input id="height_cm" name="height_cm" type="number" step="0.01" value="165"></div>
            <div class="field"><label for="bmi">BMI</label><input id="bmi" name="bmi" type="number" step="0.01"></div>
            <div class="field"><label for="temperature">Temp (°C)</label><input id="temperature" name="temperature" type="number" step="0.1" value="36.8"></div>
            <div class="field"><label for="systolic">SBP</label><input id="systolic" name="systolic" type="number" value="148"></div>
            <div class="field"><label for="diastolic">DBP</label><input id="diastolic" name="diastolic" type="number" value="92"></div>
            <div class="field"><label for="pulse">HR / Pulse</label><input id="pulse" name="pulse" type="number" value="78"></div>
            <div class="field"><label for="respiratory_rate">RR</label><input id="respiratory_rate" name="respiratory_rate" type="number" value="18"></div>
            <div class="field"><label for="oxygen_saturation">SpO₂ (%)</label><input id="oxygen_saturation" name="oxygen_saturation" type="number" value="98"></div>
            <div class="field"><label for="glucose">Glucose</label><input id="glucose" name="glucose" type="number" step="0.01" value="142"></div>
            <div class="field"><label for="hba1c">HbA1c</label><input id="hba1c" name="hba1c" type="number" step="0.01" value="7.8"></div>
            <div class="field"><label for="cholesterol">Cholesterol</label><input id="cholesterol" name="cholesterol" type="number" step="0.01" value="218"></div>
        </div>
        <div class="form-grid mt-2">
            <div class="field"><label for="physical_exam_general">General</label><textarea id="physical_exam_general" name="physical_exam_general">รู้สึกตัวดี ไม่หอบเหนื่อย</textarea></div>
            <div class="field"><label for="physical_exam_heent">HEENT</label><textarea id="physical_exam_heent" name="physical_exam_heent" placeholder="ซีด/เหลือง/ต่อมน้ำเหลือง/คอ"></textarea></div>
            <div class="field"><label for="physical_exam_chest_lung">Chest/Lung</label><textarea id="physical_exam_chest_lung" name="physical_exam_chest_lung" placeholder="เสียงหายใจ wheezing crackles"></textarea></div>
            <div class="field"><label for="physical_exam_cvs">CVS</label><textarea id="physical_exam_cvs" name="physical_exam_cvs" placeholder="heart sound murmur edema"></textarea></div>
            <div class="field"><label for="physical_exam_abdomen">Abdomen</label><textarea id="physical_exam_abdomen" name="physical_exam_abdomen" placeholder="กดเจ็บ guarding organomegaly"></textarea></div>
            <div class="field"><label for="physical_exam_neuro">Neurological</label><textarea id="physical_exam_neuro" name="physical_exam_neuro" placeholder="GCS motor sensory cranial nerve"></textarea></div>
            <div class="field"><label for="physical_exam_extremity">Extremity</label><textarea id="physical_exam_extremity" name="physical_exam_extremity" placeholder="แผล บวม ชีพจรปลายมือปลายเท้า"></textarea></div>
            <div class="field"><label for="physical_exam_skin">Skin/Wound</label><textarea id="physical_exam_skin" name="physical_exam_skin" placeholder="ผื่น แผล pressure sore infection sign"></textarea></div>
        </div>
    </section>

    <section class="emr-section">
        <h2><small>4</small> Assessment / Diagnosis</h2>
        <div class="form-grid">
            <div class="field"><label for="title">หัวข้อ Visit</label><input id="title" name="title" type="text" value="ซักประวัติและบันทึกการรักษา EMR" required></div>
            <div class="field"><label for="icd10_code">ICD-10 / รหัสวินิจฉัย</label><input id="icd10_code" name="icd10_code" type="text" placeholder="เช่น E11.9, I10"></div>
            <div class="field span-2"><label for="diagnosis">Diagnosis หลัก</label><textarea id="diagnosis" name="diagnosis" required><?= e($patient['disease'] ?? 'Follow up') ?></textarea></div>
            <div class="field"><label for="provisional_diagnosis">Provisional Diagnosis</label><textarea id="provisional_diagnosis" name="provisional_diagnosis" placeholder="วินิจฉัยเบื้องต้น"></textarea></div>
            <div class="field"><label for="differential_diagnosis">Differential Diagnosis</label><textarea id="differential_diagnosis" name="differential_diagnosis" placeholder="โรคที่ต้องแยก"></textarea></div>
            <div class="field"><label for="final_diagnosis">Final Diagnosis</label><textarea id="final_diagnosis" name="final_diagnosis" placeholder="วินิจฉัยสุดท้าย/หลังผลตรวจ"></textarea></div>
            <div class="field"><label for="assessment">Clinical Assessment</label><textarea id="assessment" name="assessment" placeholder="สรุปการประเมิน ปัญหาหลัก ความเสี่ยง และเหตุผลทางคลินิก"></textarea></div>
        </div>
    </section>

    <section class="emr-section">
        <h2><small>5</small> Orders / Lab / Imaging</h2>
        <p class="emr-subtitle">ติ๊กสั่งตรวจและใส่ผลตรวจ เมื่อต่อ DB จะสร้างเอกสารผลตรวจให้ตามรายการที่เลือก</p>
        <div class="check-grid dense">
            <?php foreach ($investigationOptions as $name => $hint): ?>
                <label class="check-card">
                    <input type="checkbox" name="investigations[]" value="<?= e($name) ?>" <?= in_array($name, ['CBC','Blood chemistry','ตรวจปัสสาวะ','X-ray'], true) ? 'checked' : '' ?>>
                    <span><strong><?= e($name) ?></strong><small><?= e($hint) ?></small></span>
                </label>
            <?php endforeach; ?>
        </div>
        <div class="form-grid mt-2">
            <div class="field"><label for="lab_results">ผล Lab / Blood</label><textarea id="lab_results" name="lab_results">CBC, chemistry, HbA1c/Lipid ตามข้อบ่งชี้</textarea></div>
            <div class="field"><label for="urine_results">ผล Urine / UPT</label><textarea id="urine_results" name="urine_results">Urinalysis ตามแผนการตรวจ</textarea></div>
            <div class="field"><label for="xray_results">ผล X-ray</label><textarea id="xray_results" name="xray_results" placeholder="สรุปผล X-ray"></textarea></div>
            <div class="field"><label for="mri_results">ผล MRI</label><textarea id="mri_results" name="mri_results" placeholder="สรุปผล MRI"></textarea></div>
        </div>
        <div class="field mt-2"><label for="imaging_results">ผล Imaging/Procedure อื่น ๆ</label><textarea id="imaging_results" name="imaging_results" placeholder="CT, Ultrasound, EKG, Echo, Endoscopy, Pathology, Culture"></textarea></div>
    </section>

    <section class="emr-section">
        <h2><small>6</small> Plan / Procedure / Medication</h2>
        <div class="form-grid">
            <div class="field span-2"><label for="treatment_plan">Treatment Plan</label><textarea id="treatment_plan" name="treatment_plan">ให้ยา/คำแนะนำตามแผน ตรวจติดตาม และนัดหมายครั้งถัดไป</textarea></div>
            <div class="field span-2"><label for="medication_orders">Medication Orders / Prescription</label><textarea id="medication_orders" name="medication_orders" placeholder="ชื่อยา ขนาด วิธีใช้ จำนวน วันเริ่ม-หยุดยา"></textarea></div>
            <div class="field"><label for="has_surgery">มีการผ่าตัดไหม</label><select id="has_surgery" name="has_surgery"><option>ไม่มี</option><option>มี</option><option>วางแผนผ่าตัด</option><option>รอคิวผ่าตัด</option></select></div>
            <div class="field"><label for="surgery_type">ประเภทผ่าตัด</label><select id="surgery_type" name="surgery_type"><option>-</option><option>ผ่าตัดเล็ก</option><option>ผ่าตัดใหญ่</option><option>หัตถการ</option></select></div>
            <div class="field"><label for="procedure_name">Procedure Name</label><input id="procedure_name" name="procedure_name" type="text" placeholder="ชื่อหัตถการ/ผ่าตัด"></div>
            <div class="field"><label for="anesthesia_type">Anesthesia</label><select id="anesthesia_type" name="anesthesia_type"><option>-</option><option>Local</option><option>Regional</option><option>General</option><option>Sedation</option></select></div>
            <div class="field span-2"><label for="surgery_note">Surgery Note</label><textarea id="surgery_note" name="surgery_note" placeholder="ข้อบ่งชี้ ความเสี่ยง คิวผ่าตัด แผนก่อน/หลังผ่าตัด"></textarea></div>
            <div class="field span-2"><label for="procedure_note">Procedure Note</label><textarea id="procedure_note" name="procedure_note" placeholder="รายละเอียดหัตถการ ผลลัพธ์ ภาวะแทรกซ้อน"></textarea></div>
            <div class="field"><label for="consent_status">Consent</label><select id="consent_status" name="consent_status"><option>ยังไม่ต้องใช้ consent</option><option>อธิบายและยินยอมแล้ว</option><option>รอญาติ/ผู้ป่วยตัดสินใจ</option><option>ปฏิเสธการรักษา</option></select></div>
            <div class="field"><label for="consult_request">Consult / Refer ในโรงพยาบาล</label><input id="consult_request" name="consult_request" type="text" placeholder="เช่น consult cardio / surgery"></div>
            <div class="field span-2"><label for="nursing_instructions">Nursing Instructions</label><textarea id="nursing_instructions" name="nursing_instructions" placeholder="คำสั่งพยาบาล เฝ้าระวัง V/S, I/O, NPO, fall precaution"></textarea></div>
        </div>
    </section>

    <section class="emr-section">
        <h2><small>7</small> Patient Education / Follow-up / Safety</h2>
        <div class="field"><label for="doctor_education">ข้อมูลให้ความรู้ผู้ป่วย</label><textarea id="doctor_education" name="doctor_education">อธิบายโรค แผนยา การดูแลตนเอง สัญญาณอันตราย และการมาตามนัด</textarea></div>
        <div class="form-grid mt-2">
            <div class="field"><label for="followup_date">วันนัดติดตาม</label><input id="followup_date" name="followup_date" type="date"></div>
            <div class="field"><label for="next_appointment_detail">รายละเอียดนัดหมายเพิ่มเติม</label><textarea id="next_appointment_detail" name="next_appointment_detail">นัดติดตามผลตรวจ หากอาการแย่ลงให้มาก่อนนัด</textarea></div>
            <div class="field span-2"><label for="red_flags">Red Flags / อาการที่ต้องกลับมาโรงพยาบาลทันที</label><textarea id="red_flags" name="red_flags" placeholder="เช่น หอบเหนื่อย เจ็บหน้าอก ซึมลง ไข้สูง เลือดออก ปวดมากขึ้น"></textarea></div>
        </div>
    </section>

    <div class="btn-row mt-2 sticky-actions">
        <button class="btn" type="submit">บันทึกเวชระเบียน EMR</button>
        <a class="btn secondary" href="<?= e(app_url('doctor/patient-profile.php?hn=' . urlencode((string) ($patient['hn'] ?? $hnDefault)))) ?>">กลับข้อมูลผู้ป่วย</a>
    </div>
</form>

<?php page_end();
