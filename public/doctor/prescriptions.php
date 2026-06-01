<?php
// public/doctor/prescriptions.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('doctor');
usemed_ensure_extended_schema();
usemed_seed_demo_data();

$doctor = current_user();
$patients = db_is_connected() ? db_fetch_all('SELECT id, hn, full_name, payment_method, disease FROM patients ORDER BY hn ASC') : demo_patients();
$selectedHn = $_GET['hn'] ?? ($_POST['hn'] ?? ($patients[0]['hn'] ?? 'HN0001'));
$selectedPatient = demo_patient($selectedHn);
foreach ($patients as $p) {
    if (($p['hn'] ?? '') === $selectedHn) { $selectedPatient = array_merge($selectedPatient, $p); break; }
}

$createdId = null;
$error = '';
if (is_post()) {
    $hn = trim($_POST['hn'] ?? '');
    $patientRow = db_is_connected() ? db_fetch_one('SELECT * FROM patients WHERE hn = :hn LIMIT 1', ['hn' => $hn]) : null;
    $patientId = (int) ($patientRow['id'] ?? $selectedPatient['id'] ?? 0);
    $doctorId = (int) ($doctor['id'] ?? 0);
    $rxNo = 'RX' . date('YmdHis');
    $names = $_POST['medication_name'] ?? [];
    $hasItems = false;
    foreach ((array) $names as $name) { if (trim((string) $name) !== '') { $hasItems = true; break; } }

    if (!$hasItems) {
        $error = 'กรุณาใส่รายการยาอย่างน้อย 1 รายการ';
    } elseif (db_is_connected() && $patientId > 0) {
        $ok = db_execute(
            'INSERT INTO prescriptions (patient_id, doctor_id, rx_no, diagnosis, payment_method, note, status)
             VALUES (:patient_id, :doctor_id, :rx_no, :diagnosis, :payment_method, :note, :status)',
            [
                'patient_id' => $patientId,
                'doctor_id' => $doctorId ?: null,
                'rx_no' => $rxNo,
                'diagnosis' => $_POST['diagnosis'] ?? '',
                'payment_method' => $_POST['payment_method'] ?? '',
                'note' => $_POST['note'] ?? '',
                'status' => $_POST['status'] ?? 'จ่ายยาแล้ว',
            ]
        );
        $createdId = (int) db_last_id();
        if ($ok && $createdId > 0) {
            foreach ((array) $names as $i => $name) {
                $name = trim((string) $name);
                if ($name === '') { continue; }
                db_execute(
                    'INSERT INTO prescription_items (prescription_id, medication_name, strength, dose, route, frequency, duration, quantity, instruction)
                     VALUES (:prescription_id, :medication_name, :strength, :dose, :route, :frequency, :duration, :quantity, :instruction)',
                    [
                        'prescription_id' => $createdId,
                        'medication_name' => $name,
                        'strength' => $_POST['strength'][$i] ?? '',
                        'dose' => $_POST['dose'][$i] ?? '',
                        'route' => $_POST['route'][$i] ?? '',
                        'frequency' => $_POST['frequency'][$i] ?? '',
                        'duration' => $_POST['duration'][$i] ?? '',
                        'quantity' => $_POST['quantity'][$i] ?? '',
                        'instruction' => $_POST['instruction'][$i] ?? '',
                    ]
                );
            }
            flash_set('success', 'บันทึกใบสั่งยาแล้ว สามารถพิมพ์ได้ทันที');
            redirect_to('doctor/prescription-print.php?id=' . $createdId);
        } else {
            $error = 'บันทึกใบสั่งยาไม่สำเร็จ';
        }
    } else {
        $_SESSION['demo_prescriptions'][] = [
            'id' => time(), 'rx_no' => $rxNo, 'hn' => $hn, 'patient_name' => $selectedPatient['full_name'] ?? '-',
            'diagnosis' => $_POST['diagnosis'] ?? '', 'payment_method' => $_POST['payment_method'] ?? '', 'created_at' => date('Y-m-d H:i:s'),
        ];
        flash_set('success', 'บันทึกใบสั่งยา Demo แล้ว');
    }
}

$recent = db_is_connected()
    ? db_fetch_all('SELECT rx.*, p.hn, p.full_name FROM prescriptions rx LEFT JOIN patients p ON p.id = rx.patient_id ORDER BY rx.created_at DESC, rx.id DESC LIMIT 10')
    : array_reverse($_SESSION['demo_prescriptions'] ?? []);

page_start('ยา/ใบสั่งยา', 'doctor', 'rx');

topbar('ยา · จ่ายยา · พิมพ์ใบสั่งยา', 'บันทึกการจ่ายยา เลือกสิทธิการรักษา และพิมพ์ใบสั่งยาให้ผู้ป่วย');
?>

<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<section class="grid grid-2">
    <div class="form-card">
        <h2>สร้างใบสั่งยา</h2>
        <form method="post" class="mt-2">
            <div class="form-grid compact-form">
                <div class="field"><label>เลือกผู้ป่วย</label><select name="hn" onchange="location.href='?hn='+encodeURIComponent(this.value)"><?php foreach ($patients as $p): ?><option value="<?= e($p['hn']) ?>" <?= ($p['hn'] ?? '') === $selectedHn ? 'selected' : '' ?>><?= e(($p['hn'] ?? '-') . ' · ' . ($p['full_name'] ?? '-')) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label>สิทธิ/วิธีจ่าย</label><select name="payment_method"><?php foreach (demo_payment_methods() as $m): ?><option value="<?= e($m) ?>" <?= (($selectedPatient['payment_method'] ?? '') === $m) ? 'selected' : '' ?>><?= e($m) ?></option><?php endforeach; ?></select></div>
                <div class="field full"><label>Diagnosis / Indication</label><input name="diagnosis" value="<?= e($selectedPatient['disease'] ?? '') ?>" placeholder="เช่น DM/HT follow-up"></div>
            </div>

            <div class="table-wrap mt-2"><table class="table compact-table"><thead><tr><th>ยา</th><th>ขนาด</th><th>Dose</th><th>Route</th><th>ความถี่</th><th>ระยะเวลา</th><th>จำนวน</th></tr></thead><tbody>
                <?php for ($i=0; $i<5; $i++): ?>
                <tr>
                    <td><input name="medication_name[]" placeholder="เช่น Metformin"></td>
                    <td><input name="strength[]" placeholder="500 mg"></td>
                    <td><input name="dose[]" placeholder="1 tab"></td>
                    <td><input name="route[]" placeholder="PO"></td>
                    <td><input name="frequency[]" placeholder="bid pc"></td>
                    <td><input name="duration[]" placeholder="30 วัน"></td>
                    <td><input name="quantity[]" placeholder="60"></td>
                </tr>
                <tr><td colspan="7"><input name="instruction[]" placeholder="คำแนะนำเพิ่มเติม เช่น รับประทานหลังอาหารทันที / ระวังน้ำตาลต่ำ"></td></tr>
                <?php endfor; ?>
            </tbody></table></div>

            <div class="form-grid compact-form mt-2">
                <div class="field"><label>สถานะ</label><select name="status"><option>จ่ายยาแล้ว</option><option>รอห้องยา</option><option>ยกเลิก</option></select></div>
                <div class="field full"><label>หมายเหตุ</label><textarea name="note" rows="2" placeholder="คำแนะนำ เงื่อนไขจ่ายยา หรือหมายเหตุห้องยา"></textarea></div>
            </div>

            <div class="btn-row mt-2"><button class="btn" type="submit">บันทึกและพิมพ์ใบสั่งยา</button><a class="btn secondary" href="<?= e(app_url('doctor/export-excel.php')) ?>">Export Excel</a></div>
        </form>
    </div>

    <div class="card">
        <h2>ข้อมูลผู้ป่วย</h2>
        <div class="document-grid mt-2">
            <div class="document-card"><div><strong><?= e($selectedPatient['full_name'] ?? '-') ?></strong><span><?= e($selectedPatient['hn'] ?? '-') ?></span></div><span class="badge blue">Patient</span></div>
            <div class="document-card"><div><strong>โรค/ข้อบ่งชี้</strong><span><?= e($selectedPatient['disease'] ?? '-') ?></span></div><span class="badge orange">Dx</span></div>
            <div class="document-card"><div><strong>สิทธิเดิม</strong><span><?= e($selectedPatient['payment_method'] ?? 'ยังไม่มีข้อมูล') ?></span></div><span class="badge green">Payment</span></div>
        </div>
    </div>
</section>

<section class="table-card mt-2">
    <div class="topbar"><div><h1>ใบสั่งยาล่าสุด</h1><p>รายการที่บันทึกไว้ สามารถเปิดพิมพ์ซ้ำได้</p></div></div>
    <?php if (empty($recent)): ?><?php render_empty_state('ยังไม่มีใบสั่งยา', 'เมื่อบันทึกแล้วรายการจะแสดงตรงนี้'); ?><?php else: ?>
    <div class="table-wrap"><table class="table"><thead><tr><th>Rx No</th><th>ผู้ป่วย</th><th>Diagnosis</th><th>วิธีจ่าย</th><th>วันที่</th><th>พิมพ์</th></tr></thead><tbody>
        <?php foreach ($recent as $r): ?><tr><td><?= e($r['rx_no'] ?? '-') ?></td><td><?= e(($r['hn'] ?? '-') . ' · ' . ($r['full_name'] ?? $r['patient_name'] ?? '-')) ?></td><td><?= e($r['diagnosis'] ?? '-') ?></td><td><?= e($r['payment_method'] ?? '-') ?></td><td><?= e($r['created_at'] ?? '-') ?></td><td><?php if (!empty($r['id']) && db_is_connected()): ?><a class="btn secondary" href="<?= e(app_url('doctor/prescription-print.php?id=' . urlencode((string)$r['id']))) ?>">พิมพ์</a><?php else: ?><span class="badge blue">Demo</span><?php endif; ?></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
</section>

<?php page_end(); ?>
