<?php
// public/patient/portal.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('patient');
usemed_ensure_extended_schema();

$user = current_user();

$patient = demo_patient();
$visits = demo_visits();
$documents = demo_documents();

if (db_is_connected()) {
    $patientRow = db_fetch_one(
        'SELECT * FROM patients WHERE hn = :hn LIMIT 1',
        ['hn' => $user['hn'] ?? 'HN0001']
    );

    if ($patientRow) {
        $patient = array_merge($patient, $patientRow);

        $dbVisits = db_fetch_all(
            'SELECT 
                v.*,
                d.full_name AS doctor_name
             FROM visits v
             LEFT JOIN doctors d ON d.id = v.doctor_id
             WHERE v.patient_id = :patient_id
             ORDER BY v.visit_date DESC, v.id DESC
             LIMIT 5',
            ['patient_id' => (int) $patientRow['id']]
        );

        if (!empty($dbVisits)) {
            $visits = $dbVisits;
        }

        $dbDocuments = db_fetch_all(
            'SELECT *
             FROM documents
             WHERE patient_id = :patient_id
             ORDER BY created_at DESC, id DESC
             LIMIT 5',
            ['patient_id' => (int) $patientRow['id']]
        );

        if (!empty($dbDocuments)) {
            $documents = $dbDocuments;
        }
    }
}

$latestVisit = $visits[0] ?? [];
$riskLevel = $latestVisit['risk'] ?? $latestVisit['risk_level'] ?? $patient['risk_level'] ?? 'Medium';
$riskScore = (int) ($latestVisit['risk_score'] ?? 62);
$riskBadge = badge_class((string) $riskLevel);
$paymentMethod = usemed_visit_field($latestVisit, 'payment_method', (string) ($patient['payment_method'] ?? 'ยังไม่มีข้อมูลสิทธิ'));
$insuranceDetail = usemed_visit_field($latestVisit, 'insurance_detail', (string) ($patient['insurance_detail'] ?? 'ยังไม่มีรายละเอียด'));
$visitHospital = usemed_visit_field($latestVisit, 'hospital', (string) ($patient['hospital'] ?? '-'));
$educationText = usemed_visit_field($latestVisit, 'doctor_education', 'ยังไม่มีคำแนะนำล่าสุดจากแพทย์');
$nextAppointmentDetail = usemed_visit_field($latestVisit, 'next_appointment_detail', (string) ($patient['next_appointment'] ?? '-'));
$followupDate = usemed_visit_field($latestVisit, 'followup_date', '-');

page_start('Patient Portal', 'patient', 'portal');

topbar(
    'Patient Portal',
    'ภาพรวมสุขภาพ ประวัติการรักษา เอกสาร และนัดหมายของคุณ'
);
?>

<section class="stat-grid">
    <?php stat_card('HN', $patient['hn'] ?? 'HN0001', 'Patient ID'); ?>
    <?php stat_card('Visits', (string) count($visits), 'Treatment'); ?>
    <?php stat_card('Documents', (string) count($documents), 'Medical Files'); ?>
    <?php stat_card('Risk Level', (string) $riskLevel, 'AI Risk'); ?>
</section>

<section class="grid grid-2">
    <div class="card">
        <h2>ข้อมูลของฉัน</h2>
        <p class="text-muted">
            ข้อมูลพื้นฐานของผู้ป่วยที่เชื่อมกับบัญชี Patient Portal
        </p>

        <div class="document-grid mt-2">
            <div class="document-card">
                <div>
                    <strong><?= e($patient['full_name'] ?? '-') ?></strong>
                    <span>HN: <?= e($patient['hn'] ?? '-') ?></span>
                </div>
                <span class="badge <?= e($riskBadge) ?>">
                    <?= e($riskLevel) ?>
                </span>
            </div>

            <div class="document-card">
                <div>
                    <strong>อายุ / เพศ</strong>
                    <span><?= e($patient['age'] ?? '-') ?> ปี / <?= e($patient['gender'] ?? '-') ?></span>
                </div>
                <span class="badge blue">Profile</span>
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
                    <strong>นัดหมายถัดไป</strong>
                    <span><?= e($patient['next_appointment'] ?? '-') ?></span>
                </div>
                <span class="badge green">Follow-up</span>
            </div>
        </div>
    </div>

    <div class="risk-card">
        <div class="risk-score">
            <div>
                <span class="badge <?= e($riskBadge) ?>">
                    Risk <?= e($riskLevel) ?>
                </span>

                <h2 style="margin:12px 0 6px;">
                    คะแนนล่าสุด <?= e($riskScore) ?>/100
                </h2>

                <p class="text-muted">
                    อ้างอิงจากข้อมูล Visit ล่าสุดในระบบ USE MED
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
            <li>ติดตามประวัติการรักษาทั้งหมด <?= e(count($visits)) ?> รายการ</li>
            <li>มีเอกสารสุขภาพ <?= e(count($documents)) ?> รายการ</li>
            <li>โรคประจำตัว: <?= e($patient['disease'] ?? '-') ?></li>
        </ul>
    </div>
</section>

<section class="grid grid-3 mt-2">
    <a class="card" href="<?= e(app_url('patient/timeline.php')) ?>">
        <h3>Timeline</h3>
        <p>ดูประวัติการรักษาเรียงตามเวลา</p>
    </a>

    <a class="card" href="<?= e(app_url('patient/documents.php')) ?>">
        <h3>Documents</h3>
        <p>เปิดดูเอกสารสุขภาพ ผลตรวจ และใบนัด</p>
    </a>

    <a class="card" href="<?= e(app_url('support.php')) ?>">
        <h3>Support</h3>
        <p>แจ้งปัญหาข้อมูลหรือเอกสารไม่ถูกต้อง</p>
    </a>
</section>

<section class="grid grid-2 mt-2">
    <div class="table-card">
        <div class="topbar">
            <div>
                <h1>Visit ล่าสุด</h1>
                <p>ประวัติการรักษาล่าสุดของคุณ</p>
            </div>
        </div>

        <?php if (empty($visits)): ?>
            <?php render_empty_state('ยังไม่มีประวัติการรักษา', 'เมื่อมีการบันทึก Visit รายการจะแสดงที่นี่'); ?>
        <?php else: ?>
            <div class="document-grid">
                <?php foreach (array_slice($visits, 0, 3) as $visit): ?>
                    <?php
                    $id = (int) ($visit['id'] ?? 1);
                    $date = $visit['date'] ?? $visit['visit_date'] ?? '-';
                    $title = $visit['title'] ?? '-';
                    $doctorName = $visit['doctor'] ?? $visit['doctor_name'] ?? 'นพ.กิตติ ภัทรเวช';
                    $summary = $visit['summary'] ?? $visit['diagnosis'] ?? '-';
                    $risk = $visit['risk'] ?? $visit['risk_level'] ?? 'Medium';
                    $badge = badge_class((string) $risk);
                    ?>

                    <a class="document-card" href="<?= e(app_url('patient/visit-detail.php?id=' . $id)) ?>">
                        <div>
                            <strong><?= e($title) ?></strong>
                            <span><?= e($date) ?> · <?= e($doctorName) ?></span>
                            <span><?= e($summary) ?></span>
                        </div>
                        <span class="badge <?= e($badge) ?>">
                            <?= e($risk) ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="btn-row mt-2">
                <a class="btn secondary" href="<?= e(app_url('patient/timeline.php')) ?>">
                    ดู Timeline ทั้งหมด
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="table-card">
        <div class="topbar">
            <div>
                <h1>เอกสารล่าสุด</h1>
                <p>เอกสารสุขภาพของคุณ</p>
            </div>
        </div>

        <?php if (empty($documents)): ?>
            <?php render_empty_state('ยังไม่มีเอกสาร', 'เมื่อมีเอกสาร รายการจะแสดงที่นี่'); ?>
        <?php else: ?>
            <div class="document-grid">
                <?php foreach (array_slice($documents, 0, 3) as $doc): ?>
                    <?php
                    $id = (int) ($doc['id'] ?? 1);
                    $title = $doc['title'] ?? 'เอกสารสุขภาพ';
                    $type = $doc['document_type'] ?? $doc['type'] ?? 'PDF';
                    $date = $doc['created_at'] ?? $doc['date'] ?? '-';
                    ?>

                    <a class="document-card" href="<?= e(app_url('patient/document-view.php?id=' . $id)) ?>">
                        <div style="display:flex;align-items:center;gap:14px;">
                            <div class="document-icon">📄</div>
                            <div>
                                <strong><?= e($title) ?></strong>
                                <span><?= e($date) ?></span>
                            </div>
                        </div>
                        <span class="badge blue"><?= e($type) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="btn-row mt-2">
                <a class="btn secondary" href="<?= e(app_url('patient/documents.php')) ?>">
                    ดูเอกสารทั้งหมด
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="grid grid-2 mt-2">
    <div class="card">
        <h2>ข้อมูลให้ความรู้และนัดหมาย</h2>
        <p class="text-muted">ข้อมูลนี้มาจาก Visit ล่าสุดที่แพทย์บันทึกไว้</p>

        <div class="document-grid mt-2">
            <div class="document-card">
                <div>
                    <strong>คำแนะนำจากแพทย์</strong>
                    <span><?= e($educationText) ?></span>
                </div>
                <span class="badge green">Education</span>
            </div>
            <div class="document-card">
                <div>
                    <strong>นัดหมายเพิ่มเติม</strong>
                    <span><?= e($nextAppointmentDetail) ?></span>
                </div>
                <span class="badge orange">Appointment</span>
            </div>
            <div class="document-card">
                <div>
                    <strong>วันนัดติดตาม</strong>
                    <span><?= e($followupDate) ?></span>
                </div>
                <span class="badge blue">Follow-up</span>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>สิทธิการรักษาที่เคยใช้</h2>
        <p class="text-muted">ดูว่าครั้งล่าสุดใช้สิทธิ/ประกันหรือชำระเงินแบบใด</p>

        <div class="document-grid mt-2">
            <div class="document-card">
                <div>
                    <strong>สิทธิ / วิธีชำระเงินล่าสุด</strong>
                    <span><?= e($paymentMethod) ?></span>
                </div>
                <span class="badge green">Payment</span>
            </div>
            <div class="document-card">
                <div>
                    <strong>รายละเอียดสิทธิ/ประกัน</strong>
                    <span><?= e($insuranceDetail) ?></span>
                </div>
                <span class="badge blue">Insurance</span>
            </div>
            <div class="document-card">
                <div>
                    <strong>โรงพยาบาลที่ใช้บริการ</strong>
                    <span><?= e($visitHospital) ?></span>
                </div>
                <span class="badge orange">Hospital</span>
            </div>
        </div>
    </div>
</section>

<?php
page_end();