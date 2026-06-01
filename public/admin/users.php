<?php
// public/admin/users.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';

require_login('admin');
usemed_ensure_extended_schema();
usemed_seed_demo_data();

$patients = demo_patients();
$doctors = demo_doctors();
$admins = [['id'=>1,'username'=>'admin','full_name'=>'USE MED Admin','role'=>'admin']];

if (db_is_connected()) {
    $dbPatients = db_fetch_all('SELECT id, hn, full_name, gender, age, phone, email, care_area, hospital, payment_method, registration_source, registration_status, created_at FROM patients ORDER BY id DESC LIMIT 50');
    $dbDoctors = db_fetch_all('SELECT id, username, full_name, license_no, department, hospital, created_at FROM doctors ORDER BY id DESC LIMIT 50');
    $dbAdmins = db_fetch_all('SELECT id, username, full_name, created_at FROM admin_users ORDER BY id DESC LIMIT 50');
    if (!empty($dbPatients)) { $patients = $dbPatients; }
    if (!empty($dbDoctors)) { $doctors = $dbDoctors; }
    if (!empty($dbAdmins)) { $admins = $dbAdmins; }
}

page_start('ผู้ใช้งาน', 'admin', 'users');
topbar('ผู้ใช้งานทั้งหมด', 'ดูบัญชีผู้ป่วย แพทย์ และผู้ดูแลระบบ');
?>

<section class="stat-grid">
    <?php stat_card('ผู้ป่วย', (string) count($patients), 'Patients'); ?>
    <?php stat_card('แพทย์', (string) count($doctors), 'Doctors'); ?>
    <?php stat_card('Admin', (string) count($admins), 'Admins'); ?>
    <?php stat_card('สถานะ DB', db_is_connected() ? 'Connected' : 'Demo', 'System'); ?>
</section>

<section class="table-card mt-2">
    <div class="topbar"><div><h1>ผู้ป่วย</h1><p>HN / สิทธิ / โรงพยาบาล / สถานะผู้ป่วย / แหล่งลงทะเบียน</p></div><div class="searchbar"><input type="search" data-table-search="adminPatients" placeholder="ค้นหาผู้ป่วย..."></div></div>
    <div class="table-wrap"><table class="table" id="adminPatients"><thead><tr><th>HN</th><th>ชื่อ</th><th>ข้อมูล</th><th>สิทธิ</th><th>ลงทะเบียน</th><th>จัดการ</th></tr></thead><tbody>
        <?php foreach ($patients as $p): ?>
            <tr>
                <td><strong><?= e($p['hn'] ?? '-') ?></strong></td>
                <td><?= e($p['full_name'] ?? '-') ?><br><span class="text-muted"><?= e(($p['gender'] ?? '-') . ' · ' . ($p['age'] ?? '-') . ' ปี') ?></span></td>
                <td><?= e(($p['care_area'] ?? 'OPD') . ' · ' . ($p['hospital'] ?? '-')) ?><br><span class="text-muted"><?= e($p['email'] ?? '') ?></span></td>
                <td><?= e($p['payment_method'] ?? '-') ?></td>
                <td>
                    <span class="badge <?= e(($p['registration_source'] ?? '') === 'patient_self' ? 'green' : 'blue') ?>">
                        <?= e(($p['registration_source'] ?? '') === 'patient_self' ? 'สมัครเอง' : 'เจ้าหน้าที่') ?>
                    </span><br>
                    <span class="text-muted"><?= e($p['registration_status'] ?? 'active') ?></span>
                </td>
                <td><a class="btn secondary" href="<?= e(app_url('doctor/patient-profile.php?hn=' . urlencode((string) ($p['hn'] ?? '')))) ?>">เปิด</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody></table></div>
</section>

<section class="table-card mt-2">
    <div class="topbar"><div><h1>แพทย์</h1><p>บัญชีแพทย์และแผนก</p></div><div class="searchbar"><input type="search" data-table-search="adminDoctors" placeholder="ค้นหาแพทย์..."></div></div>
    <div class="table-wrap"><table class="table" id="adminDoctors"><thead><tr><th>Username</th><th>ชื่อแพทย์</th><th>แผนก</th><th>โรงพยาบาล</th><th>License</th></tr></thead><tbody>
        <?php foreach ($doctors as $d): ?>
            <tr>
                <td><strong><?= e($d['username'] ?? '-') ?></strong></td>
                <td><?= e($d['full_name'] ?? '-') ?></td>
                <td><?= e($d['department'] ?? '-') ?></td>
                <td><?= e($d['hospital'] ?? '-') ?></td>
                <td><?= e($d['license_no'] ?? '-') ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody></table></div>
</section>

<section class="table-card mt-2">
    <div class="topbar"><div><h1>Admin</h1><p>ผู้ดูแลระบบ</p></div></div>
    <div class="table-wrap"><table class="table"><thead><tr><th>Username</th><th>ชื่อ</th><th>สร้างเมื่อ</th></tr></thead><tbody>
        <?php foreach ($admins as $a): ?>
            <tr><td><strong><?= e($a['username'] ?? '-') ?></strong></td><td><?= e($a['full_name'] ?? '-') ?></td><td><?= e($a['created_at'] ?? '-') ?></td></tr>
        <?php endforeach; ?>
    </tbody></table></div>
</section>

<?php page_end();
