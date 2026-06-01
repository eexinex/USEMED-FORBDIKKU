<?php
// public/patient/documents.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('patient');

$user = current_user();

$patient = demo_patient();
$documents = demo_documents();

if (db_is_connected()) {
    $patientRow = db_fetch_one(
        'SELECT * FROM patients WHERE hn = :hn LIMIT 1',
        ['hn' => $user['hn'] ?? 'HN0001']
    );

    if ($patientRow) {
        $patient = array_merge($patient, $patientRow);

        $rows = db_fetch_all(
            'SELECT *
             FROM documents
             WHERE patient_id = :patient_id
             ORDER BY created_at DESC, id DESC',
            ['patient_id' => (int) $patientRow['id']]
        );

        if (!empty($rows)) {
            $documents = $rows;
        }
    }
}

page_start('เอกสารของฉัน', 'patient', 'documents');

topbar(
    'My Documents',
    'เอกสารสุขภาพ ผลตรวจ ใบนัด และสรุปการรักษาของคุณ'
);
?>

<section class="stat-grid">
    <?php stat_card('เอกสารทั้งหมด', (string) count($documents), 'Documents'); ?>
    <?php stat_card('HN', $patient['hn'] ?? 'HN0001', 'Patient ID'); ?>
    <?php stat_card('ผู้ป่วย', $patient['full_name'] ?? '-', 'Owner'); ?>
    <?php stat_card('สถานะ', 'พร้อมใช้งาน', 'Medical Files'); ?>
</section>

<section class="grid grid-2">
    <div class="card">
        <h2>ข้อมูลผู้ป่วย</h2>
        <p class="text-muted">
            เอกสารทั้งหมดในหน้านี้ผูกกับบัญชีผู้ป่วยของคุณ
        </p>

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
                    <strong>โรคประจำตัว</strong>
                    <span><?= e($patient['disease'] ?? '-') ?></span>
                </div>
                <span class="badge red">Chronic</span>
            </div>

            <div class="document-card">
                <div>
                    <strong>นัดหมายถัดไป</strong>
                    <span><?= e($patient['next_appointment'] ?? '-') ?></span>
                </div>
                <span class="badge green">Follow-up</span>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>เมนูลัด</h2>
        <p class="text-muted">
            เปิด Timeline หรือกลับไปหน้าหลัก Patient Portal
        </p>

        <div class="document-grid mt-2">
            <a class="document-card" href="<?= e(app_url('patient/portal.php')) ?>">
                <div>
                    <strong>หน้าหลักผู้ป่วย</strong>
                    <span>ดูภาพรวมสุขภาพและข้อมูลล่าสุด</span>
                </div>
                <span class="badge blue">Portal</span>
            </a>

            <a class="document-card" href="<?= e(app_url('patient/timeline.php')) ?>">
                <div>
                    <strong>Timeline</strong>
                    <span>ดูประวัติการรักษาแบบเรียงตามเวลา</span>
                </div>
                <span class="badge orange">Timeline</span>
            </a>

            <a class="document-card" href="<?= e(app_url('support.php')) ?>">
                <div>
                    <strong>แจ้งปัญหา</strong>
                    <span>แจ้งปัญหาเอกสารหรือข้อมูลไม่ถูกต้อง</span>
                </div>
                <span class="badge red">Support</span>
            </a>
        </div>
    </div>
</section>

<section class="table-card mt-2">
    <div class="topbar">
        <div>
            <h1>รายการเอกสาร</h1>
            <p>ค้นหาและเปิดดูเอกสารสุขภาพของคุณ</p>
        </div>

        <div class="searchbar">
            <input
                type="search"
                data-table-search="patientDocuments"
                placeholder="ค้นหาเอกสาร..."
            >
        </div>
    </div>

    <?php if (empty($documents)): ?>
        <?php render_empty_state('ยังไม่มีเอกสาร', 'เมื่อมีเอกสารสุขภาพ รายการจะแสดงที่นี่'); ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table" id="patientDocuments">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ชื่อเอกสาร</th>
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
                        ?>
                        <tr>
                            <td><?= e($id) ?></td>
                            <td>
                                <strong><?= e($title) ?></strong><br>
                                <span class="text-muted">USE MED Document</span>
                            </td>
                            <td>
                                <span class="badge blue"><?= e($type) ?></span>
                            </td>
                            <td><?= e($date) ?></td>
                            <td>
                                <span class="badge green"><?= e($status) ?></span>
                            </td>
                            <td>
                                <a class="btn secondary" href="<?= e(app_url('patient/document-view.php?id=' . $id)) ?>">
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
        <a class="document-card" href="<?= e(app_url('patient/document-view.php?id=' . $id)) ?>">
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
