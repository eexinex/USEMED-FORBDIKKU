<?php
// public/doctor/timeline.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('doctor');

$hn = trim($_GET['hn'] ?? 'HN0001');

$patient = demo_patient();
$visits = demo_visits();

if (db_is_connected()) {
    $patientRow = db_fetch_one(
        'SELECT * FROM patients WHERE hn = :hn LIMIT 1',
        ['hn' => $hn]
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
             ORDER BY v.visit_date DESC, v.id DESC',
            ['patient_id' => (int) $patientRow['id']]
        );

        if (!empty($dbVisits)) {
            $visits = $dbVisits;
        }
    }
}

$latestVisit = $visits[0] ?? [];
$riskLevel = $latestVisit['risk'] ?? $latestVisit['risk_level'] ?? $patient['risk_level'] ?? 'Medium';
$riskScore = $latestVisit['risk_score'] ?? 62;
$riskBadge = badge_class((string) $riskLevel);

page_start('Timeline ผู้ป่วย', 'doctor', 'patient');

topbar(
    'Patient Timeline',
    'ประวัติการรักษาแบบเรียงตามเวลา สำหรับแพทย์ดูภาพรวมผู้ป่วย'
);
?>

<section class="stat-grid">
    <?php stat_card('ผู้ป่วย', $patient['hn'] ?? 'HN0001', $patient['full_name'] ?? 'Patient'); ?>
    <?php stat_card('จำนวน Visit', (string) count($visits), 'Timeline'); ?>
    <?php stat_card('Risk ล่าสุด', (string) $riskLevel, 'AI Risk'); ?>
    <?php stat_card('นัดหมายถัดไป', $patient['next_appointment'] ?? '-', 'Follow-up'); ?>
</section>

<section class="grid grid-2">
    <div class="card">
        <h2>ข้อมูลผู้ป่วย</h2>
        <p class="text-muted">
            Timeline นี้แสดงลำดับการรักษา การวินิจฉัย และผลประเมินความเสี่ยงของผู้ป่วย
        </p>

        <div class="document-grid mt-2">
            <div class="document-card">
                <div>
                    <strong><?= e($patient['full_name'] ?? '-') ?></strong>
                    <span>HN: <?= e($patient['hn'] ?? '-') ?></span>
                </div>
                <span class="badge <?= e($riskBadge) ?>"><?= e($riskLevel) ?></span>
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
                    อ้างอิงจาก Visit ล่าสุดของผู้ป่วยในระบบ
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
            <li>มีประวัติการรักษาทั้งหมด <?= e(count($visits)) ?> รายการ</li>
            <li>โรคประจำตัว: <?= e($patient['disease'] ?? '-') ?></li>
            <li>ติดตามต่อเนื่องตามนัดหมาย: <?= e($patient['next_appointment'] ?? '-') ?></li>
        </ul>
    </div>
</section>

<section class="card mt-2">
    <div class="topbar">
        <div>
            <h1>Timeline การรักษา</h1>
            <p>เรียงจากรายการล่าสุดไปเก่าสุด</p>
        </div>

        <div class="btn-row">
            <a class="btn secondary" href="<?= e(app_url('doctor/patient-profile.php?hn=' . urlencode($patient['hn'] ?? $hn))) ?>">
                กลับโปรไฟล์
            </a>

            <a class="btn" href="<?= e(app_url('doctor/add-treatment.php')) ?>">
                เพิ่มการรักษา
            </a>
        </div>
    </div>

    <?php if (empty($visits)): ?>
        <?php render_empty_state('ยังไม่มี Timeline', 'เมื่อมีการบันทึกการรักษา Timeline จะแสดงที่นี่'); ?>
    <?php else: ?>
        <div class="timeline mt-2">
            <?php foreach ($visits as $visit): ?>
                <?php
                $id = (int) ($visit['id'] ?? 1);
                $date = $visit['date'] ?? $visit['visit_date'] ?? '-';
                $title = $visit['title'] ?? '-';
                $doctorName = $visit['doctor'] ?? $visit['doctor_name'] ?? 'นพ.กิตติ ภัทรเวช';
                $diagnosis = $visit['diagnosis'] ?? '-';
                $summary = $visit['summary'] ?? $visit['treatment_plan'] ?? $diagnosis;
                $risk = $visit['risk'] ?? $visit['risk_level'] ?? 'Medium';
                $score = $visit['risk_score'] ?? 62;
                $badge = badge_class((string) $risk);
                ?>

                <div class="timeline-item">
                    <div class="timeline-head">
                        <div>
                            <strong><?= e($title) ?></strong>
                            <div class="text-muted">
                                โดย <?= e($doctorName) ?>
                            </div>
                        </div>

                        <span><?= e($date) ?></span>
                    </div>

                    <p>
                        <?= e($summary) ?>
                    </p>

                    <div class="document-grid mt-2">
                        <div class="document-card">
                            <div>
                                <strong>Diagnosis</strong>
                                <span><?= e($diagnosis) ?></span>
                            </div>
                            <span class="badge blue">Dx</span>
                        </div>

                        <div class="document-card">
                            <div>
                                <strong>AI Risk</strong>
                                <span>Score <?= e($score) ?>/100</span>
                            </div>
                            <span class="badge <?= e($badge) ?>"><?= e($risk) ?></span>
                        </div>
                    </div>

                    <div class="btn-row mt-2">
                        <a class="btn secondary" href="<?= e(app_url('doctor/visit-detail.php?id=' . $id)) ?>">
                            เปิดรายละเอียด
                        </a>

                        <a class="btn secondary" href="<?= e(app_url('doctor/documents.php')) ?>">
                            ดูเอกสาร
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="grid grid-3 mt-2">
    <a class="card" href="<?= e(app_url('doctor/dashboard.php')) ?>">
        <h3>Dashboard</h3>
        <p>กลับหน้าภาพรวมแพทย์</p>
    </a>

    <a class="card" href="<?= e(app_url('doctor/ai-risk.php')) ?>">
        <h3>AI Risk</h3>
        <p>ประเมินความเสี่ยงผู้ป่วย</p>
    </a>

    <a class="card" href="<?= e(app_url('doctor/documents.php')) ?>">
        <h3>Documents</h3>
        <p>ดูเอกสารสุขภาพของผู้ป่วย</p>
    </a>
</section>

<?php
page_end();