<?php
// public/admin/dashboard.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('admin');

$user = current_user();

$patientCount = 1;
$doctorCount = 1;
$ticketCount = 2;
$openTicketCount = 1;
$databaseStatus = db_is_connected() ? 'Connected' : 'Demo Mode';

if (db_is_connected()) {
    $patientRow = db_fetch_one('SELECT COUNT(*) AS total FROM patients');
    $doctorRow = db_fetch_one('SELECT COUNT(*) AS total FROM doctors');
    $ticketRow = db_fetch_one('SELECT COUNT(*) AS total FROM support_tickets');
    $openTicketRow = db_fetch_one("SELECT COUNT(*) AS total FROM support_tickets WHERE status = 'open'");

    $patientCount = (int) ($patientRow['total'] ?? 0);
    $doctorCount = (int) ($doctorRow['total'] ?? 0);
    $ticketCount = (int) ($ticketRow['total'] ?? 0);
    $openTicketCount = (int) ($openTicketRow['total'] ?? 0);
}

$tickets = [];

if (db_is_connected()) {
    $tickets = db_fetch_all(
        'SELECT * FROM support_tickets ORDER BY created_at DESC LIMIT 10'
    );
}

if (empty($tickets)) {
    $tickets = demo_tickets();
}

page_start('Admin Dashboard', 'admin', 'dashboard');

topbar(
    'Admin Dashboard',
    'ภาพรวมระบบ USE MED และรายการแจ้งปัญหาจากผู้ใช้งาน'
);
?>

<section class="stat-grid">
    <?php stat_card('ผู้ป่วยทั้งหมด', (string) $patientCount, 'Patients'); ?>
    <?php stat_card('แพทย์ในระบบ', (string) $doctorCount, 'Doctors'); ?>
    <?php stat_card('รายการแจ้งปัญหา', (string) $ticketCount, 'Tickets'); ?>
    <?php stat_card('รอดำเนินการ', (string) $openTicketCount, $databaseStatus); ?>
</section>

<section class="grid grid-2">
    <div class="card">
        <h2>สถานะระบบ</h2>
        <p class="text-muted">
            ใช้หน้านี้สำหรับตรวจสอบภาพรวมระบบ Demo, Database และรายการแจ้งปัญหา
        </p>

        <div class="document-grid mt-2">
            <div class="document-card">
                <div>
                    <strong>Application</strong>
                    <span>USE MED Public Portal</span>
                </div>
                <span class="badge green">Online</span>
            </div>

            <div class="document-card">
                <div>
                    <strong>Database</strong>
                    <span><?= e($databaseStatus) ?></span>
                </div>
                <span class="badge <?= db_is_connected() ? 'green' : 'orange' ?>">
                    <?= e($databaseStatus) ?>
                </span>
            </div>

            <div class="document-card">
                <div>
                    <strong>Frontend Assets</strong>
                    <span>frontend/css/usemed.css และ frontend/js/app.js</span>
                </div>
                <span class="badge blue">Loaded</span>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>บัญชี Demo</h2>
        <p class="text-muted">
            ใช้สำหรับทดสอบระบบก่อนเชื่อมฐานข้อมูลจริง
        </p>

        <div class="document-grid mt-2">
            <div class="document-card">
                <div>
                    <strong>Patient</strong>
                    <span>HN0001 / 123456</span>
                </div>
                <button class="btn secondary" type="button" data-copy="HN0001 / 123456">
                    คัดลอก
                </button>
            </div>

            <div class="document-card">
                <div>
                    <strong>Doctor</strong>
                    <span>doctor1 / 123456</span>
                </div>
                <button class="btn secondary" type="button" data-copy="doctor1 / 123456">
                    คัดลอก
                </button>
            </div>

            <div class="document-card">
                <div>
                    <strong>Admin</strong>
                    <span>admin / admin123</span>
                </div>
                <button class="btn secondary" type="button" data-copy="admin / admin123">
                    คัดลอก
                </button>
            </div>
        </div>
    </div>
</section>

<section class="table-card mt-2">
    <div class="topbar">
        <div>
            <h1>Support Tickets</h1>
            <p>รายการแจ้งปัญหาล่าสุดจากผู้ใช้งาน</p>
        </div>

        <div class="searchbar">
            <input
                type="search"
                data-table-search="adminTickets"
                placeholder="ค้นหา ticket..."
            >
        </div>
    </div>

    <?php if (empty($tickets)): ?>
        <?php render_empty_state('ยังไม่มีรายการแจ้งปัญหา', 'เมื่อมีผู้ใช้แจ้งปัญหา รายการจะแสดงที่นี่'); ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table" id="adminTickets">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ผู้แจ้ง</th>
                        <th>หัวข้อ</th>
                        <th>รายละเอียด</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                        <?php
                        $status = strtolower((string) ($ticket['status'] ?? 'open'));
                        $badge = $status === 'closed' ? 'green' : 'orange';
                        ?>
                        <tr>
                            <td><?= e($ticket['id'] ?? '-') ?></td>
                            <td>
                                <strong><?= e($ticket['user_name'] ?? 'ไม่ระบุ') ?></strong><br>
                                <span class="text-muted"><?= e($ticket['user_role'] ?? 'guest') ?></span>
                            </td>
                            <td><?= e($ticket['subject'] ?? '-') ?></td>
                            <td><?= e($ticket['message'] ?? '-') ?></td>
                            <td>
                                <span class="badge <?= e($badge) ?>">
                                    <?= e($status) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="grid grid-3 mt-2">
    <a class="card" href="<?= e(app_url('index.php')) ?>">
        <h3>หน้าแรก</h3>
        <p>กลับไปดูหน้า Landing Page ของระบบ</p>
    </a>

    <a class="card" href="<?= e(app_url('patient/login.php')) ?>">
        <h3>Patient Portal</h3>
        <p>ทดสอบหน้าเข้าสู่ระบบของผู้ป่วย</p>
    </a>

    <a class="card" href="<?= e(app_url('doctor/login.php')) ?>">
        <h3>Doctor Portal</h3>
        <p>ทดสอบหน้าเข้าสู่ระบบของแพทย์</p>
    </a>

    <a class="card" href="<?= e(app_url('admin/users.php')) ?>">
        <h3>ผู้ใช้งาน</h3>
        <p>ดูรายชื่อคนไข้ หมอ และ Admin ทั้งหมด</p>
    </a>

    <a class="card" href="<?= e(app_url('admin/tickets.php')) ?>">
        <h3>Support Tickets</h3>
        <p>ดูปัญหาเมนูที่ผู้ใช้เข้าไม่ได้และอัปเดตสถานะ</p>
    </a>
</section>

<?php
page_end();