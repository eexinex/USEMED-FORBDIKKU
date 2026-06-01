<?php
// public/doctor/export-excel.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';
require_login('doctor');
usemed_ensure_extended_schema();

$type = $_GET['type'] ?? 'patients';
$filename = 'usemed_' . preg_replace('/[^a-z0-9_\-]/i', '', $type) . '_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');

if ($type === 'prescriptions' && db_is_connected()) {
    fputcsv($out, ['rx_no','created_at','hn','patient_name','diagnosis','payment_method','status','medication','strength','dose','frequency','quantity']);
    $rows = db_fetch_all('SELECT rx.rx_no, rx.created_at, p.hn, p.full_name, rx.diagnosis, rx.payment_method, rx.status, it.medication_name, it.strength, it.dose, it.frequency, it.quantity FROM prescriptions rx LEFT JOIN patients p ON p.id=rx.patient_id LEFT JOIN prescription_items it ON it.prescription_id=rx.id ORDER BY rx.created_at DESC, rx.id DESC');
    foreach ($rows as $r) { fputcsv($out, $r); }
    exit;
}

if ($type === 'visits' && db_is_connected()) {
    fputcsv($out, ['visit_date','hn','patient_name','visit_type','chief_complaint','diagnosis','treatment_plan','risk_score','risk_level','bp','pulse','weight_kg','height_cm']);
    $rows = db_fetch_all('SELECT v.visit_date, p.hn, p.full_name, v.visit_type, v.chief_complaint, v.diagnosis, v.treatment_plan, v.risk_score, v.risk_level, CONCAT(COALESCE(v.systolic,""),"/",COALESCE(v.diastolic,"")) AS bp, v.pulse, v.weight_kg, v.height_cm FROM visits v LEFT JOIN patients p ON p.id=v.patient_id ORDER BY v.visit_date DESC, v.id DESC');
    foreach ($rows as $r) { fputcsv($out, $r); }
    exit;
}

fputcsv($out, ['hn','full_name','gender','age','phone','disease','care_area','department','hospital','ward','risk_score','risk_level','payment_method','insurance_detail','created_at']);
$rows = db_is_connected()
    ? db_fetch_all('SELECT hn, full_name, gender, age, phone, disease, care_area, department, hospital, ward, risk_score, risk_level, payment_method, insurance_detail, created_at FROM patients ORDER BY hn ASC')
    : demo_patients();
foreach ($rows as $r) {
    fputcsv($out, [
        $r['hn'] ?? '', $r['full_name'] ?? '', $r['gender'] ?? '', $r['age'] ?? '', $r['phone'] ?? '', $r['disease'] ?? '', $r['care_area'] ?? '', $r['department'] ?? '', $r['hospital'] ?? '', $r['ward'] ?? '', $r['risk_score'] ?? '', $r['risk_level'] ?? '', $r['payment_method'] ?? '', $r['insurance_detail'] ?? '', $r['created_at'] ?? ''
    ]);
}
