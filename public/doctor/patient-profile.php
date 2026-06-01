<?php
// public/doctor/patient-profile.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('doctor');

$hn = trim($_GET['hn'] ?? 'HN0001');

$patient = demo_patient();
$visits = demo_visits();
$documents = demo_documents();

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

        $dbDocuments = db_fetch_all(
            'SELECT *
             FROM documents
             WHERE patient_id = :patient_id
             ORDER BY created_at DESC, id DESC',
            ['patient_id' => (int) $patientRow['id']]
        );

        if (!empty($dbDocuments)) {
            $documents = $dbDocuments;
        }
    }
}

$latestVisit = $visits[0] ?? [];
$riskLevel = $latestVisit['risk'] ?? $latestVisit['risk_level'] ?? $patient['risk_level'] ?? 'Medium';
$riskBadge = badge_class((string) $riskLevel);

page_start('ข้อมูลผู้ป่วย', 'doctor', 'patient');

topbar(
    'Patient Profile',
    'ค้นหาและดูข้อมูลผู้ป่วย ประวัติการรักษา เอกสาร และความเสี่ยง'
);
?>

<section class="stat-grid">
    <?php stat_card('HN', $patient['hn'] ?? 'HN0001', 'Patient ID'); ?>
    <?php stat_card('อายุ', (string) ($patient['age'] ?? '-') . ' ปี', $patient['gender'] ?? '-'); ?>
    <?php stat_card('Visits', (string) count($visits), 'Treatment'); ?>
    <?php stat_card('Risk Level', (string) $riskLevel, 'AI Risk'); ?>
</section>

<section class="grid grid-2">
    <div class="card">
        <h2>ค้นหาผู้ป่วย</h2>
        <p class="text-muted">
            กรอกเลข HN เพื่อค้นหาข้อมูลผู้ป่วยในระบบ
        </p>

        <form method="get" class="mt-2">
            <div class="field">
                <label for="hn">เลข HN</label>
                <input
                    id="hn"
                    name="hn"
                    type="text"
                    value="<?= e($hn) ?>"
                    placeholder="เช่น HN0001"
                >
            </div>

            <div class="btn-row">
                <button class="btn" type="submit">ค้นหาผู้ป่วย</button>
                <a class="btn secondary" href="<?= e(app_url('doctor/register-patient.php')) ?>">
                    ลงทะเบียนผู้ป่วยใหม่
                </a>
            </div>
        </form>

        <div class="note-box mt-2">
            Demo HN: <strong>HN0001</strong>
        </div>
    </div>

    <div class="card">
        <h2>ข้อมูลส่วนตัว</h2>

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
                    <strong>เบอร์โทร</strong>
                    <span><?= e($patient['phone'] ?? '-') ?></span>
                </div>
                <span class="badge green">Contact</span>
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
                    <strong>ที่อยู่</strong>
                    <span><?= e($patient['address'] ?? '-') ?></span>
                </div>
                <span class="badge orange">Address</span>
            </div>
        </div>
    </div>
</section>

<section class="grid grid-3 mt-2">
    <a class="card" href="<?= e(app_url('doctor/add-treatment.php')) ?>">
        <h3>เพิ่มการรักษา</h3>
        <p>บันทึก Visit ใหม่ วินิจฉัย แผนรักษา และค่า Lab</p>
    </a>

    <a class="card" href="<?= e(app_url('doctor/ai-risk.php')) ?>">
        <h3>AI Risk</h3>
        <p>ประเมินความเสี่ยงจากข้อมูลสุขภาพผู้ป่วย</p>
    </a>

    <a class="card" href="<?= e(app_url('doctor/documents.php')) ?>">
        <h3>เอกสารผู้ป่วย</h3>
        <p>ดูผลตรวจ ใบนัด และสรุปการรักษา</p>
    </a>
</section>

<section class="table-card mt-2">
    <div class="topbar">
        <div>
            <h1>ประวัติการรักษา</h1>
            <p>Visit ล่าสุดของผู้ป่วยรายนี้</p>
        </div>

        <div class="searchbar">
            <input
                type="search"
                data-table-search="patientVisitTable"
                placeholder="ค้นหาประวัติ..."
            >
        </div>
    </div>

    <?php if (empty($visits)): ?>
        <?php render_empty_state('ยังไม่มีประวัติการรักษา', 'เมื่อแพทย์เพิ่มการรักษา รายการจะแสดงที่นี่'); ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table" id="patientVisitTable">
                <thead>
                    <tr>
                        <th>วันที่</th>
                        <th>หัวข้อ</th>
                        <th>แพทย์</th>
                        <th>สรุป / วินิจฉัย</th>
                        <th>Risk</th>
                        <th>เปิด</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($visits as $visit): ?>
                        <?php
                        $date = $visit['date'] ?? $visit['visit_date'] ?? '-';
                        $title = $visit['title'] ?? '-';
                        $doctorName = $visit['doctor'] ?? $visit['doctor_name'] ?? 'นพ.กิตติ ภัทรเวช';
                        $summary = $visit['summary'] ?? $visit['diagnosis'] ?? '-';
                        $risk = $visit['risk'] ?? $visit['risk_level'] ?? 'Medium';
                        $badge = badge_class((string) $risk);
                        ?>
                        <tr>
                            <td><?= e($date) ?></td>
                            <td><?= e($title) ?></td>
                            <td><?= e($doctorName) ?></td>
                            <td><?= e($summary) ?></td>
                            <td>
                                <span class="badge <?= e($badge) ?>">
                                    <?= e($risk) ?>
                                </span>
                            </td>
                            <td>
                                <a class="btn secondary" href="<?= e(app_url('doctor/visit-detail.php')) ?>">
                                    เปิด
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="grid grid-2 mt-2">
    <div class="card">
        <h2>เอกสารล่าสุด</h2>

        <?php if (empty($documents)): ?>
            <?php render_empty_state('ยังไม่มีเอกสาร', 'เมื่อมีการสร้างเอกสาร รายการจะแสดงที่นี่'); ?>
        <?php else: ?>
            <div class="document-grid mt-2">
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

    <div class="risk-card">
        <div class="risk-score">
            <div>
                <span class="badge <?= e($riskBadge) ?>">
                    Risk <?= e($riskLevel) ?>
                </span>

                <h2 style="margin:12px 0 6px;">
                    สถานะความเสี่ยงล่าสุด
                </h2>

                <p class="text-muted">
                    อ้างอิงจาก Visit ล่าสุดหรือข้อมูล Demo ของผู้ป่วย
                </p>
            </div>

            <div class="score-circle" style="--value:62">
                <strong><?= e($latestVisit['risk_score'] ?? 62) ?></strong>
            </div>
        </div>

        <div class="mt-2">
            <div class="riskbar">
                <span style="width:<?= e($latestVisit['risk_score'] ?? 62) ?>%"></span>
            </div>
        </div>

        <ul class="factor-list">
            <li>โรคประจำตัว: <?= e($patient['disease'] ?? '-') ?></li>
            <li>ติดตามประวัติการรักษา <?= e(count($visits)) ?> รายการ</li>
            <li>มีเอกสารสุขภาพ <?= e(count($documents)) ?> รายการ</li>
        </ul>

        <hr style="margin: 16px 0; border: none; border-top: 1px solid var(--border);">
        <h3 style="margin-bottom: 8px;">🤖 AI Predictive Insights</h3>
        <canvas id="aiTrajectoryChart" style="max-height: 200px;"></canvas>
        <div class="note-box mt-2" style="background: var(--bg-hover);">
            <strong>💡 Recommendation:</strong><br>
            <span id="aiMedRec">กำลังวิเคราะห์ข้อมูล Longitudinal...</span>
        </div>
    </div>
</section>

<?php
page_end();
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('aiTrajectoryChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['-120 วัน', '-60 วัน', 'ปัจจุบัน', '+60 วัน (AI Predict)'],
                datasets: [{
                    label: 'Trend Value',
                    data: [7.2, 7.5, 7.8, 8.2], // Mock trend data
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: false }
                }
            }
        });
        
        document.getElementById('aiMedRec').innerHTML = "AI พยากรณ์แนวโน้มค่าผลตรวจจะสูงขึ้นเกินเกณฑ์ใน 60 วันข้างหน้า แนะนำพิจารณาปรับแผนยา (เช่น เพิ่มโดสยา หรือเปลี่ยนกลุ่มยา)";
    }
});
</script>