<?php
// public/doctor/progress-note.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('doctor');
usemed_ensure_extended_schema();
usemed_seed_demo_data();

$emsId = (int) ($_GET['ems_id'] ?? 0);
$case = null;
if (db_is_connected() && $emsId > 0) {
    $case = db_fetch_one('SELECT e.*, p.hn, p.full_name FROM ems_cases e LEFT JOIN patients p ON p.id=e.patient_id WHERE e.id=:id LIMIT 1', ['id'=>$emsId]);
}

$visits = db_is_connected()
    ? db_fetch_all('SELECT v.*, p.hn, p.full_name FROM visits v LEFT JOIN patients p ON p.id=v.patient_id ORDER BY v.visit_date DESC, v.id DESC LIMIT 20')
    : demo_visits();
$emsCases = db_is_connected()
    ? db_fetch_all('SELECT e.*, p.hn, p.full_name FROM ems_cases e LEFT JOIN patients p ON p.id=e.patient_id ORDER BY e.created_at DESC, e.id DESC LIMIT 20')
    : array_reverse($_SESSION['demo_ems_cases'] ?? []);

page_start('Progress Note', 'doctor', 'progress');

topbar('Progress Note', 'รวม CC, Vital sign, height/weight, assessment และ note ที่กดเข้าไปดูได้');
?>

<?php if ($case): ?>
<section class="card">
    <h2>EMS Progress Note #<?= e($case['id']) ?></h2>
    <div class="grid grid-3 mt-2">
        <div class="document-card"><div><strong>ผู้ป่วย</strong><span><?= e(($case['hn'] ?? '-') . ' · ' . ($case['full_name'] ?? '-')) ?></span></div><span class="badge blue">Patient</span></div>
        <div class="document-card"><div><strong>CC</strong><span><?= e($case['chief_complaint'] ?? '-') ?></span></div><span class="badge orange">CC</span></div>
        <div class="document-card"><div><strong>Vitals</strong><span><?= e('BP ' . ($case['bp'] ?? '-') . ' P ' . ($case['pulse'] ?? '-') . ' RR ' . ($case['rr'] ?? '-') . ' SpO2 ' . ($case['spo2'] ?? '-')) ?></span></div><span class="badge green">VS</span></div>
    </div>
    <div class="grid grid-2 mt-2">
        <div class="card soft-card"><h3>MIST</h3><p><strong>Mechanism/Illness:</strong> <?= nl2br(e($case['mechanism'] ?? '-')) ?></p><p><strong>Injuries/Inspection:</strong> <?= nl2br(e($case['injuries'] ?? '-')) ?></p><p><strong>Signs:</strong> <?= nl2br(e($case['signs_vitals'] ?? '-')) ?></p><p><strong>Treatment:</strong> <?= nl2br(e($case['treatment_given'] ?? '-')) ?></p></div>
        <div class="card soft-card"><h3>SBAR</h3><p><strong>S:</strong> <?= nl2br(e($case['sbar_situation'] ?? '-')) ?></p><p><strong>B:</strong> <?= nl2br(e($case['sbar_background'] ?? '-')) ?></p><p><strong>A:</strong> <?= nl2br(e($case['sbar_assessment'] ?? '-')) ?></p><p><strong>R:</strong> <?= nl2br(e($case['sbar_recommendation'] ?? '-')) ?></p></div>
    </div>
    <div class="card soft-card mt-2"><h3>Progress note</h3><p><?= nl2br(e($case['progress_note'] ?? '-')) ?></p></div>
</section>
<?php endif; ?>

<section class="table-card mt-2">
    <div class="topbar"><div><h1>Visit Progress Notes</h1><p>คลิกดูรายละเอียด visit ต่อได้</p></div><div class="searchbar"><input type="search" data-table-search="progressVisits" placeholder="ค้นหา"></div></div>
    <div class="table-wrap"><table class="table" id="progressVisits"><thead><tr><th>วันที่</th><th>ผู้ป่วย</th><th>CC</th><th>Vitals</th><th>Height/Weight</th><th>Progress/Assessment</th><th>ดู</th></tr></thead><tbody>
        <?php foreach ($visits as $v): ?>
        <tr><td><?= e($v['visit_date'] ?? $v['date'] ?? '-') ?></td><td><strong><?= e($v['full_name'] ?? '-') ?></strong><br><span class="text-muted"><?= e($v['hn'] ?? '-') ?></span></td><td><?= e($v['chief_complaint'] ?? $v['visit_reason'] ?? '-') ?></td><td><?= e('BP ' . ($v['systolic'] ?? '-') . '/' . ($v['diastolic'] ?? '-') . ' P ' . ($v['pulse'] ?? '-') . ' T ' . ($v['temperature'] ?? '-')) ?></td><td><?= e(($v['height_cm'] ?? '-') . ' cm / ' . ($v['weight_kg'] ?? '-') . ' kg') ?></td><td><?= e($v['assessment'] ?? $v['treatment_plan'] ?? $v['summary'] ?? '-') ?></td><td><a class="btn secondary" href="<?= e(app_url('doctor/visit-detail.php?id=' . urlencode((string)($v['id'] ?? '')))) ?>">เปิด</a></td></tr>
        <?php endforeach; ?>
    </tbody></table></div>
</section>

<section class="table-card mt-2">
    <div class="topbar"><div><h1>EMS Progress Notes</h1><p>เคสจาก EMS ที่รับเข้ามา</p></div></div>
    <?php if (empty($emsCases)): ?><?php render_empty_state('ยังไม่มี EMS case', 'เพิ่มจากหน้า EMS MIST/SBAR'); ?><?php else: ?>
    <div class="table-wrap"><table class="table"><thead><tr><th>เวลา</th><th>ประเภท</th><th>ผู้ป่วย</th><th>CC</th><th>Vitals</th><th>Note</th><th>ดู</th></tr></thead><tbody><?php foreach ($emsCases as $e): ?><tr><td><?= e($e['created_at'] ?? '-') ?></td><td><?= e($e['case_type'] ?? '-') ?></td><td><?= e(($e['hn'] ?? '-') . ' · ' . ($e['full_name'] ?? '-')) ?></td><td><?= e($e['chief_complaint'] ?? '-') ?></td><td><?= e('BP ' . ($e['bp'] ?? '-') . ' P ' . ($e['pulse'] ?? '-') . ' RR ' . ($e['rr'] ?? '-')) ?></td><td><?= e($e['progress_note'] ?? '-') ?></td><td><a class="btn secondary" href="<?= e(app_url('doctor/progress-note.php?ems_id=' . urlencode((string)($e['id'] ?? '')))) ?>">เปิด</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>

<?php page_end(); ?>
