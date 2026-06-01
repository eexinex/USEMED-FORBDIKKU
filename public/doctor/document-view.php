<?php
// public/doctor/document-view.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('doctor');

$patient = demo_patient();
$documents = demo_documents();

$documentId = (int) ($_GET['id'] ?? 1);
$document = null;

if (db_is_connected()) {
    $row = db_fetch_one(
        'SELECT 
            d.*,
            p.hn,
            p.full_name,
            p.age,
            p.gender,
            p.disease
         FROM documents d
         INNER JOIN patients p ON p.id = d.patient_id
         WHERE d.id = :id
         LIMIT 1',
        ['id' => $documentId]
    );

    if ($row) {
        $document = $row;
        $patient = [
            'hn' => $row['hn'] ?? 'HN0001',
            'full_name' => $row['full_name'] ?? 'สมชาย ใจดี',
            'age' => $row['age'] ?? 58,
            'gender' => $row['gender'] ?? 'ชาย',
            'disease' => $row['disease'] ?? 'Type 2 Diabetes Mellitus',
        ];
    }
}

if (!$document) {
    foreach ($documents as $item) {
        if ((int) $item['id'] === $documentId) {
            $document = $item;
            break;
        }
    }
}

if (!$document) {
    $document = $documents[0];
}

$title = $document['title'] ?? 'เอกสารสุขภาพ';
$type = $document['document_type'] ?? $document['type'] ?? 'PDF';
$date = $document['created_at'] ?? $document['date'] ?? date('d M Y');

page_start('ดูเอกสาร', 'doctor', 'documents');

topbar(
    'Document Viewer',
    'ดูเอกสารสุขภาพของผู้ป่วยในมุมมองแพทย์'
);
?>

<section class="grid grid-2">
    <div class="card">
        <h2><?= e($title) ?></h2>
        <p class="text-muted">
            เอกสารนี้เป็นตัวอย่างสำหรับระบบ Demo สามารถต่อยอดเป็น PDF จริงหรือไฟล์จากฐานข้อมูลได้
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
                    <strong>ผู้ป่วย</strong>
                    <span><?= e($patient['full_name']) ?> / <?= e($patient['hn']) ?></span>
                </div>
                <span class="badge green">Patient</span>
            </div>

            <div class="document-card">
                <div>
                    <strong>วันที่เอกสาร</strong>
                    <span><?= e($date) ?></span>
                </div>
                <span class="badge orange">Date</span>
            </div>
        </div>

        <div class="btn-row mt-2">
            <button class="btn" type="button" data-print>
                พิมพ์เอกสาร
            </button>

            <a class="btn secondary" href="<?= e(app_url('doctor/documents.php')) ?>">
                กลับรายการเอกสาร
            </a>

            <a class="btn secondary" href="<?= e(app_url('doctor/patient-profile.php')) ?>">
                เปิดโปรไฟล์ผู้ป่วย
            </a>
        </div>
    </div>

    <div class="card">
        <h2>ข้อมูลผู้ป่วย</h2>

        <div class="document-grid mt-2">
            <div class="document-card">
                <div>
                    <strong><?= e($patient['full_name']) ?></strong>
                    <span>HN: <?= e($patient['hn']) ?></span>
                </div>
                <span class="badge blue">Profile</span>
            </div>

            <div class="document-card">
                <div>
                    <strong>อายุ / เพศ</strong>
                    <span><?= e($patient['age']) ?> ปี / <?= e($patient['gender']) ?></span>
                </div>
                <span class="badge green">Info</span>
            </div>

            <div class="document-card">
                <div>
                    <strong>โรคประจำตัว</strong>
                    <span><?= e($patient['disease']) ?></span>
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
                <p class="text-muted" style="margin:4px 0 0;">Health BDI Hospital System</p>
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
                <td style="padding:10px 0;"><?= e($patient['hn']) ?></td>
            </tr>
            <tr>
                <td style="padding:10px 0;"><strong>ชื่อผู้ป่วย</strong></td>
                <td style="padding:10px 0;"><?= e($patient['full_name']) ?></td>
            </tr>
            <tr>
                <td style="padding:10px 0;"><strong>อายุ / เพศ</strong></td>
                <td style="padding:10px 0;"><?= e($patient['age']) ?> ปี / <?= e($patient['gender']) ?></td>
            </tr>
            <tr>
                <td style="padding:10px 0;"><strong>โรคประจำตัว</strong></td>
                <td style="padding:10px 0;"><?= e($patient['disease']) ?></td>
            </tr>
        </table>

        <div style="background:var(--primary-soft);border:1px solid #bcf2e5;border-radius:18px;padding:18px;margin:22px 0;">
            <strong>รายละเอียดเอกสาร</strong>
            <p style="margin:8px 0 0;">
                เอกสารฉบับนี้เป็นตัวอย่างในระบบ USE MED สำหรับแสดงข้อมูลสุขภาพ
                ประวัติการรักษา ผลตรวจ หรือใบนัดหมายของผู้ป่วย
            </p>
        </div>

        <h3>สรุปข้อมูล</h3>
        <ul>
            <li>ผู้ป่วยอยู่ในระบบติดตามต่อเนื่อง</li>
            <li>สามารถดู Timeline และประวัติ Visit เพิ่มเติมได้</li>
            <li>แพทย์สามารถใช้ข้อมูลนี้ประกอบการรักษาและประเมิน AI Risk</li>
        </ul>

        <div style="display:flex;justify-content:space-between;gap:18px;margin-top:42px;">
            <div>
                <strong>แพทย์ผู้ตรวจ</strong><br>
                <span class="text-muted"><?= e(current_user()['name'] ?? 'Doctor') ?></span>
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