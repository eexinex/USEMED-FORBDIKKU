<?php
// public/doctor/ems-handover.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('doctor');
usemed_ensure_extended_schema();
usemed_seed_demo_data();

$doctor = current_user();
$patients = db_is_connected() ? db_fetch_all('SELECT id, hn, full_name, age, gender FROM patients ORDER BY hn ASC') : demo_patients();
$caseType = $_POST['case_type'] ?? ($_GET['case_type'] ?? 'medical');
$mist = usemed_mist_labels($caseType);
$error = '';

if (is_post()) {
    $patientId = null;
    if (!empty($_POST['hn']) && db_is_connected()) {
        $p = db_fetch_one('SELECT id FROM patients WHERE hn = :hn LIMIT 1', ['hn' => $_POST['hn']]);
        $patientId = $p['id'] ?? null;
    }
    $data = [
        'patient_id' => $patientId,
        'doctor_id' => (int) ($doctor['id'] ?? 0) ?: null,
        'case_type' => $_POST['case_type'] ?? 'medical',
        'ems_unit' => $_POST['ems_unit'] ?? '',
        'arrival_time' => ($_POST['arrival_time'] ?? '') !== '' ? str_replace('T', ' ', $_POST['arrival_time']) . ':00' : null,
        'chief_complaint' => $_POST['chief_complaint'] ?? '',
        'mechanism' => $_POST['mechanism'] ?? '',
        'injuries' => $_POST['injuries'] ?? '',
        'signs_vitals' => $_POST['signs_vitals'] ?? '',
        'treatment_given' => $_POST['treatment_given'] ?? '',
        'sbar_situation' => $_POST['sbar_situation'] ?? '',
        'sbar_background' => $_POST['sbar_background'] ?? '',
        'sbar_assessment' => $_POST['sbar_assessment'] ?? '',
        'sbar_recommendation' => $_POST['sbar_recommendation'] ?? '',
        'height_cm' => $_POST['height_cm'] !== '' ? (float) $_POST['height_cm'] : null,
        'weight_kg' => $_POST['weight_kg'] !== '' ? (float) $_POST['weight_kg'] : null,
        'bp' => $_POST['bp'] ?? '',
        'pulse' => $_POST['pulse'] !== '' ? (int) $_POST['pulse'] : null,
        'rr' => $_POST['rr'] !== '' ? (int) $_POST['rr'] : null,
        'spo2' => $_POST['spo2'] !== '' ? (int) $_POST['spo2'] : null,
        'temp' => $_POST['temp'] !== '' ? (float) $_POST['temp'] : null,
        'gcs' => $_POST['gcs'] ?? '',
        'progress_note' => $_POST['progress_note'] ?? '',
        'status' => $_POST['status'] ?? 'รับเคสใหม่',
    ];
    if (trim((string) $data['chief_complaint']) === '') {
        $error = 'กรุณาใส่ CC / อาการนำคนไข้';
    } elseif (db_is_connected()) {
        $ok = usemed_insert_available('ems_cases', $data);
        if ($ok) { flash_set('success', 'บันทึก EMS handover แล้ว'); redirect_to('doctor/ems-handover.php'); }
        $error = 'บันทึกไม่ได้ กรุณาตรวจตาราง ems_cases';
    } else {
        $_SESSION['demo_ems_cases'][] = array_merge($data, ['id'=>time(), 'created_at'=>date('Y-m-d H:i:s'), 'hn'=>$_POST['hn'] ?? '-']);
        flash_set('success', 'บันทึก EMS handover แบบ Demo แล้ว'); redirect_to('doctor/ems-handover.php');
    }
}

$cases = db_is_connected()
    ? db_fetch_all('SELECT e.*, p.hn, p.full_name FROM ems_cases e LEFT JOIN patients p ON p.id=e.patient_id ORDER BY e.created_at DESC, e.id DESC LIMIT 20')
    : array_reverse($_SESSION['demo_ems_cases'] ?? []);

if (empty($cases)) {
    $cases = [
        ['id'=>1,'case_type'=>'medical','ems_unit'=>'EMS ขอนแก่น 1669','hn'=>'HN0002','full_name'=>'สมหญิง สุขใจ','chief_complaint'=>'หอบเหนื่อย ความดันสูง','bp'=>'182/106','pulse'=>112,'rr'=>26,'spo2'=>92,'gcs'=>'E4V5M6','status'=>'รับเคสใหม่','created_at'=>'2026-05-28 12:10:00','sbar_situation'=>'ผู้ป่วยหญิง หอบเหนื่อย BP สูง','sbar_background'=>'มี HT/CKD','sbar_assessment'=>'สงสัย volume overload','sbar_recommendation'=>'ขอรับประเมิน ER/อายุรกรรมด่วน'],
        ['id'=>2,'case_type'=>'trauma','ems_unit'=>'กู้ชีพลาดกระบัง','hn'=>'HN0009','full_name'=>'ศุภชัย คำดี','chief_complaint'=>'รถจักรยานยนต์ล้ม ปวดศีรษะ','bp'=>'146/88','pulse'=>98,'rr'=>20,'spo2'=>98,'gcs'=>'E4V5M6','status'=>'รอ CT','created_at'=>'2026-05-28 14:20:00','mechanism'=>'MCA ล้มเอง สวมหมวกกันน็อค','injuries'=>'แผลถลอก แขนขวา ปวดศีรษะ','signs_vitals'=>'GCS 15 BP 146/88','treatment_given'=>'C-spine precaution, IV line'],
    ];
}

page_start('EMS MIST/SBAR', 'doctor', 'ems');

topbar('รับเคสจาก EMS · MIST / SBAR', 'แยก Medical / Trauma และเก็บ CC, vital sign, height/weight, progress note');
?>

<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<section class="grid grid-2">
    <div class="form-card">
        <h2>บันทึกเคสที่รับจากรถฉุกเฉิน</h2>
        <p class="text-muted">MIST ใช้กับ handover EMS/Trauma ได้ดี ส่วน SBAR ใช้สื่อสารสถานการณ์-พื้นหลัง-ประเมิน-ข้อเสนอแนะภายในทีม</p>
        <form method="post" class="mt-2">
            <div class="form-grid compact-form">
                <div class="field"><label>ประเภทเคส</label><select name="case_type" onchange="this.form.submit()"><option value="medical" <?= $caseType==='medical'?'selected':'' ?>>Medical</option><option value="trauma" <?= $caseType==='trauma'?'selected':'' ?>>Trauma</option></select></div>
                <div class="field"><label>EMS / หน่วยที่ส่งมา</label><input name="ems_unit" placeholder="เช่น EMS ขอนแก่น 1669"></div>
                <div class="field"><label>ผู้ป่วยเดิมในระบบ</label><select name="hn"><option value="">ไม่ทราบ/ยังไม่ผูก HN</option><?php foreach ($patients as $p): ?><option value="<?= e($p['hn']) ?>"><?= e(($p['hn'] ?? '-') . ' · ' . ($p['full_name'] ?? '-')) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label>เวลาถึง ER</label><input type="datetime-local" name="arrival_time"></div>
                <div class="field full"><label>CC / อาการนำ</label><input name="chief_complaint" placeholder="เช่น เจ็บหน้าอก 30 นาที / รถชนหมดสติ"></div>
            </div>

            <div class="grid grid-2 mt-2">
                <div class="card soft-card"><h3>MIST</h3><div class="field"><label><?= e($mist['mechanism']) ?></label><textarea name="mechanism" rows="2"></textarea></div><div class="field"><label><?= e($mist['injuries']) ?></label><textarea name="injuries" rows="2"></textarea></div><div class="field"><label><?= e($mist['signs']) ?></label><textarea name="signs_vitals" rows="2"></textarea></div><div class="field"><label><?= e($mist['treatment']) ?></label><textarea name="treatment_given" rows="2"></textarea></div></div>
                <div class="card soft-card"><h3>SBAR</h3><div class="field"><label>Situation</label><textarea name="sbar_situation" rows="2"></textarea></div><div class="field"><label>Background</label><textarea name="sbar_background" rows="2"></textarea></div><div class="field"><label>Assessment</label><textarea name="sbar_assessment" rows="2"></textarea></div><div class="field"><label>Recommendation</label><textarea name="sbar_recommendation" rows="2"></textarea></div></div>
            </div>

            <div class="form-grid compact-form mt-2">
                <div class="field"><label>BP</label><input name="bp" placeholder="120/80"></div><div class="field"><label>Pulse</label><input name="pulse" type="number"></div><div class="field"><label>RR</label><input name="rr" type="number"></div><div class="field"><label>SpO2</label><input name="spo2" type="number"></div><div class="field"><label>Temp</label><input name="temp" type="number" step="0.1"></div><div class="field"><label>GCS</label><input name="gcs" placeholder="E4V5M6"></div><div class="field"><label>Height</label><input name="height_cm" type="number" step="0.01"></div><div class="field"><label>Weight</label><input name="weight_kg" type="number" step="0.01"></div><div class="field"><label>Status</label><select name="status"><option>รับเคสใหม่</option><option>รอแพทย์ประเมิน</option><option>รอ CT/Lab</option><option>Admit</option><option>ส่งต่อ</option></select></div><div class="field full"><label>Progress note แรกเข้า</label><textarea name="progress_note" rows="3"></textarea></div>
            </div>
            <div class="btn-row mt-2"><button class="btn" type="submit">บันทึก EMS Case</button><a class="btn secondary" href="<?= e(app_url('doctor/progress-note.php')) ?>">เปิด Progress Note</a></div>
        </form>
    </div>
    <div class="card"><h2>Medical vs Trauma</h2><div class="document-grid mt-2"><div class="document-card"><div><strong>Medical</strong><span>เน้น CC, อาการป่วย, โรคเดิม, ยา, vital trend, treatment response</span></div><span class="badge blue">SBAR</span></div><div class="document-card"><div><strong>Trauma</strong><span>เน้น mechanism, injuries, bleeding, GCS, C-spine, immobilization, pain control</span></div><span class="badge orange">MIST</span></div></div></div>
</section>

<section class="table-card mt-2"><div class="topbar"><div><h1>เคส EMS ล่าสุด</h1><p>กดดู progress note เพื่อ follow-up ต่อ</p></div></div><div class="table-wrap"><table class="table"><thead><tr><th>เวลา</th><th>ประเภท</th><th>ผู้ป่วย</th><th>CC</th><th>Vitals</th><th>Status</th><th>ดู</th></tr></thead><tbody><?php foreach ($cases as $c): ?><tr><td><?= e($c['created_at'] ?? '-') ?></td><td><span class="badge <?= ($c['case_type'] ?? '') === 'trauma' ? 'orange' : 'blue' ?>"><?= e($c['case_type'] ?? '-') ?></span></td><td><?= e(($c['hn'] ?? '-') . ' · ' . ($c['full_name'] ?? '-')) ?></td><td><?= e($c['chief_complaint'] ?? '-') ?></td><td><?= e('BP ' . ($c['bp'] ?? '-') . ' P ' . ($c['pulse'] ?? '-') . ' RR ' . ($c['rr'] ?? '-') . ' SpO2 ' . ($c['spo2'] ?? '-')) ?></td><td><?= e($c['status'] ?? '-') ?></td><td><a class="btn secondary" href="<?= e(app_url('doctor/progress-note.php?ems_id=' . urlencode((string)($c['id'] ?? '')))) ?>">Progress</a></td></tr><?php endforeach; ?></tbody></table></div></section>

<?php page_end(); ?>
