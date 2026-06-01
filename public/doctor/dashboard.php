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

topbar(
    'Doctor Dashboard',
    'ภาพรวม OPD / IPD / ICU / ผ่าตัด / คิวผ่าตัด / ส่งต่อแผนกและโรงพยาบาล'
);
?>

<section class="stat-grid">
    <?php stat_card('ผู้ป่วยในระบบ', (string) $patientCount, 'Patients'); ?>
    <?php stat_card('ประวัติการรักษา', (string) $visitCount, 'Visits'); ?>
    <?php stat_card('เอกสารสุขภาพ', (string) $documentCount, 'Documents'); ?>
    <?php stat_card('เฝ้าระวังสูง', (string) $highRiskCount, 'High Watch'); ?>
</section>

<section class="grid grid-3" id="patient-flow">
    <?php foreach ($flowSummary as $label => $count): ?>
        <a class="card care-flow-card" href="<?= e(app_url('doctor/care-list.php?type=' . urlencode(usemed_care_type_key_from_label((string) $label)))) ?>">
            <h3><?= e(($flowIcons[$label] ?? '🏥') . ' ' . $label) ?></h3>
            <p>เปิดดูรายชื่อผู้ป่วยในกลุ่มนี้เพื่อ follow up</p>
            <div class="mt-1">
                <span class="badge <?= e($label === 'ICU' || $label === 'คนไข้เฝ้าระวังสูง' ? 'red' : ($label === 'คิวผ่าตัด' || $label === 'ผ่าตัด' ? 'orange' : 'blue')) ?>">
                    <?= e((string) $count) ?> ราย
                </span>
            </div>
        </a>
    <?php endforeach; ?>
</section>

<section class="grid grid-2 mt-2">
    <div class="card">
        <h2>Quick Actions</h2>
        <p class="text-muted">เมนูลัดสำหรับจัดการผู้ป่วย การรักษา เอกสาร AI และการส่งต่อ</p>

        <div class="document-grid mt-2">
            <a class="document-card" href="<?= e(app_url('doctor/patient-profile.php')) ?>">
                <div><strong>ดูข้อมูลผู้ป่วย</strong><span>ค้นหาและดูข้อมูลผู้ป่วย</span></div>
                <span class="badge blue">Profile</span>
            </a>
            <a class="document-card" href="<?= e(app_url('doctor/register-patient.php')) ?>">
                <div><strong>ลงทะเบียนผู้ป่วย</strong><span>เพิ่มข้อมูลผู้ป่วยรายใหม่เข้าสู่ระบบ</span></div>
                <span class="badge green">Register</span>
            </a>
            <a class="document-card" href="<?= e(app_url('doctor/add-treatment.php')) ?>">
                <div><strong>เพิ่มการรักษา</strong><span>บันทึกผลตรวจ วินิจฉัย และแผนการรักษา</span></div>
                <span class="badge orange">Treatment</span>
            </a>
            <a class="document-card" href="<?= e(app_url('doctor/referral.php')) ?>">
                <div><strong>ส่งตัว / ส่งต่อ</strong><span>เลือกแผนก แพทย์ และโรงพยาบาลปลายทาง</span></div>
                <span class="badge red">Refer</span>
            </a>
            <a class="document-card" href="<?= e(app_url('doctor/population-health.php')) ?>">
                <div><strong>AI Population Health</strong><span>จัดอันดับผู้ป่วยที่ควรติดตามก่อน พร้อม cohort และ recommendation</span></div>
                <span class="badge red">AI</span>
            </a>
            <a class="document-card" href="<?= e(app_url('doctor/ems-handover.php')) ?>">
                <div><strong>EMS MIST/SBAR</strong><span>รับเคสจากรถฉุกเฉิน แยก Medical / Trauma</span></div>
                <span class="badge orange">EMS</span>
            </a>
            <a class="document-card" href="<?= e(app_url('doctor/prescriptions.php')) ?>">
                <div><strong>ยา / ใบสั่งยา</strong><span>บันทึกจ่ายยา พิมพ์ใบสั่งยา และ Export Excel</span></div>
                <span class="badge green">Rx</span>
            </a>
            <a class="document-card" href="<?= e(app_url('doctor/progress-note.php')) ?>">
                <div><strong>Progress Note</strong><span>เปิดดู CC, vital signs, height/weight และ note ล่าสุด</span></div>
                <span class="badge blue">Note</span>
            </a>
        </div>
    </div>

    <div class="card">
        <h2>ผู้ป่วย Demo 10 คน</h2>
        <p class="text-muted">ใช้ HN เหล่านี้ทดสอบระบบได้ รหัสผ่านทุกคนคือ 123456</p>

        <div class="document-grid mt-2">
            <?php foreach (array_slice($latestPatients, 0, 5) as $p): ?>
                <?php $risk = (string) ($p['risk_level'] ?? 'Medium'); ?>
                <a class="document-card" href="<?= e(app_url('doctor/patient-profile.php?hn=' . urlencode((string) ($p['hn'] ?? '')))) ?>">
                    <div>
                        <strong><?= e($p['full_name'] ?? '-') ?></strong>
                        <span><?= e($p['hn'] ?? '-') ?> · <?= e($p['care_area'] ?? 'OPD') ?> · <?= e($p['department'] ?? '-') ?></span>
                    </div>
                    <span class="badge <?= e(badge_class($risk)) ?>"><?= e($risk) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="table-card mt-2">
    <div class="topbar">
        <div>
            <h1>ประวัติการรักษาล่าสุด</h1>
            <p>รายการ Visit ล่าสุดของผู้ป่วย</p>
        </div>
        <div class="searchbar"><input type="search" data-table-search="doctorVisits" placeholder="ค้นหาประวัติ..."></div>
    </div>

    <?php if (empty($visits)): ?>
        <?php render_empty_state('ยังไม่มีประวัติการรักษา', 'เมื่อมีการบันทึก Visit รายการจะแสดงที่นี่'); ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table" id="doctorVisits">
                <thead>
                    <tr>
                        <th>วันที่</th>
                        <th>ผู้ป่วย</th>
                        <th>หัวข้อ</th>
                        <th>วินิจฉัย/สรุป</th>
                        <th>Risk</th>
                        <th>ดู</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($visits as $visit): ?>
                        <?php
                        $risk = $visit['risk'] ?? $visit['risk_level'] ?? 'Medium';
                        $date = $visit['date'] ?? $visit['visit_date'] ?? '-';
                        $patientName = $visit['full_name'] ?? $patient['full_name'];
                        $hn = $visit['hn'] ?? $patient['hn'];
                        $summary = $visit['summary'] ?? $visit['diagnosis'] ?? '-';
                        ?>
                        <tr>
                            <td><?= e($date) ?></td>
                            <td><strong><?= e($patientName) ?></strong><br><span class="text-muted"><?= e($hn) ?></span></td>
                            <td><?= e($visit['title'] ?? '-') ?></td>
                            <td><?= e($summary) ?></td>
                            <td><span class="badge <?= e(badge_class((string) $risk)) ?>"><?= e($risk) ?></span></td>
                            <td><a class="btn secondary" href="<?= e(app_url('doctor/visit-detail.php')) ?>">เปิด</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="grid grid-2 mt-2">
    <div class="table-card">
        <div class="topbar">
            <div>
                <h1>รายการส่งต่อ / ส่งตัวล่าสุด</h1>
                <p>แผนก แพทย์ และโรงพยาบาลปลายทาง</p>
            </div>
            <a class="btn" href="<?= e(app_url('doctor/referral.php')) ?>">+ ส่งต่อใหม่</a>
        </div>

        <div class="document-grid mt-2">
            <?php foreach (array_slice($referrals, 0, 5) as $ref): ?>
                <div class="document-card">
                    <div>
                        <strong><?= e(($ref['hn'] ?? '-') . ' · ' . ($ref['patient_name'] ?? '-')) ?></strong>
                        <span><?= e(($ref['to_department'] ?? '-') . ' → ' . ($ref['to_hospital'] ?? '-')) ?></span>
                    </div>
                    <span class="badge <?= e(($ref['urgency'] ?? '') === 'ด่วน' ? 'red' : 'blue') ?>"><?= e($ref['status'] ?? 'รอรับเคส') ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h2>บัญชี Demo หมอ 3 คน</h2>
        <p class="text-muted">รหัสผ่านทุกบัญชี: 123456</p>
        <div class="document-grid mt-2">
            <?php foreach (demo_doctors() as $d): ?>
                <div class="document-card">
                    <div>
                        <strong><?= e($d['full_name']) ?></strong>
                        <span><?= e($d['username']) ?> · <?= e($d['department']) ?> · <?= e($d['hospital']) ?></span>
                    </div>
                    <span class="badge green">Doctor</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="grid grid-3 mt-2">
    <a class="card" href="<?= e(app_url('doctor/documents.php')) ?>">
        <h3>เอกสารผู้ป่วย</h3>
        <p>ดูผลตรวจ ใบนัด และสรุปการรักษา</p>
    </a>
    <a class="card" href="<?= e(app_url('doctor/stat-list.php')) ?>">
        <h3>สถิติระบบ</h3>
        <p>ดูรายการสรุปจำนวนผู้ป่วย Visit และเอกสาร</p>
    </a>
    <a class="card" href="<?= e(app_url('doctor/icu.php')) ?>">
        <h3>ICU Monitor</h3>
        <p>หน้าจำลองติดตามผู้ป่วยวิกฤต</p>
    </a>
</section>

<?php page_end();
