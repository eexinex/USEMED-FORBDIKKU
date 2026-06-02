<?php
// public/doctor/dashboard.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('doctor');
usemed_ensure_extended_schema();
usemed_seed_demo_data();

$doctor = current_user();
$demoPatients = demo_patients();
$patient = demo_patient();
$visits = demo_visits();
$documents = demo_documents();
$referrals = demo_referrals();

$patientCount = count($demoPatients);
$visitCount = count($visits);
$documentCount = count($documents);
$highRiskCount = demo_patient_flow_summary($demoPatients)['คนไข้เฝ้าระวังสูง'];
$latestPatients = $demoPatients;
$flowSummary = demo_patient_flow_summary($demoPatients);

if (db_is_connected()) {
    $patientRow = db_fetch_one('SELECT COUNT(*) AS total FROM patients');
    $visitRow = db_fetch_one('SELECT COUNT(*) AS total FROM visits');
    $documentRow = db_fetch_one('SELECT COUNT(*) AS total FROM documents');
    $highRiskRow = db_fetch_one("SELECT COUNT(*) AS total FROM patients WHERE high_watch = 1 OR care_area = 'คนไข้เฝ้าระวังสูง'");

    $patientCount = (int) ($patientRow['total'] ?? 0);
    $visitCount = (int) ($visitRow['total'] ?? 0);
    $documentCount = (int) ($documentRow['total'] ?? 0);
    $highRiskCount = (int) ($highRiskRow['total'] ?? 0);

    $dbPatients = db_fetch_all('SELECT * FROM patients ORDER BY created_at DESC, id DESC LIMIT 10');
    if (!empty($dbPatients)) {
        $latestPatients = $dbPatients;
    }

    $flowSummary = [
        'OPD' => (int) (db_fetch_one("SELECT COUNT(*) AS total FROM patients WHERE care_area = 'OPD'")['total'] ?? 0),
        'IPD' => (int) (db_fetch_one("SELECT COUNT(*) AS total FROM patients WHERE care_area = 'IPD'")['total'] ?? 0),
        'ICU' => (int) (db_fetch_one("SELECT COUNT(*) AS total FROM patients WHERE care_area = 'ICU'")['total'] ?? 0),
        'ผ่าตัด' => (int) (db_fetch_one("SELECT COUNT(*) AS total FROM patients WHERE care_area = 'ผ่าตัด'")['total'] ?? 0),
        'คิวผ่าตัด' => (int) (db_fetch_one("SELECT COUNT(*) AS total FROM patients WHERE care_area = 'คิวผ่าตัด'")['total'] ?? 0),
        'คนไข้เฝ้าระวังสูง' => $highRiskCount,
    ];

    $dbVisits = db_fetch_all(
        'SELECT v.*, p.hn, p.full_name
         FROM visits v
         INNER JOIN patients p ON p.id = v.patient_id
         ORDER BY v.visit_date DESC, v.id DESC
         LIMIT 5'
    );
    if (!empty($dbVisits)) {
        $visits = $dbVisits;
    }

    $dbReferrals = db_fetch_all(
        'SELECT r.*, p.hn, p.full_name AS patient_name
         FROM referrals r
         LEFT JOIN patients p ON p.id = r.patient_id
         ORDER BY r.created_at DESC, r.id DESC
         LIMIT 5'
    );
    if (!empty($dbReferrals)) {
        $referrals = $dbReferrals;
    }
}

$flowIcons = [
    'OPD' => '🏥',
    'IPD' => '🛏️',
    'ICU' => '🚨',
    'ผ่าตัด' => '🔪',
    'คิวผ่าตัด' => '📋',
    'คนไข้เฝ้าระวังสูง' => '⚠️',
];

page_start('Doctor Dashboard', 'doctor', 'dashboard');
?>

<div style="max-width: 1200px; margin: 0 auto; padding: 24px 16px;">
    
    <!-- Hero / Welcome Card -->
    <div class="card" style="padding: 32px; margin-bottom: 24px; border-left: 6px solid var(--primary); background: #ffffff;">
        <h1 style="margin: 0 0 8px; font-size: 28px; color: var(--ink);">สวัสดี นพ.<?= e($doctor['first_name'] ?? 'กิตติ') ?> 👋</h1>
        <p style="margin: 0; color: var(--muted); font-size: 16px;">ภาพรวมประจำวัน: มีคนไข้เฝ้าระวังสูง <strong style="color: var(--primary-dark);"><?= e((string)$highRiskCount) ?> ราย</strong> ที่ต้องการความสนใจเป็นพิเศษ</p>
    </div>

    <!-- Prominent AI Population Health Link -->
    <a href="<?= e(app_url('doctor/population-health.php')) ?>" class="card" style="display: block; text-decoration: none; padding: 24px; margin-bottom: 24px; background: var(--bg); border: 1px solid var(--primary); transition: transform 0.2s ease;">
        <div style="display: flex; gap: 20px; align-items: center;">
            <div style="width: 64px; height: 64px; border-radius: 16px; background: var(--primary); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 28px;">✦</div>
            <div>
                <h2 style="margin: 0 0 4px; font-size: 22px; color: var(--primary-dark);">เข้าสู่ระบบ AI Population Health</h2>
                <p style="margin: 0; color: var(--muted); font-size: 15px;">จัดอันดับผู้ป่วยที่ควรติดตามก่อน พร้อมวิเคราะห์กลุ่มผู้ป่วยด้วย AI เพื่อวางแผนทรัพยากรการแพทย์</p>
            </div>
            <div style="margin-left: auto; color: var(--primary); font-size: 24px; font-weight: bold;">›</div>
        </div>
    </a>

    <!-- Stats Row -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card" style="padding: 20px;">
            <span style="color: var(--muted); font-size: 14px;">ผู้ป่วยในระบบ</span>
            <strong style="display: block; font-size: 32px; color: var(--ink); margin-top: 8px;"><?= e((string)$patientCount) ?></strong>
        </div>
        <div class="card" style="padding: 20px;">
            <span style="color: var(--muted); font-size: 14px;">การเข้าตรวจทั้งหมด</span>
            <strong style="display: block; font-size: 32px; color: var(--ink); margin-top: 8px;"><?= e((string)$visitCount) ?></strong>
        </div>
        <div class="card" style="padding: 20px;">
            <span style="color: var(--muted); font-size: 14px;">ผู้ป่วยเฝ้าระวังสูง (High Watch)</span>
            <strong style="display: block; font-size: 32px; color: #e11d48; margin-top: 8px;"><?= e((string)$highRiskCount) ?></strong>
        </div>
    </div>

    <!-- Two-column main layout -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
        
        <!-- Left Column: Patient Flow -->
        <div class="card">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--line);">
                <h2 style="margin: 0; font-size: 18px; color: var(--ink);">🏥 ปริมาณผู้ป่วยตามจุดบริการ</h2>
            </div>
            <div style="padding: 20px; display: grid; gap: 12px;">
                <?php foreach ($flowSummary as $label => $count): ?>
                    <a href="<?= e(app_url('doctor/care-list.php?type=' . urlencode(usemed_care_type_key_from_label((string) $label)))) ?>" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: var(--bg); border: 1px solid var(--line); border-radius: 12px; text-decoration: none; color: var(--ink);">
                        <strong style="font-size: 15px;"><?= e(($flowIcons[$label] ?? '🏥') . ' ' . $label) ?></strong>
                        <span class="badge" style="background: white; border: 1px solid var(--line);"><?= e((string) $count) ?> ราย</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right Column: Quick Actions -->
        <div class="card">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--line);">
                <h2 style="margin: 0; font-size: 18px; color: var(--ink);">⚡ เมนูจัดการด่วน</h2>
            </div>
            <div style="padding: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <a href="<?= e(app_url('doctor/register-patient.php')) ?>" class="btn secondary" style="display: block; text-align: left; padding: 12px 16px;">
                    <strong style="display: block; color: var(--ink); margin-bottom: 4px;">ลงทะเบียนผู้ป่วย</strong>
                    <small style="color: var(--muted); font-size: 12px;">เพิ่มข้อมูลผู้ป่วยใหม่</small>
                </a>
                <a href="<?= e(app_url('doctor/add-treatment.php')) ?>" class="btn secondary" style="display: block; text-align: left; padding: 12px 16px;">
                    <strong style="display: block; color: var(--ink); margin-bottom: 4px;">เพิ่มการรักษา</strong>
                    <small style="color: var(--muted); font-size: 12px;">บันทึกผลตรวจ/วินิจฉัย</small>
                </a>
                <a href="<?= e(app_url('doctor/ems-handover.php')) ?>" class="btn secondary" style="display: block; text-align: left; padding: 12px 16px;">
                    <strong style="display: block; color: var(--ink); margin-bottom: 4px;">รับเคส EMS</strong>
                    <small style="color: var(--muted); font-size: 12px;">ส่งมอบ Medical/Trauma</small>
                </a>
                <a href="<?= e(app_url('doctor/prescriptions.php')) ?>" class="btn secondary" style="display: block; text-align: left; padding: 12px 16px;">
                    <strong style="display: block; color: var(--ink); margin-bottom: 4px;">ระบบจ่ายยา</strong>
                    <small style="color: var(--muted); font-size: 12px;">พิมพ์ใบสั่งยา/Export</small>
                </a>
            </div>
        </div>

    </div>

    <!-- Recent Visits Table -->
    <div class="table-card mt-2">
        <div class="topbar" style="padding: 16px 20px; border-bottom: 1px solid var(--line);">
            <h2 style="margin: 0; font-size: 18px; color: var(--ink);">ประวัติการรักษาล่าสุด</h2>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>วันที่</th>
                        <th>ผู้ป่วย</th>
                        <th>หัวข้อ</th>
                        <th>Risk</th>
                        <th style="width: 80px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($visits)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 24px; color: var(--muted);">ยังไม่มีประวัติการรักษา</td></tr>
                    <?php else: ?>
                        <?php foreach ($visits as $visit): ?>
                            <?php
                            $risk = $visit['risk'] ?? $visit['risk_level'] ?? 'Medium';
                            $date = $visit['date'] ?? $visit['visit_date'] ?? '-';
                            $patientName = $visit['full_name'] ?? $patient['full_name'];
                            ?>
                            <tr>
                                <td><?= e($date) ?></td>
                                <td><strong style="color: var(--ink);"><?= e($patientName) ?></strong></td>
                                <td style="color: var(--muted);"><?= e($visit['title'] ?? '-') ?></td>
                                <td><span class="badge <?= e(badge_class((string) $risk)) ?>"><?= e($risk) ?></span></td>
                                <td><a class="btn secondary small" href="<?= e(app_url('doctor/visit-detail.php')) ?>">เปิด</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
