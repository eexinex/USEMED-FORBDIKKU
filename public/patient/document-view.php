<?php
// public/patient/document-view.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('patient');

$user = current_user();
$patient = demo_patient();
$documents = demo_documents();

$documentId = (int) ($_GET['id'] ?? 1);
$document = null;

if (db_is_connected()) {
    $patientRow = db_fetch_one(
        'SELECT * FROM patients WHERE hn = :hn LIMIT 1',
        ['hn' => $user['hn'] ?? 'HN0001']
    );

    if ($patientRow) {
        $patient = array_merge($patient, $patientRow);

        $row = db_fetch_one(
            'SELECT *
             FROM documents
             WHERE id = :id
             AND patient_id = :patient_id
             LIMIT 1',
            [
                'id' => $documentId,
                'patient_id' => (int) $patientRow['id'],
            ]
        );

        if ($row) {
            $document = $row;
        }
    }
}

if (!$document) {
    foreach ($documents as $item) {
        if ((int) ($item['id'] ?? 0) === $documentId) {
            $document = $item;
            break;
        }
    }
}

if (!$document) {
    $document = $documents[0] ?? [
        'id' => 1,
        'title' => 'สรุปการรักษา',
        'type' => 'PDF',
        'date' => date('d M Y'),
        'status' => 'พร้อมเปิดดู',
    ];
}

$title = $document['title'] ?? 'เอกสารสุขภาพ';
$type = $document['document_type'] ?? $document['type'] ?? 'PDF';
$date = $document['created_at'] ?? $document['date'] ?? date('d M Y');
$status = $document['status'] ?? 'พร้อมเปิดดู';

page_start('ดูเอกสาร', 'patient', 'documents');

topbar(
    'Document Viewer',
    'ดูเอกสารสุขภาพของคุณจากระบบ USE MED'
);
?>

<section class="stat-grid">
    <?php stat_card('ชื่อเอกสาร', $title, $type); ?>
    <?php stat_card('HN', $patient['hn'] ?? 'HN0001', 'Patient ID'); ?>
    <?php stat_card('ผู้ป่วย', $patient['full_name'] ?? '-', 'Owner'); ?>
    <?php stat_card('สถานะ', $status, 'Document'); ?>
</section>

<section class="grid grid-2">
    <div class="card">
        <h2><?= e($title) ?></h2>
        <p class="text-muted">
            เอกสารนี้เป็นเอกสารสุขภาพของผู้ป่วยในระบบ USE MED
            สามารถใช้ดูรายละเอียด ผลตรวจ ใบนัด หรือสรุปการรักษาได้
        </p>

        <div class="document-grid mt-2">
            <div class="document-card">
                <div>
                    <strong>ชื่อเอกสาร</strong>
                    <span><?= e($title) ?></span>
                </div>
                <span class="badge blue"><?= e($type) ?></span>
            </div>

            <div class="document-card">
                <div>
                    <strong>วันที่เอกสาร</strong>
                    <span><?= e($date) ?></span>
                </div>
                <span class="badge orange">Date</span>
            </div>

            <div class="document-card">
                <div>
                    <strong>สถานะ</strong>
                    <span><?= e($status) ?></span>
                </div>
                <span class="badge green">Ready</span>
            </div>
        </div>

        <div class="btn-row mt-2">
            <button class="btn" type="button" data-print>
                พิมพ์เอกสาร
            </button>

            <a class="btn secondary" href="<?= e(app_url('patient/documents.php')) ?>">
                กลับรายการเอกสาร
            </a>

            <a class="btn secondary" href="<?= e(app_url('patient/portal.php')) ?>">
                กลับหน้าหลัก
            </a>
        </div>
    </div>

    <div class="card">
        <h2>ข้อมูลผู้ป่วย</h2>

        <div class="document-grid mt-2">
            <div class="document-card">
                <div>
                    <strong><?= e($patient['full_name'] ?? '-') ?></strong>
                    <span>HN: <?= e($patient['hn'] ?? '-') ?></span>
                </div>
                <span class="badge blue">Patient</span>
            </div>

            <div class="document-card">
                <div>
                    <strong>อายุ / เพศ</strong>
                    <span><?= e($patient['age'] ?? '-') ?> ปี / <?= e($patient['gender'] ?? '-') ?></span>
                </div>
                <span class="badge green">Info</span>
            </div>

            <div class="document-card">
                <div>
                    <strong>โรคประจำตัว</strong>
                    <span><?= e($patient['disease'] ?? '-') ?></span>
                </div>
                <span class="badge red">Chronic</span>
            </div>
        </div>
    </div>
</section>

<section class="card mt-2">
    <div style="background:#fff;border:1px solid var(--line);border-radius:22px;padding:34px;max-width:860px;margin:auto;">
        <div style="display:flex;justify-content:space-between;gap:18px;border-bottom:2px solid var(--line);padding-bottom:18px;margin-bottom:24px;">
            <div>
                <h1 style="margin:0;">USE MED</h1>
                <p class="text-muted" style="margin:4px 0 0;">
                    Health BDI Hospital System
                </p>
            </div>

            <div style="text-align:right;">
                <strong><?= e($type) ?> DOCUMENT</strong><br>
                <span class="text-muted"><?= e($date) ?></span>
            </div>
        </div>

        <h2 style="margin-top:0;"><?= e($title) ?></h2>

        <table style="width:100%;border-collapse:collapse;margin:20px 0;">
            <tr>
                <td style="padding:10px 0;width:180px;"><strong>HN</strong></td>
                <td style="padding:10px 0;"><?= e($patient['hn'] ?? '-') ?></td>
            </tr>
            <tr>
                <td style="padding:10px 0;"><strong>ชื่อผู้ป่วย</strong></td>
                <td style="padding:10px 0;"><?= e($patient['full_name'] ?? '-') ?></td>
            </tr>
            <tr>
                <td style="padding:10px 0;"><strong>อายุ / เพศ</strong></td>
                <td style="padding:10px 0;">
                    <?= e($patient['age'] ?? '-') ?> ปี / <?= e($patient['gender'] ?? '-') ?>
                </td>
            </tr>
            <tr>
                <td style="padding:10px 0;"><strong>โรคประจำตัว</strong></td>
                <td style="padding:10px 0;"><?= e($patient['disease'] ?? '-') ?></td>
            </tr>
        </table>

        <div style="background:var(--primary-soft);border:1px solid #bcf2e5;border-radius:18px;padding:18px;margin:22px 0;">
            <strong>รายละเอียดเอกสาร</strong>
            <p style="margin:8px 0 0;">
                เอกสารฉบับนี้เป็นตัวอย่างในระบบ USE MED สำหรับแสดงข้อมูลสุขภาพ
                ประวัติการรักษา ผลตรวจ ใบนัดหมาย หรือสรุปการรักษาของผู้ป่วย
            </p>
        </div>

        <h3>สรุปข้อมูล</h3>
        <ul>
            <li>เอกสารนี้เชื่อมกับบัญชีผู้ป่วยใน Patient Portal</li>
            <li>ผู้ป่วยสามารถเปิดดูเอกสารย้อนหลังได้ทุกเมื่อ</li>
            <li>ข้อมูลใช้สำหรับติดตามสุขภาพและประวัติการรักษา</li>
        </ul>

        <div style="display:flex;justify-content:space-between;gap:18px;margin-top:42px;">
            <div>
                <strong>เจ้าของเอกสาร</strong><br>
                <span class="text-muted"><?= e($patient['full_name'] ?? '-') ?></span>
            </div>

            <div style="text-align:right;">
                <strong>ระบบเอกสาร</strong><br>
                <span class="text-muted">USE MED Digital Document</span>
            </div>
        </div>
    </div>
</section>

<?php
page_end();