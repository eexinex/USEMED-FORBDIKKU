<?php
// public/patient/portal.php
// Clean, solid, and focused patient home

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('patient');
usemed_ensure_extended_schema();

$user = current_user();

$patient = demo_patient($user['hn'] ?? null);
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
            'SELECT v.*, d.full_name AS doctor_name
             FROM visits v
             LEFT JOIN doctors d ON d.id = v.doctor_id
             WHERE v.patient_id = :patient_id
             ORDER BY v.visit_date DESC, v.id DESC
             LIMIT 1',
            ['patient_id' => (int) $patientRow['id']]
        );
        if ($dbVisits) { $visits = $dbVisits; }
    }
}

$latestVisit = $visits[0] ?? [];

$riskScore = (int) ($latestVisit['risk_score'] ?? $patient['risk_score'] ?? 62);
$careStatus = $riskScore >= 75 ? 'ต้องติดตามใกล้ชิด' : 'กำลังดูแลต่อเนื่อง';

$followupDate = usemed_visit_field($latestVisit, 'followup_date', (string) ($patient['next_appointment'] ?? '-'));
$appointmentDate = $followupDate !== '-' ? $followupDate : 'ยังไม่มีนัดหมายใหม่';
$appointmentTime = usemed_visit_field($latestVisit, 'next_appointment_time', '');

$doctorName = $latestVisit['doctor'] ?? $latestVisit['doctor_name'] ?? 'ทีมแพทย์';
$lastVisitDate = $latestVisit['date'] ?? $latestVisit['visit_date'] ?? '-';
$educationText = usemed_visit_field($latestVisit, 'doctor_education', 'ยังไม่มีคำแนะนำล่าสุดจากแพทย์');

page_start('Patient Portal', 'patient', 'portal');
?>

<div style="max-width: 800px; margin: 0 auto; padding: 24px 16px;">
    
    <!-- Profile Card (Solid, No Gradient) -->
    <div class="card" style="padding: 24px; margin-bottom: 24px; border-left: 6px solid var(--primary);">
        <div style="display: flex; gap: 20px; align-items: center;">
            <div style="width: 72px; height: 72px; border-radius: 50%; background: var(--bg2); color: var(--primary); font-size: 28px; font-weight: 800; display: flex; align-items: center; justify-content: center;">
                <?= e(initials($patient['full_name'] ?? 'ผู้ป่วย')) ?>
            </div>
            <div>
                <h1 style="margin: 0; font-size: 28px; color: var(--ink);"><?= e($patient['full_name'] ?? 'ผู้ป่วย') ?></h1>
                <p style="margin: 4px 0 0; color: var(--muted); font-size: 16px;">
                    HN: <?= e($patient['hn'] ?? '-') ?> · 
                    อายุ <?= e((string)($patient['age'] ?? '-')) ?> ปี · 
                    <?= e((string)($patient['disease'] ?? 'ทั่วไป')) ?>
                </p>
                <div style="margin-top: 8px;">
                    <span class="badge" style="background: var(--bg); color: var(--muted); border: 1px solid var(--line);">สถานะ: <?= e($careStatus) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div style="display: grid; gap: 24px;">
        
        <!-- Next Appointment -->
        <div class="card" style="padding: 24px; border: 1px solid var(--line);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 style="margin: 0; font-size: 20px; color: var(--ink);">📅 นัดหมายครั้งถัดไป</h2>
                <a href="<?= e(app_url('patient/timeline.php')) ?>" style="color: var(--primary); font-size: 14px; font-weight: bold;">ดูทั้งหมด</a>
            </div>
            <div style="background: var(--bg); padding: 16px; border-radius: 12px;">
                <strong style="display: block; font-size: 22px; color: var(--primary-dark);"><?= e($appointmentDate) ?></strong>
                <?php if ($appointmentTime): ?>
                    <span style="color: var(--muted); display: block; margin-top: 4px;">เวลา: <?= e($appointmentTime) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Latest Visit -->
        <div class="card" style="padding: 24px; border: 1px solid var(--line);">
            <h2 style="margin: 0 0 16px; font-size: 20px; color: var(--ink);">🩺 สรุปการตรวจล่าสุด</h2>
            <div style="margin-bottom: 16px;">
                <span style="color: var(--muted); font-size: 14px;">วันที่:</span>
                <strong style="display: block; font-size: 16px; margin-bottom: 8px;"><?= e($lastVisitDate) ?> (<?= e($doctorName) ?>)</strong>
                
                <span style="color: var(--muted); font-size: 14px;">คำแนะนำจากแพทย์:</span>
                <p style="margin: 4px 0 0; background: var(--bg2); padding: 12px; border-radius: 8px; color: var(--ink); line-height: 1.6;">
                    <?= e($educationText) ?>
                </p>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <a href="<?= e(app_url('patient/visit-detail.php?id=' . (int)($latestVisit['id'] ?? 1))) ?>" class="btn btn-primary" style="flex: 1; text-align: center;">ดูรายละเอียดการตรวจ</a>
            </div>
        </div>

        <!-- Quick Actions (Solid Buttons) -->
        <div class="card" style="padding: 24px; border: 1px solid var(--line);">
            <h2 style="margin: 0 0 16px; font-size: 20px; color: var(--ink);">⚡ บริการด่วน</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                <a href="<?= e(app_url('patient/documents.php')) ?>" class="btn" style="background: var(--bg); border: 1px solid var(--line); color: var(--ink); text-align: center; justify-content: center;">📑 ดูเอกสารทางการแพทย์</a>
                <a href="<?= e(app_url('patient/self-assessment.php')) ?>" class="btn" style="background: var(--bg); border: 1px solid var(--line); color: var(--ink); text-align: center; justify-content: center;">📝 ประเมินสุขภาพตนเอง</a>
                <a href="<?= e(app_url('support.php')) ?>" class="btn" style="background: var(--bg); border: 1px solid var(--line); color: var(--ink); text-align: center; justify-content: center;">💬 ติดต่อเจ้าหน้าที่</a>
            </div>
        </div>

    </div>

</div>

<?php page_end(); ?>
