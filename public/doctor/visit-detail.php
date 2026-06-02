<?php
// public/doctor/visit-detail.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('doctor');

$visitId = (int) ($_GET['id'] ?? 1);

$patient = demo_patient();
$visits = demo_visits();
$documents = demo_documents();
$visit = null;

if (db_is_connected()) {
    $row = db_fetch_one(
        'SELECT
            v.*,
            p.hn,
            p.full_name,
            p.gender,
            p.age,
            p.phone,
            p.disease,
            p.address,
            d.full_name AS doctor_name
         FROM visits v
         INNER JOIN patients p ON p.id = v.patient_id
         LEFT JOIN doctors d ON d.id = v.doctor_id
         WHERE v.id = :id
         LIMIT 1',
        ['id' => $visitId]
    );

    if ($row) {
        $visit = $row;

        $patient = [
            'id' => $row['patient_id'] ?? 1,
            'hn' => $row['hn'] ?? 'HN0001',
            'full_name' => $row['full_name'] ?? 'สมชาย ใจดี',
            'gender' => $row['gender'] ?? '-',
            'age' => $row['age'] ?? '-',
            'phone' => $row['phone'] ?? '-',
            'disease' => $row['disease'] ?? '-',
            'address' => $row['address'] ?? '-',
            'risk_level' => $row['risk_level'] ?? 'Medium',
            'next_appointment' => '-',
        ];

        $dbDocuments = db_fetch_all(
            'SELECT *
             FROM documents
             WHERE patient_id = :patient_id
             ORDER BY created_at DESC, id DESC',
            ['patient_id' => (int) ($row['patient_id'] ?? 0)]
        );

        if (!empty($dbDocuments)) {
            $documents = $dbDocuments;
        }
    }
}

if (!$visit) {
    foreach ($visits as $item) {
        if ((int) ($item['id'] ?? 0) === $visitId) {
            $visit = $item;
            break;
        }
    }
}

if (!$visit) {
    $visit = $visits[0] ?? [];
}

$date = $visit['date'] ?? $visit['visit_date'] ?? '-';
$title = $visit['title'] ?? 'รายละเอียดการรักษา';
$doctorName = $visit['doctor'] ?? $visit['doctor_name'] ?? current_user()['name'] ?? 'Doctor';
$diagnosis = $visit['diagnosis'] ?? '-';
$treatmentPlan = $visit['treatment_plan'] ?? $visit['summary'] ?? '-';
$summary = $visit['summary'] ?? $visit['treatment_plan'] ?? $diagnosis;
$riskLevel = $visit['risk'] ?? $visit['risk_level'] ?? 'Medium';
$riskScore = (int) ($visit['risk_score'] ?? 62);
$riskBadge = badge_class((string) $riskLevel);

$systolic = $visit['systolic'] ?? 148;
$diastolic = $visit['diastolic'] ?? 92;
$pulse = $visit['pulse'] ?? 78;
$glucose = $visit['glucose'] ?? 142;
$hba1c = $visit['hba1c'] ?? 7.8;
$bmi = $visit['bmi'] ?? 27.4;
$cholesterol = $visit['cholesterol'] ?? 218;
$visitType = usemed_visit_field($visit, 'visit_type', '-');
$visitReason = usemed_visit_field($visit, 'visit_reason', '-');
$careArea = usemed_visit_field($visit, 'care_area', '-');
$hospital = usemed_visit_field($visit, 'hospital', '-');
$paymentMethod = usemed_visit_field($visit, 'payment_method', '-');
$insuranceDetail = usemed_visit_field($visit, 'insurance_detail', '-');
$bloodGroup = usemed_visit_field($visit, 'blood_group', '-');
$weightKg = usemed_visit_field($visit, 'weight_kg', '-');
$heightCm = usemed_visit_field($visit, 'height_cm', '-');
$temperature = usemed_visit_field($visit, 'temperature', '-');
$respiratoryRate = usemed_visit_field($visit, 'respiratory_rate', '-');
$oxygenSaturation = usemed_visit_field($visit, 'oxygen_saturation', '-');
$alcoholUse = usemed_visit_field($visit, 'alcohol_use', '-');
$smokingStatus = usemed_visit_field($visit, 'smoking_status', '-');
$hasSurgery = usemed_visit_field($visit, 'has_surgery', '-');
$surgeryType = usemed_visit_field($visit, 'surgery_type', '-');
$surgeryNote = usemed_visit_field($visit, 'surgery_note', '-');
$hasMenstruation = usemed_visit_field($visit, 'has_menstruation', '-');
$lastMenstrualPeriod = usemed_visit_field($visit, 'last_menstrual_period', '-');
$investigations = usemed_visit_field($visit, 'investigations', '-');
$labResults = usemed_visit_field($visit, 'lab_results', '-');
$urineResults = usemed_visit_field($visit, 'urine_results', '-');
$xrayResults = usemed_visit_field($visit, 'xray_results', '-');
$mriResults = usemed_visit_field($visit, 'mri_results', '-');
$imagingResults = usemed_visit_field($visit, 'imaging_results', '-');
$doctorEducation = usemed_visit_field($visit, 'doctor_education', '-');
$nextAppointmentDetail = usemed_visit_field($visit, 'next_appointment_detail', '-');
$followupDate = usemed_visit_field($visit, 'followup_date', '-');

page_start('รายละเอียดการรักษา', 'doctor', 'patient');

topbar(
    'Visit Detail',
    'รายละเอียดการตรวจ วินิจฉัย แผนรักษา และผลประเมิน AI Risk'
);
?>

<style>
/* Collapsible Details Styles */
.collapsible-card {
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid rgba(220, 235, 231, 0.9);
    border-radius: var(--radius);
    margin-top: 20px;
    overflow: hidden;
    transition: box-shadow 0.25s ease, border-color 0.25s ease;
    box-shadow: var(--shadow-soft);
}
.collapsible-card:hover {
    border-color: #8ce4d3;
}
.collapsible-card summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    font-size: 18px;
    font-weight: 700;
    color: var(--primary-dark);
    cursor: pointer;
    user-select: none;
    list-style: none;
    background: linear-gradient(135deg, rgba(244, 251, 250, 0.6) 0%, rgba(255, 255, 255, 0.8) 100%);
    transition: background 0.2s ease, color 0.2s ease;
}
.collapsible-card summary:hover {
    background: rgba(15, 159, 133, 0.05);
    color: var(--primary);
}
.collapsible-card summary::-webkit-details-marker {
    display: none;
}
.collapsible-card summary .summary-title {
    display: flex;
    align-items: center;
    gap: 12px;
}
.collapsible-card summary .summary-icon {
    font-size: 20px;
}
.collapsible-card summary::after {
    content: '';
    display: inline-block;
    width: 8px;
    height: 8px;
    border-right: 2.5px solid var(--primary-dark);
    border-bottom: 2.5px solid var(--primary-dark);
    transform: rotate(45deg);
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    margin-right: 6px;
}
.collapsible-card[open] {
    border-color: var(--primary);
    box-shadow: var(--shadow);
}
.collapsible-card[open] summary {
    border-bottom: 1px solid var(--line);
    background: var(--white);
}
.collapsible-card[open] summary::after {
    transform: rotate(-135deg);
}
.collapsible-body {
    padding: 24px;
    background: rgba(255, 255, 255, 0.5);
    animation: slideDown 0.25s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}
.collapsible-body > .grid {
    margin-top: 0 !important;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<section class="stat-grid">
    <?php stat_card('วันที่ตรวจ', (string) $date, 'Visit Date'); ?>
    <?php stat_card('ผู้ป่วย', $patient['hn'] ?? 'HN0001', $patient['full_name'] ?? 'Patient'); ?>
    <?php stat_card('Risk Score', (string) $riskScore . '/100', (string) $riskLevel); ?>
    <?php stat_card('แพทย์ผู้ตรวจ', (string) $doctorName, 'Doctor'); ?>
</section>

<section class="grid grid-2">
    <div class="card">
        <h2><?= e($title) ?></h2>
        <p class="text-muted">
            รายละเอียด Visit ของผู้ป่วย พร้อมข้อมูลวินิจฉัย แผนการรักษา และค่าตรวจสำคัญ
        </p>

        <div class="document-grid mt-2">
            <div class="document-card">
                <div>
                    <strong>ผู้ป่วย</strong>
                    <span><?= e($patient['full_name'] ?? '-') ?> / <?= e($patient['hn'] ?? '-') ?></span>
                </div>
                <span class="badge blue">Patient</span>
            </div>

            <div class="document-card">
                <div>
                    <strong>แพทย์ผู้ตรวจ</strong>
                    <span><?= e($doctorName) ?></span>
                </div>
                <span class="badge green">Doctor</span>
            </div>

            <div class="document-card">
                <div>
                    <strong>วันที่ตรวจ</strong>
                    <span><?= e($date) ?></span>
                </div>
                <span class="badge orange">Visit</span>
            </div>
        </div>

        <div class="btn-row mt-2">
            <button class="btn" type="button" data-print>
                พิมพ์ Visit
            </button>

            <a class="btn secondary" href="<?= e(app_url('doctor/timeline.php?hn=' . urlencode((string) ($patient['hn'] ?? 'HN0001')))) ?>">
                กลับ Timeline
            </a>

            <a class="btn secondary" href="<?= e(app_url('doctor/patient-profile.php?hn=' . urlencode((string) ($patient['hn'] ?? 'HN0001')))) ?>">
                โปรไฟล์ผู้ป่วย
            </a>
        </div>
    </div>

    <div class="risk-card">
        <div class="risk-score">
            <div>
                <span class="badge <?= e($riskBadge) ?>">
                    Risk <?= e($riskLevel) ?>
                </span>

                <h2 style="margin:12px 0 6px;">
                    คะแนนความเสี่ยง <?= e($riskScore) ?>/100
                </h2>

                <p class="text-muted">
                    ประเมินจากข้อมูล Vital Signs และ Lab ของ Visit นี้
                </p>
            </div>

            <div class="score-circle" style="--value:<?= e($riskScore) ?>">
                <strong><?= e($riskScore) ?></strong>
            </div>
        </div>

        <div class="mt-2">
            <div class="riskbar">
                <span style="width:<?= e($riskScore) ?>%"></span>
            </div>
        </div>

        <ul class="factor-list">
            <li>BP: <?= e($systolic) ?>/<?= e($diastolic) ?> mmHg</li>
            <li>Glucose: <?= e($glucose) ?> mg/dL</li>
            <li>HbA1c: <?= e($hba1c) ?>%</li>
        </ul>
    </div>
</section>

<details class="collapsible-card">
    <summary>
        <span class="summary-title"><span class="summary-icon">🩺</span> ผลการวินิจฉัยโรค (Diagnosis)</span>
    </summary>
    <div class="collapsible-body">
        <div class="note-box mt-2">
            <?= nl2br(e($diagnosis)) ?>
        </div>
    </div>
</details>

<details class="collapsible-card">
    <summary>
        <span class="summary-title"><span class="summary-icon">📋</span> แผนรักษาและสรุปอาการ (Treatment Plan & Summary)</span>
    </summary>
    <div class="collapsible-body">
        <h2>Treatment Plan</h2>
        <div class="note-box mt-2">
            <?= nl2br(e($treatmentPlan)) ?>
        </div>

        <h2 class="mt-2">Summary</h2>
        <p><?= nl2br(e($summary)) ?></p>
    </div>
</details>

<details class="collapsible-card">
    <summary>
        <span class="summary-title"><span class="summary-icon">📊</span> สัญญาณชีพและผลตรวจพื้นฐาน (Vital Signs & Basic Lab)</span>
    </summary>
    <div class="collapsible-body">
        <div class="document-grid">
            <div class="document-card">
                <div>
                    <strong>Blood Pressure</strong>
                    <span><?= e($systolic) ?>/<?= e($diastolic) ?> mmHg</span>
                </div>
                <span class="badge <?= ((int) $systolic >= 140 || (int) $diastolic >= 90) ? 'orange' : 'green' ?>">
                    BP
                </span>
            </div>

            <div class="document-card">
                <div>
                    <strong>Pulse</strong>
                    <span><?= e($pulse) ?> bpm</span>
                </div>
                <span class="badge blue">Pulse</span>
            </div>

            <div class="document-card">
                <div>
                    <strong>Glucose</strong>
                    <span><?= e($glucose) ?> mg/dL</span>
                </div>
                <span class="badge <?= ((float) $glucose >= 126) ? 'orange' : 'green' ?>">
                    Sugar
                </span>
            </div>

            <div class="document-card">
                <div>
                    <strong>HbA1c</strong>
                    <span><?= e($hba1c) ?>%</span>
                </div>
                <span class="badge <?= ((float) $hba1c >= 7) ? 'orange' : 'green' ?>">
                    HbA1c
                </span>
            </div>

            <div class="document-card">
                <div>
                    <strong>BMI</strong>
                    <span><?= e($bmi) ?></span>
                </div>
                <span class="badge <?= ((float) $bmi >= 25) ? 'orange' : 'green' ?>">
                    BMI
                </span>
            </div>

            <div class="document-card">
                <div>
                    <strong>Cholesterol</strong>
                    <span><?= e($cholesterol) ?> mg/dL</span>
                </div>
                <span class="badge <?= ((float) $cholesterol >= 240) ? 'red' : 'blue' ?>">
                    Lipid
                </span>
            </div>
        </div>
    </div>
</details>

<details class="collapsible-card">
    <summary>
        <span class="summary-title"><span class="summary-icon">👤</span> ข้อมูลผู้ป่วย (Patient Profile)</span>
    </summary>
    <div class="collapsible-body">
        <div class="document-grid">
            <div class="document-card">
                <div>
                    <strong><?= e($patient['full_name'] ?? '-') ?></strong>
                    <span>HN: <?= e($patient['hn'] ?? '-') ?></span>
                </div>
                <span class="badge blue">Profile</span>
            </div>

            <div class="document-card">
                <div>
                    <strong>อายุ / เพศ</strong>
                    <span><?= e($patient['age'] ?? '-') ?> ปี / <?= e($patient['gender'] ?? '-') ?></span>
                </div>
                <span class="badge green">Info</span>
            </div>

            <div class="document-card">
                <div>
                    <strong>โรคประจำตัว</strong>
                    <span><?= e($patient['disease'] ?? '-') ?></span>
                </div>
                <span class="badge red">Chronic</span>
            </div>

            <div class="document-card">
                <div>
                    <strong>เบอร์โทร</strong>
                    <span><?= e($patient['phone'] ?? '-') ?></span>
                </div>
                <span class="badge orange">Contact</span>
            </div>
        </div>
    </div>
</details>

<details class="collapsible-card">
    <summary>
        <span class="summary-title"><span class="summary-icon">📂</span> เอกสารที่เกี่ยวข้อง (Related Documents)</span>
    </summary>
    <div class="collapsible-body">
        <?php if (empty($documents)): ?>
            <?php render_empty_state('ยังไม่มีเอกสาร', 'ยังไม่มีเอกสารที่เกี่ยวข้องกับ Visit นี้'); ?>
        <?php else: ?>
            <div class="document-grid">
                <?php foreach ($documents as $doc): ?>
                    <?php
                    $docId = (int) ($doc['id'] ?? 1);
                    $docTitle = $doc['title'] ?? 'เอกสารสุขภาพ';
                    $docType = $doc['document_type'] ?? $doc['type'] ?? 'PDF';
                    $docDate = $doc['created_at'] ?? $doc['date'] ?? '-';
                    ?>
                    <a class="document-card" href="<?= e(app_url('doctor/document-view.php?id=' . $docId)) ?>">
                        <div>
                            <strong><?= e($docTitle) ?></strong>
                            <span><?= e($docDate) ?></span>
                        </div>
                        <span class="badge blue"><?= e($docType) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</details>

<details class="collapsible-card">
    <summary>
        <span class="summary-title"><span class="summary-icon">🏥</span> รายละเอียด Visit / สิทธิรักษา (Visit Details)</span>
    </summary>
    <div class="collapsible-body">
        <div class="document-grid">
            <div class="document-card"><div><strong>ประเภทการมา</strong><span><?= e($visitType) ?> · <?= e($careArea) ?></span></div><span class="badge blue">Visit</span></div>
            <div class="document-card"><div><strong>มาด้วยเรื่อง</strong><span><?= e($visitReason) ?></span></div><span class="badge orange">CC</span></div>
            <div class="document-card"><div><strong>โรงพยาบาล</strong><span><?= e($hospital) ?></span></div><span class="badge green">Hospital</span></div>
            <div class="document-card"><div><strong>สิทธิ/การชำระเงิน</strong><span><?= e($paymentMethod) ?> · <?= e($insuranceDetail) ?></span></div><span class="badge blue">Payment</span></div>
            <div class="document-card"><div><strong>กรุ๊ปเลือด</strong><span><?= e($bloodGroup) ?></span></div><span class="badge red">Blood</span></div>
            <div class="document-card"><div><strong>สุรา / บุหรี่</strong><span><?= e($alcoholUse) ?> · <?= e($smokingStatus) ?></span></div><span class="badge orange">Behavior</span></div>
        </div>
    </div>
</details>

<details class="collapsible-card">
    <summary>
        <span class="summary-title"><span class="summary-icon">📅</span> การผ่าตัด / ประจำเดือน / นัดติดตาม (Surgery & OB/GYN)</span>
    </summary>
    <div class="collapsible-body">
        <div class="document-grid">
            <div class="document-card"><div><strong>ผ่าตัด</strong><span><?= e($hasSurgery) ?> · <?= e($surgeryType) ?></span></div><span class="badge red">Surgery</span></div>
            <div class="document-card"><div><strong>รายละเอียดผ่าตัด</strong><span><?= e($surgeryNote) ?></span></div><span class="badge orange">Note</span></div>
            <div class="document-card"><div><strong>ประจำเดือน</strong><span><?= e($hasMenstruation) ?> · LMP: <?= e($lastMenstrualPeriod) ?></span></div><span class="badge blue">OB</span></div>
            <div class="document-card"><div><strong>นัดติดตาม</strong><span><?= e($followupDate) ?> · <?= e($nextAppointmentDetail) ?></span></div><span class="badge green">Follow-up</span></div>
        </div>
    </div>
</details>

<details class="collapsible-card">
    <summary>
        <span class="summary-title"><span class="summary-icon">🔬</span> ผลตรวจเพิ่มเติม (Additional Lab Results)</span>
    </summary>
    <div class="collapsible-body">
        <div class="document-grid">
            <div class="document-card"><div><strong>ตรวจที่สั่ง</strong><span><?= e($investigations) ?></span></div><span class="badge green">Orders</span></div>
            <div class="document-card"><div><strong>ผลตรวจเลือด</strong><span><?= e($labResults) ?></span></div><span class="badge red">Blood</span></div>
            <div class="document-card"><div><strong>ผลปัสสาวะ</strong><span><?= e($urineResults) ?></span></div><span class="badge blue">Urine</span></div>
            <div class="document-card"><div><strong>X-ray</strong><span><?= e($xrayResults) ?></span></div><span class="badge orange">X-ray</span></div>
            <div class="document-card"><div><strong>MRI</strong><span><?= e($mriResults) ?></span></div><span class="badge orange">MRI</span></div>
            <div class="document-card"><div><strong>Imaging อื่น ๆ</strong><span><?= e($imagingResults) ?></span></div><span class="badge blue">Imaging</span></div>
        </div>
    </div>
</details>

<details class="collapsible-card">
    <summary>
        <span class="summary-title"><span class="summary-icon">💡</span> คำแนะนำสำหรับผู้ป่วย (Instructions & Vitals)</span>
    </summary>
    <div class="collapsible-body">
        <div class="note-box">
            <?= nl2br(e($doctorEducation)) ?>
        </div>
        <h2 class="mt-2">ข้อมูลร่างกายเพิ่มเติม</h2>
        <div class="document-grid mt-2">
            <div class="document-card"><div><strong>น้ำหนัก / ส่วนสูง</strong><span><?= e($weightKg) ?> kg · <?= e($heightCm) ?> cm</span></div><span class="badge blue">Body</span></div>
            <div class="document-card"><div><strong>Temp / RR / SpO₂</strong><span><?= e($temperature) ?>°C · RR <?= e($respiratoryRate) ?> · SpO₂ <?= e($oxygenSaturation) ?>%</span></div><span class="badge green">Vital</span></div>
        </div>
    </div>
</details>

<details class="collapsible-card">
    <summary>
        <span class="summary-title"><span class="summary-icon">📝</span> บันทึกของแพทย์ (Doctor Note)</span>
    </summary>
    <div class="collapsible-body">
        <p>
            Visit นี้บันทึกไว้ในระบบ USE MED เพื่อใช้ติดตามการรักษาของผู้ป่วย
            แพทย์สามารถดู Timeline, เอกสาร และผลประเมินความเสี่ยงประกอบการรักษาครั้งถัดไปได้
        </p>

        <div class="btn-row mt-2">
            <a class="btn" href="<?= e(app_url('doctor/add-treatment.php')) ?>">
                เพิ่มการรักษาครั้งใหม่
            </a>

            <a class="btn secondary" href="<?= e(app_url('doctor/documents.php')) ?>">
                ดูเอกสารทั้งหมด
            </a>

            <a class="btn secondary" href="<?= e(app_url('doctor/ai-risk.php')) ?>">
                เปิด AI Risk
            </a>
        </div>
    </div>
</details>

<?php
page_end();