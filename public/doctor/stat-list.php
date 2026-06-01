<?php
// public/doctor/stat-list.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('doctor');

$patientCount = 1;
$doctorCount = 1;
$visitCount = count(demo_visits());
$documentCount = count(demo_documents());
$highRiskCount = 0;
$mediumRiskCount = 2;
$lowRiskCount = 1;

$rows = [
    [
        'label' => 'ผู้ป่วยทั้งหมด',
        'value' => $patientCount,
        'type' => 'Patient',
        'status' => 'Active',
        'color' => 'blue',
    ],
    [
        'label' => 'แพทย์ในระบบ',
        'value' => $doctorCount,
        'type' => 'Doctor',
        'status' => 'Active',
        'color' => 'green',
    ],
    [
        'label' => 'ประวัติการรักษา',
        'value' => $visitCount,
        'type' => 'Visit',
        'status' => 'Updated',
        'color' => 'orange',
    ],
    [
        'label' => 'เอกสารสุขภาพ',
        'value' => $documentCount,
        'type' => 'Document',
        'status' => 'Ready',
        'color' => 'blue',
    ],
    [
        'label' => 'High Risk',
        'value' => $highRiskCount,
        'type' => 'AI Risk',
        'status' => 'Alert',
        'color' => 'red',
    ],
    [
        'label' => 'Medium Risk',
        'value' => $mediumRiskCount,
        'type' => 'AI Risk',
        'status' => 'Monitor',
        'color' => 'orange',
    ],
    [
        'label' => 'Low Risk',
        'value' => $lowRiskCount,
        'type' => 'AI Risk',
        'status' => 'Stable',
        'color' => 'green',
    ],
];

$visits = demo_visits();

if (db_is_connected()) {
    $patientRow = db_fetch_one('SELECT COUNT(*) AS total FROM patients');
    $doctorRow = db_fetch_one('SELECT COUNT(*) AS total FROM doctors');
    $visitRow = db_fetch_one('SELECT COUNT(*) AS total FROM visits');
    $documentRow = db_fetch_one('SELECT COUNT(*) AS total FROM documents');
    $highRiskRow = db_fetch_one("SELECT COUNT(*) AS total FROM visits WHERE risk_level = 'High'");
    $mediumRiskRow = db_fetch_one("SELECT COUNT(*) AS total FROM visits WHERE risk_level = 'Medium'");
    $lowRiskRow = db_fetch_one("SELECT COUNT(*) AS total FROM visits WHERE risk_level = 'Low'");

    $patientCount = (int) ($patientRow['total'] ?? 0);
    $doctorCount = (int) ($doctorRow['total'] ?? 0);
    $visitCount = (int) ($visitRow['total'] ?? 0);
    $documentCount = (int) ($documentRow['total'] ?? 0);
    $highRiskCount = (int) ($highRiskRow['total'] ?? 0);
    $mediumRiskCount = (int) ($mediumRiskRow['total'] ?? 0);
    $lowRiskCount = (int) ($lowRiskRow['total'] ?? 0);

    $rows = [
        [
            'label' => 'ผู้ป่วยทั้งหมด',
            'value' => $patientCount,
            'type' => 'Patient',
            'status' => 'Active',
            'color' => 'blue',
        ],
        [
            'label' => 'แพทย์ในระบบ',
            'value' => $doctorCount,
            'type' => 'Doctor',
            'status' => 'Active',
            'color' => 'green',
        ],
        [
            'label' => 'ประวัติการรักษา',
            'value' => $visitCount,
            'type' => 'Visit',
            'status' => 'Updated',
            'color' => 'orange',
        ],
        [
            'label' => 'เอกสารสุขภาพ',
            'value' => $documentCount,
            'type' => 'Document',
            'status' => 'Ready',
            'color' => 'blue',
        ],
        [
            'label' => 'High Risk',
            'value' => $highRiskCount,
            'type' => 'AI Risk',
            'status' => 'Alert',
            'color' => 'red',
        ],
        [
            'label' => 'Medium Risk',
            'value' => $mediumRiskCount,
            'type' => 'AI Risk',
            'status' => 'Monitor',
            'color' => 'orange',
        ],
        [
            'label' => 'Low Risk',
            'value' => $lowRiskCount,
            'type' => 'AI Risk',
            'status' => 'Stable',
            'color' => 'green',
        ],
    ];

    $dbVisits = db_fetch_all(
        'SELECT 
            v.*,
            p.hn,
            p.full_name
         FROM visits v
         INNER JOIN patients p ON p.id = v.patient_id
         ORDER BY v.visit_date DESC, v.id DESC
         LIMIT 10'
    );

    if (!empty($dbVisits)) {
        $visits = $dbVisits;
    }
}

page_start('สถิติระบบ', 'doctor', 'dashboard');

topbar(
    'Statistics List',
    'สรุปจำนวนข้อมูลผู้ป่วย การรักษา เอกสาร และ AI Risk'
);
?>

<section class="stat-grid">
    <?php stat_card('ผู้ป่วย', (string) $patientCount, 'Patients'); ?>
    <?php stat_card('Visits', (string) $visitCount, 'Treatment'); ?>
    <?php stat_card('Documents', (string) $documentCount, 'Medical Files'); ?>
    <?php stat_card('High Risk', (string) $highRiskCount, 'AI Alert'); ?>
</section>

<section class="grid grid-2">
    <div class="card">
        <h2>ภาพรวมข้อมูล</h2>
        <p class="text-muted">
            ตารางนี้ใช้ดูจำนวนข้อมูลหลักในระบบ USE MED สำหรับฝั่งแพทย์
        </p>

        <div class="document-grid mt-2">
            <div class="document-card">
                <div>
                    <strong>Database Status</strong>
                    <span><?= db_is_connected() ? 'เชื่อมต่อฐานข้อมูลแล้ว' : 'กำลังใช้งาน Demo Mode' ?></span>
                </div>
                <span class="badge <?= db_is_connected() ? 'green' : 'orange' ?>">
                    <?= db_is_connected() ? 'Connected' : 'Demo' ?>
                </span>
            </div>

            <div class="document-card">
                <div>
                    <strong>Risk Engine</strong>
                    <span>Rule-based AI Assessment</span>
                </div>
                <span class="badge blue">AI</span>
            </div>

            <div class="document-card">
                <div>
                    <strong>Doctor</strong>
                    <span><?= e(current_user()['name'] ?? 'Doctor') ?></span>
                </div>
                <span class="badge green">Online</span>
            </div>
        </div>
    </div>

    <div class="risk-card">
        <div class="risk-score">
            <div>
                <span class="badge orange">Risk Summary</span>

                <h2 style="margin:12px 0 6px;">
                    <?= e($highRiskCount) ?> High Risk
                </h2>

                <p class="text-muted">
                    จำนวนผู้ป่วยหรือ Visit ที่อยู่ในระดับความเสี่ยงสูง
                </p>
            </div>

            <div class="score-circle" style="--value:<?= e(min(100, ($highRiskCount + $mediumRiskCount + $lowRiskCount) * 20)) ?>">
                <strong><?= e($highRiskCount) ?></strong>
            </div>
        </div>

        <div class="mt-2">
            <div class="riskbar">
                <span style="width:<?= e(min(100, ($highRiskCount + $mediumRiskCount + $lowRiskCount) * 20)) ?>%"></span>
            </div>
        </div>

        <ul class="factor-list">
            <li>High Risk: <?= e($highRiskCount) ?> รายการ</li>
            <li>Medium Risk: <?= e($mediumRiskCount) ?> รายการ</li>
            <li>Low Risk: <?= e($lowRiskCount) ?> รายการ</li>
        </ul>
    </div>
</section>

<section class="table-card mt-2">
    <div class="topbar">
        <div>
            <h1>รายการสถิติ</h1>
            <p>สรุปข้อมูลระบบแยกตามหมวดหมู่</p>
        </div>

        <div class="searchbar">
            <input
                type="search"
                data-table-search="statTable"
                placeholder="ค้นหาสถิติ..."
            >
        </div>
    </div>

    <div class="table-wrap">
        <table class="table" id="statTable">
            <thead>
                <tr>
                    <th>หมวดหมู่</th>
                    <th>จำนวน</th>
                    <th>ประเภท</th>
                    <th>สถานะ</th>
                    <th>รายละเอียด</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td>
                            <strong><?= e($row['label']) ?></strong>
                        </td>
                        <td><?= e($row['value']) ?></td>
                        <td><?= e($row['type']) ?></td>
                        <td>
                            <span class="badge <?= e($row['color']) ?>">
                                <?= e($row['status']) ?>
                            </span>
                        </td>
                        <td>
                            ข้อมูลหมวด <?= e($row['label']) ?> ในระบบ USE MED
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="table-card mt-2">
    <div class="topbar">
        <div>
            <h1>Visit ล่าสุด</h1>
            <p>ข้อมูลล่าสุดที่ใช้ประกอบสถิติ</p>
        </div>

        <div class="searchbar">
            <input
                type="search"
                data-table-search="recentVisitTable"
                placeholder="ค้นหา visit..."
            >
        </div>
    </div>

    <?php if (empty($visits)): ?>
        <?php render_empty_state('ยังไม่มี Visit', 'เมื่อมีการบันทึกการรักษา รายการจะแสดงที่นี่'); ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table" id="recentVisitTable">
                <thead>
                    <tr>
                        <th>วันที่</th>
                        <th>ผู้ป่วย</th>
                        <th>หัวข้อ</th>
                        <th>วินิจฉัย</th>
                        <th>Risk</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($visits as $visit): ?>
                        <?php
                        $date = $visit['date'] ?? $visit['visit_date'] ?? '-';
                        $patientName = $visit['full_name'] ?? demo_patient()['full_name'];
                        $hn = $visit['hn'] ?? demo_patient()['hn'];
                        $title = $visit['title'] ?? '-';
                        $diagnosis = $visit['summary'] ?? $visit['diagnosis'] ?? '-';
                        $risk = $visit['risk'] ?? $visit['risk_level'] ?? 'Medium';
                        $badge = badge_class((string) $risk);
                        ?>
                        <tr>
                            <td><?= e($date) ?></td>
                            <td>
                                <strong><?= e($patientName) ?></strong><br>
                                <span class="text-muted"><?= e($hn) ?></span>
                            </td>
                            <td><?= e($title) ?></td>
                            <td><?= e($diagnosis) ?></td>
                            <td>
                                <span class="badge <?= e($badge) ?>">
                                    <?= e($risk) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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

    <a class="card" href="<?= e(app_url('doctor/icu.php')) ?>">
        <h3>ICU Monitor</h3>
        <p>ดูหน้าจำลองผู้ป่วย ICU</p>
    </a>
</section>

<?php
page_end();