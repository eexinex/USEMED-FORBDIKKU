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

<div style="max-width: 1200px; margin: 0 auto; padding: 24px 16px;">
    
    <!-- Top Header & Search -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px; align-items: start;">
        <div class="card" style="padding: 24px; border-left: 6px solid var(--primary);">
            <div style="display: flex; gap: 20px; align-items: flex-start;">
                <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--bg-hover); display: flex; align-items: center; justify-content: center; font-size: 28px; border: 2px solid var(--primary);">
                    👤
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                        <h1 style="margin: 0; font-size: 24px; color: var(--ink);"><?= e($patient['full_name'] ?? 'ไม่พบชื่อผู้ป่วย') ?></h1>
                        <span class="badge <?= e($riskBadge) ?>"><?= e($riskLevel) ?> Risk</span>
                    </div>
                    <div style="color: var(--muted); display: flex; flex-wrap: wrap; gap: 16px; font-size: 14px;">
                        <span><strong>HN:</strong> <?= e($patient['hn'] ?? 'HN0001') ?></span>
                        <span><strong>อายุ:</strong> <?= e($patient['age'] ?? '-') ?> ปี</span>
                        <span><strong>เพศ:</strong> <?= e($patient['gender'] ?? '-') ?></span>
                        <span><strong>เบอร์โทร:</strong> <?= e($patient['phone'] ?? '-') ?></span>
                    </div>
                    <div style="margin-top: 12px; font-size: 14px;">
                        <span style="color: var(--ink);"><strong>โรคประจำตัว:</strong> <?= e($patient['disease'] ?? 'ไม่มีโรคประจำตัว') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="padding: 24px;">
            <h2 style="margin: 0 0 16px; font-size: 16px; color: var(--ink);">ค้นหาผู้ป่วยอื่น</h2>
            <form method="get" style="display: flex; gap: 8px;">
                <input type="text" name="hn" value="<?= e($hn) ?>" placeholder="กรอกเลข HN..." style="flex: 1; padding: 10px; border: 1px solid var(--line); border-radius: 8px;">
                <button class="btn" type="submit">ค้นหา</button>
            </form>
            <div style="margin-top: 16px; text-align: right;">
                <a href="<?= e(app_url('doctor/register-patient.php')) ?>" style="color: var(--primary); font-size: 14px; text-decoration: none;">+ ลงทะเบียนผู้ป่วยใหม่</a>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
        <a href="<?= e(app_url('doctor/add-treatment.php?hn=' . urlencode($hn))) ?>" class="card" style="padding: 20px; display: flex; align-items: center; gap: 16px; text-decoration: none; background: var(--bg); border: 1px solid var(--line);">
            <div style="font-size: 24px;">🩺</div>
            <div>
                <strong style="display: block; color: var(--ink); margin-bottom: 4px;">เพิ่มการรักษาใหม่</strong>
                <span style="color: var(--muted); font-size: 13px;">บันทึก Visit และวินิจฉัย</span>
            </div>
        </a>
        <a href="<?= e(app_url('doctor/prescriptions.php?hn=' . urlencode($hn))) ?>" class="card" style="padding: 20px; display: flex; align-items: center; gap: 16px; text-decoration: none; background: var(--bg); border: 1px solid var(--line);">
            <div style="font-size: 24px;">💊</div>
            <div>
                <strong style="display: block; color: var(--ink); margin-bottom: 4px;">สั่งยา / ใบสั่งยา</strong>
                <span style="color: var(--muted); font-size: 13px;">จัดการรายการยาผู้ป่วย</span>
            </div>
        </a>
        <a href="<?= e(app_url('doctor/referral.php?hn=' . urlencode($hn))) ?>" class="card" style="padding: 20px; display: flex; align-items: center; gap: 16px; text-decoration: none; background: var(--bg); border: 1px solid var(--line);">
            <div style="font-size: 24px;">🚑</div>
            <div>
                <strong style="display: block; color: var(--ink); margin-bottom: 4px;">ส่งต่อ / ส่งตัว</strong>
                <span style="color: var(--muted); font-size: 13px;">ทำเรื่องส่งผู้ป่วยไปแผนกอื่น</span>
            </div>
        </a>
    </div>

    <!-- Main Content Split -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
        
        <!-- Left Column: Visit History & Documents -->
        <div>
            <!-- Visits -->
            <div class="table-card" style="margin-bottom: 24px;">
                <div style="padding: 16px 24px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="margin: 0; font-size: 18px; color: var(--ink);">ประวัติการรักษา</h2>
                    <span style="color: var(--muted); font-size: 14px;"><?= e((string)count($visits)) ?> รายการ</span>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>วันที่</th>
                                <th>หัวข้อ / วินิจฉัย</th>
                                <th>แพทย์</th>
                                <th style="width: 80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($visits)): ?>
                                <tr><td colspan="4" style="text-align: center; padding: 24px; color: var(--muted);">ยังไม่มีประวัติการรักษา</td></tr>
                            <?php else: ?>
                                <?php foreach ($visits as $visit): ?>
                                    <?php
                                    $date = $visit['date'] ?? $visit['visit_date'] ?? '-';
                                    $title = $visit['title'] ?? '-';
                                    $summary = $visit['summary'] ?? $visit['diagnosis'] ?? '-';
                                    $doctorName = $visit['doctor'] ?? $visit['doctor_name'] ?? 'นพ.กิตติ';
                                    ?>
                                    <tr>
                                        <td><?= e($date) ?></td>
                                        <td>
                                            <strong style="color: var(--ink); display: block; margin-bottom: 4px;"><?= e($title) ?></strong>
                                            <span style="color: var(--muted); font-size: 13px;"><?= e($summary) ?></span>
                                        </td>
                                        <td style="color: var(--muted);"><?= e($doctorName) ?></td>
                                        <td><a class="btn secondary small" href="<?= e(app_url('doctor/visit-detail.php')) ?>">เปิด</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Documents -->
            <div class="card">
                <div style="padding: 16px 24px; border-bottom: 1px solid var(--line);">
                    <h2 style="margin: 0; font-size: 18px; color: var(--ink);">เอกสารสุขภาพ</h2>
                </div>
                <div style="padding: 0;">
                    <?php if (empty($documents)): ?>
                        <div style="text-align: center; padding: 24px; color: var(--muted);">ยังไม่มีเอกสาร</div>
                    <?php else: ?>
                        <?php foreach ($documents as $doc): ?>
                            <?php
                            $docId = (int) ($doc['id'] ?? 1);
                            $docTitle = $doc['title'] ?? 'เอกสารสุขภาพ';
                            $docType = $doc['document_type'] ?? $doc['type'] ?? 'PDF';
                            $docDate = $doc['created_at'] ?? $doc['date'] ?? '-';
                            ?>
                            <a href="<?= e(app_url('doctor/document-view.php?id=' . $docId)) ?>" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; border-bottom: 1px solid var(--line); text-decoration: none;">
                                <div>
                                    <strong style="display: block; color: var(--ink); margin-bottom: 4px;"><?= e($docTitle) ?></strong>
                                    <span style="color: var(--muted); font-size: 13px;"><?= e($docDate) ?></span>
                                </div>
                                <span class="badge blue"><?= e($docType) ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: AI Risk -->
        <div class="card" style="background: linear-gradient(180deg, #ffffff 0%, var(--bg) 100%); border: 1px solid var(--line);">
            <div style="padding: 20px; border-bottom: 1px solid var(--line); text-align: center;">
                <span class="badge <?= e($riskBadge) ?>" style="margin-bottom: 12px;">Risk <?= e($riskLevel) ?></span>
                <h2 style="margin: 0 0 8px; font-size: 18px; color: var(--ink);">ระดับความเสี่ยงล่าสุด</h2>
                <div class="score-circle" style="--value:<?= e($latestVisit['risk_score'] ?? 62) ?>; margin: 16px auto;">
                    <strong><?= e($latestVisit['risk_score'] ?? 62) ?></strong>
                </div>
                <div class="riskbar mt-2"><span style="width:<?= e($latestVisit['risk_score'] ?? 62) ?>%"></span></div>
            </div>
            <div style="padding: 20px;">
                <h3 style="margin: 0 0 12px; font-size: 15px; color: var(--ink);">🤖 AI Predictive Insights</h3>
                <canvas id="aiTrajectoryChart" style="max-height: 180px; width: 100%; margin-bottom: 16px;"></canvas>
                <div style="background: var(--bg-hover); padding: 12px; border-radius: 8px; font-size: 13px; color: var(--ink); line-height: 1.5;">
                    <strong style="color: var(--primary-dark);">💡 Recommendation:</strong><br>
                    <span id="aiMedRec">กำลังวิเคราะห์ข้อมูล Longitudinal...</span>
                </div>
            </div>
        </div>

    </div>
</div>

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