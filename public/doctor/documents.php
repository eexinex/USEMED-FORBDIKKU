<?php
// public/doctor/documents.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('doctor');

$patient = demo_patient();
$documents = demo_documents();

if (db_is_connected()) {
    $rows = db_fetch_all(
        'SELECT 
            d.*,
            p.hn,
            p.full_name
         FROM documents d
         INNER JOIN patients p ON p.id = d.patient_id
         ORDER BY d.created_at DESC, d.id DESC'
    );

    if (!empty($rows)) {
        $documents = $rows;
    }
}

page_start('เอกสารผู้ป่วย', 'doctor', 'documents');

topbar(
    'Medical Documents',
    'ดูเอกสารสุขภาพ ผลตรวจ ใบนัด และสรุปการรักษาของผู้ป่วย'
);
?>

<section class="stat-grid">
    <?php stat_card('เอกสารทั้งหมด', (string) count($documents), 'Documents'); ?>
    <?php stat_card('ผู้ป่วย', $patient['hn'], $patient['full_name']); ?>
    <?php stat_card('ประเภทหลัก', 'PDF', 'Medical File'); ?>
    <?php stat_card('สถานะ', 'พร้อมใช้งาน', 'Demo Mode'); ?>
</section>

<section class="grid grid-2">
    <div class="card">
        <h2>ข้อมูลผู้ป่วย</h2>
        <p class="text-muted">
            เอกสารด้านล่างเป็นเอกสารสุขภาพของผู้ป่วยในระบบ USE MED
        </p>

        <div class="document-grid mt-2">
            <div class="document-card">
                <div>
                    <strong><?= e($patient['full_name']) ?></strong>
                    <span>HN: <?= e($patient['hn']) ?></span>
                </div>
                <span class="badge blue">Patient</span>
            </div>

            <div class="document-card">
                <div>
                    <strong>โรคประจำตัว</strong>
                    <span><?= e($patient['disease']) ?></span>
                </div>
                <span class="badge red">Chronic</span>
            </div>

            <div class="document-card">
                <div>
                    <strong>นัดหมายถัดไป</strong>
                    <span><?= e($patient['next_appointment']) ?></span>
                </div>
                <span class="badge green">Follow-up</span>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>เมนูลัด</h2>
        <p class="text-muted">
            เปิดโปรไฟล์ผู้ป่วย Timeline หรือเพิ่มประวัติการรักษาใหม่
        </p>

        <div class="document-grid mt-2">
            <a class="document-card" href="<?= e(app_url('doctor/patient-profile.php')) ?>">
                <div>
                    <strong>โปรไฟล์ผู้ป่วย</strong>
                    <span>ดูข้อมูลส่วนตัวและประวัติสุขภาพ</span>
                </div>
                <span class="badge blue">Profile</span>
            </a>

            <a class="document-card" href="<?= e(app_url('doctor/timeline.php')) ?>">
                <div>
                    <strong>Timeline</strong>
                    <span>ดูประวัติการรักษาแบบเรียงตามเวลา</span>
                </div>
                <span class="badge orange">Timeline</span>
            </a>

            <a class="document-card" href="<?= e(app_url('doctor/add-treatment.php')) ?>">
                <div>
                    <strong>เพิ่มการรักษา</strong>
                    <span>บันทึก Visit และสร้างเอกสารประกอบ</span>
                </div>
                <span class="badge green">Add</span>
            </a>
        </div>
    </div>
</section>

<section class="table-card mt-2">
    <div class="topbar">
        <div>
            <h1>รายการเอกสาร</h1>
            <p>ค้นหาและเปิดดูเอกสารสุขภาพของผู้ป่วย</p>
        </div>

        <div class="searchbar">
            <input
                type="search"
                data-table-search="doctorDocuments"
                placeholder="ค้นหาเอกสาร..."
            >
        </div>
    </div>

    <?php if (empty($documents)): ?>
        <?php render_empty_state('ยังไม่มีเอกสาร', 'เมื่อมีการเพิ่มเอกสาร รายการจะแสดงที่นี่'); ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table" id="doctorDocuments">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ชื่อเอกสาร</th>
                        <th>ผู้ป่วย</th>
                        <th>ประเภท</th>
                        <th>วันที่</th>
                        <th>สถานะ</th>
                        <th>เปิดดู</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                        <?php
                        $id = (int) ($doc['id'] ?? 1);
                        $title = $doc['title'] ?? 'เอกสารสุขภาพ';
                        $type = $doc['document_type'] ?? $doc['type'] ?? 'PDF';
                        $date = $doc['created_at'] ?? $doc['date'] ?? '-';
                        $status = $doc['status'] ?? 'พร้อมเปิดดู';
                        $patientName = $doc['full_name'] ?? $patient['full_name'];
                        $hn = $doc['hn'] ?? $patient['hn'];
                        ?>
                        <tr>
                            <td><?= e($id) ?></td>
                            <td>
                                <strong><?= e($title) ?></strong><br>
                                <span class="text-muted">USE MED Document</span>
                            </td>
                            <td>
                                <strong><?= e($patientName) ?></strong><br>
                                <span class="text-muted"><?= e($hn) ?></span>
                            </td>
                            <td>
                                <span class="badge blue"><?= e($type) ?></span>
                            </td>
                            <td><?= e($date) ?></td>
                            <td>
                                <span class="badge green"><?= e($status) ?></span>
                            </td>
                            <td>
                                <a class="btn secondary" href="<?= e(app_url('doctor/document-view.php?id=' . $id)) ?>">
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

<section class="grid grid-3 mt-2">
    <?php foreach ($documents as $doc): ?>
        <?php
        $id = (int) ($doc['id'] ?? 1);
        $title = $doc['title'] ?? 'เอกสารสุขภาพ';
        $type = $doc['document_type'] ?? $doc['type'] ?? 'PDF';
        $date = $doc['created_at'] ?? $doc['date'] ?? '-';
        ?>
        <a class="document-card" href="<?= e(app_url('doctor/document-view.php?id=' . $id)) ?>">
            <div style="display:flex;align-items:center;gap:14px;">
                <div class="document-icon">📄</div>
                <div>
                    <strong><?= e($title) ?></strong>
                    <span><?= e($date) ?></span>
                </div>
            </div>
            <span class="badge blue"><?= e($type) ?></span>
        </a>
    <?php endforeach; ?>
</section>

<?php
page_end();