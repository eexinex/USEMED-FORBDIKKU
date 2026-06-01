<?php
// public/doctor/prescription-print.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';
require_login('doctor');
usemed_ensure_extended_schema();

$id = (int) ($_GET['id'] ?? 0);
$rx = null; $items = [];
if (db_is_connected() && $id > 0) {
    $rx = db_fetch_one('SELECT rx.*, p.hn, p.full_name, p.age, p.gender, p.allergy_history, d.full_name AS doctor_name, d.license_no FROM prescriptions rx LEFT JOIN patients p ON p.id = rx.patient_id LEFT JOIN doctors d ON d.id = rx.doctor_id WHERE rx.id = :id LIMIT 1', ['id'=>$id]);
    $items = db_fetch_all('SELECT * FROM prescription_items WHERE prescription_id = :id ORDER BY id ASC', ['id'=>$id]);
}
if (!$rx) { die('ไม่พบใบสั่งยา'); }
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Print Prescription</title><style>
body{font-family:Arial,Tahoma,sans-serif;margin:0;background:#f4faf8;color:#102522}.sheet{max-width:900px;margin:32px auto;background:white;border-radius:24px;padding:34px;box-shadow:0 22px 70px rgba(15,23,42,.12)}.head{display:flex;justify-content:space-between;border-bottom:2px solid #0f766e;padding-bottom:16px}.brand{font-size:32px;font-weight:900;color:#0f766e}.meta{color:#64748b}.grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-top:20px}.box{border:1px solid #dbe8e5;border-radius:16px;padding:14px}table{width:100%;border-collapse:collapse;margin-top:20px}th,td{border-bottom:1px solid #e2e8f0;text-align:left;padding:12px}th{background:#ecfeff;color:#0f766e}.sign{display:flex;justify-content:space-between;margin-top:50px}.btn{display:inline-block;margin:20px auto 0;padding:12px 18px;border-radius:999px;background:#0f766e;color:white;text-decoration:none;font-weight:800}@media print{body{background:white}.sheet{box-shadow:none;margin:0;max-width:100%;border-radius:0}.no-print{display:none}}
</style></head><body><main class="sheet"><section class="head"><div><div class="brand">USE MED</div><div>ใบสั่งยา / Prescription</div></div><div class="meta"><strong><?= e($rx['rx_no'] ?? '-') ?></strong><br><?= e($rx['created_at'] ?? '-') ?></div></section><section class="grid"><div class="box"><strong>ผู้ป่วย</strong><br><?= e(($rx['hn'] ?? '-') . ' · ' . ($rx['full_name'] ?? '-')) ?><br><?= e(($rx['gender'] ?? '-') . ' / ' . ($rx['age'] ?? '-') . ' ปี') ?></div><div class="box"><strong>แพทย์</strong><br><?= e($rx['doctor_name'] ?? current_user()['name'] ?? '-') ?><br>เลขว. <?= e($rx['license_no'] ?? '-') ?></div><div class="box"><strong>Diagnosis</strong><br><?= e($rx['diagnosis'] ?? '-') ?></div><div class="box"><strong>สิทธิ/วิธีจ่าย</strong><br><?= e($rx['payment_method'] ?? '-') ?></div></section><table><thead><tr><th>#</th><th>ยา</th><th>ขนาด/วิธีใช้</th><th>จำนวน</th><th>คำแนะนำ</th></tr></thead><tbody><?php foreach ($items as $i=>$it): ?><tr><td><?= $i+1 ?></td><td><strong><?= e($it['medication_name'] ?? '-') ?></strong><br><?= e($it['strength'] ?? '') ?></td><td><?= e(trim(($it['dose'] ?? '') . ' ' . ($it['route'] ?? '') . ' ' . ($it['frequency'] ?? '') . ' ' . ($it['duration'] ?? ''))) ?></td><td><?= e($it['quantity'] ?? '-') ?></td><td><?= e($it['instruction'] ?? '-') ?></td></tr><?php endforeach; ?></tbody></table><p><strong>หมายเหตุ:</strong> <?= e($rx['note'] ?? '-') ?></p><section class="sign"><div>ผู้จ่ายยา ____________________</div><div>แพทย์ผู้สั่งยา ____________________</div></section><div class="no-print"><a class="btn" href="#" onclick="window.print();return false;">พิมพ์ใบสั่งยา</a> <a class="btn" href="<?= e(app_url('doctor/prescriptions.php')) ?>">กลับ</a></div></main></body></html>
