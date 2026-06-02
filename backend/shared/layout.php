<?php
// backend/shared/layout.php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/auth.php';

function page_start(string $title = 'USE MED', string $role = 'guest', string $active = ''): void
{
    $flash = flash_get();

    ?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | USE MED</title>
    <!-- DEBUG: APP_URL = "<?= e(APP_URL) ?>" -->
    <!-- DEBUG: SCRIPT_NAME = "<?= e($_SERVER['SCRIPT_NAME'] ?? '') ?>" -->
    <link rel="stylesheet" href="<?= e(app_url('assets/usemed.css')) ?>?v=step24-debug">
</head>
<body class="role-<?= e($role) ?> active-<?= e($active) ?>">
<div class="<?= $role === 'guest' ? 'guest-shell' : 'app-shell' ?>">
    <?php if ($role !== 'guest'): ?>
        <?= render_sidebar($role, $active) ?>
    <?php endif; ?>

    <main class="<?= $role === 'guest' ? 'guest-main' : 'main-content' ?>">
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>
    <?php
}

function page_end(): void
{
    ?>
    </main>
</div>
<script src="<?= e(app_url('assets/app.js')) ?>?v=step23-force-small-hero-20260601" defer></script>
</body>
</html>
    <?php
}

function render_sidebar(string $role, string $active = ''): string
{
    $user = current_user();
    $name = $user['name'] ?? 'User';
    $portal = $role === 'doctor' ? 'Doctor Portal' : ($role === 'patient' ? 'Patient Portal' : 'Admin Portal');
    $status = $role === 'doctor' ? 'ออนไลน์' : ($role === 'patient' ? (($user['hn'] ?? '') ?: 'ผู้ป่วย') : 'ผู้ดูแลระบบ');

    ob_start();
    ?>
    <aside class="sidebar ux-sidebar sidebar-<?= e($role) ?>">
        <a class="brand-block ux-brand" href="<?= e(app_url('index.php')) ?>">
            <div class="brand-logo ux-brand-logo">UM</div>
            <div>
                <strong>USE MED</strong>
                <span><?= e($portal) ?></span>
            </div>
        </a>

        <div class="user-card ux-user-card">
            <div class="user-avatar ux-user-avatar"><?= e(initials($name)) ?></div>
            <div class="ux-user-meta">
                <strong><?= e($name) ?></strong>
                <span><?= e($status) ?></span>
            </div>
        </div>

        <nav class="side-nav ux-side-nav" aria-label="เมนูหลัก">
            <?php foreach (nav_items($role) as $key => $item): ?>
                <a class="<?= e(active_class($active, $key)) ?>" href="<?= e(app_url($item['href'])) ?>">
                    <span class="ux-nav-icon" aria-hidden="true"><?= icon_svg($item['icon'] ?? $key) ?></span>
                    <b><?= e($item['label']) ?></b>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="ux-sidebar-footer">
            <a class="ux-help-card" href="<?= e(app_url('support.php')) ?>">
                <span class="ux-help-icon"><?= icon_svg('headset') ?></span>
                <strong>ต้องการความช่วยเหลือ?</strong>
                <small>ติดต่อเจ้าหน้าที่<br>08:00 – 20:00 น.</small>
            </a>

            <a class="logout-link ux-logout" href="<?= e(app_url($role . '/logout.php')) ?>">
                <span><?= icon_svg('logout') ?></span>
                ออกจากระบบ
            </a>
        </div>
    </aside>
    <?php

    return ob_get_clean();
}

function icon_svg(string $name): string
{
    $icons = [
        'home' => '<svg viewBox="0 0 24 24"><path d="M3 10.8 12 3l9 7.8v9.7a1.5 1.5 0 0 1-1.5 1.5H15v-6H9v6H4.5A1.5 1.5 0 0 1 3 20.5v-9.7Z"/></svg>',
        'assessment' => '<svg viewBox="0 0 24 24"><path d="M4 19h16v2H4v-2Zm2-4 3-3 3 2 5-7 2 1.4-6.2 8.7-3.4-2.3L7.4 17 6 15Z"/><path d="M5 4h3v9H5V4Zm6 2h3v7h-3V6Zm6-3h3v10h-3V3Z" opacity=".55"/></svg>',
        'calendar' => '<svg viewBox="0 0 24 24"><path d="M7 2h2v3h6V2h2v3h2.5A2.5 2.5 0 0 1 22 7.5v12A2.5 2.5 0 0 1 19.5 22h-15A2.5 2.5 0 0 1 2 19.5v-12A2.5 2.5 0 0 1 4.5 5H7V2Zm13 8H4v9.5c0 .3.2.5.5.5h15c.3 0 .5-.2.5-.5V10Z"/></svg>',
        'doc' => '<svg viewBox="0 0 24 24"><path d="M6 2h8l5 5v13.5A1.5 1.5 0 0 1 17.5 22h-11A1.5 1.5 0 0 1 5 20.5v-17A1.5 1.5 0 0 1 6.5 2H6Zm7 1.8V8h4.2L13 3.8ZM8 12h8v2H8v-2Zm0 4h8v2H8v-2Z"/></svg>',
        'message' => '<svg viewBox="0 0 24 24"><path d="M4 5h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H8l-5 3v-3.5A2.5 2.5 0 0 1 2 16.5V7a2 2 0 0 1 2-2Zm1.5 3 6.5 4.5L18.5 8h-13Z"/></svg>',
        'help' => '<svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm0 16.5a1.3 1.3 0 1 1 0-2.6 1.3 1.3 0 0 1 0 2.6Zm1.1-4.4h-2v-.8c0-1.1.6-1.8 1.6-2.4.9-.6 1.4-1 1.4-1.8 0-.9-.7-1.5-1.8-1.5-1 0-1.8.5-2.4 1.3L8.5 7.7A4.4 4.4 0 0 1 12.4 6c2.3 0 3.9 1.3 3.9 3.1 0 1.6-.9 2.4-2.1 3.1-.8.5-1.1.8-1.1 1.5v.4Z"/></svg>',
        'dashboard' => '<svg viewBox="0 0 24 24"><path d="M3 4h8v7H3V4Zm10 0h8v7h-8V4ZM3 13h8v7H3v-7Zm10 0h8v7h-8v-7Z"/></svg>',
        'patient' => '<svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 9a7 7 0 0 1 14 0H5Z"/></svg>',
        'plus' => '<svg viewBox="0 0 24 24"><path d="M11 4h2v7h7v2h-7v7h-2v-7H4v-2h7V4Z"/></svg>',
        'spark' => '<svg viewBox="0 0 24 24"><path d="m12 2 2.3 6.4L21 11l-6.7 2.6L12 20l-2.3-6.4L3 11l6.7-2.6L12 2Z"/><path d="m19 3 .9 2.5L22 6.5l-2.1 1-.9 2.5-.9-2.5-2.1-1 2.1-1L19 3Z" opacity=".6"/></svg>',
        'ambulance' => '<svg viewBox="0 0 24 24"><path d="M3 7h10v10H3V7Zm10 3h4l4 4v3h-2.2a2.8 2.8 0 0 0-5.6 0H10.8a2.8 2.8 0 0 0-5.6 0H3v-2h10v-5Zm2 1.5V14h3.5L16 11.5H15ZM8 18.5A1.5 1.5 0 1 1 8 15a1.5 1.5 0 0 1 0 3.5Zm8 0a1.5 1.5 0 1 1 0-3.5 1.5 1.5 0 0 1 0 3.5ZM7 9h2v2h2v2H9v2H7v-2H5v-2h2V9Z"/></svg>',
        'rx' => '<svg viewBox="0 0 24 24"><path d="M5 3h8a4 4 0 0 1 1.2 7.8l4.8 7.2h-2.7l-4.5-7H8v7H5V3Zm3 2.5v3h4.7a1.5 1.5 0 0 0 0-3H8Zm9 7.5 1.7 1.7L21 12.4l1.4 1.4-2.3 2.3 2.3 2.3-1.4 1.4-2.3-2.3-2.3 2.3-1.4-1.4 2.3-2.3-2.3-2.3L17 13Z"/></svg>',
        'note' => '<svg viewBox="0 0 24 24"><path d="M5 3h14v18H5V3Zm3 4h8v2H8V7Zm0 4h8v2H8v-2Zm0 4h6v2H8v-2Z"/></svg>',
        'transfer' => '<svg viewBox="0 0 24 24"><path d="M7 7h11l-3-3 1.4-1.4L22 8l-5.6 5.4L15 12l3-3H7V7ZM17 17H6l3 3-1.4 1.4L2 16l5.6-5.4L9 12l-3 3h11v2Z"/></svg>',
        'icu' => '<svg viewBox="0 0 24 24"><path d="M2 13h4l2-6 4 12 3-8 2 4h5v2h-6.2l-.6-1.3L12 22 8 11l-.6 2H2v-2Z"/></svg>',
        'users' => '<svg viewBox="0 0 24 24"><path d="M8 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm8-1a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM2 21a6 6 0 0 1 12 0H2Zm12.5 0a7.5 7.5 0 0 0-2.2-4.9A5.5 5.5 0 0 1 22 21h-7.5Z"/></svg>',
        'settings' => '<svg viewBox="0 0 24 24"><path d="M19.4 13.5c.1-.5.1-1 .1-1.5s0-1-.1-1.5l2-1.5-2-3.5-2.4 1a7 7 0 0 0-2.6-1.5L14 2h-4l-.4 2.5A7 7 0 0 0 7 6L4.6 5 2.6 8.5l2 1.5c-.1.5-.1 1-.1 1.5s0 1 .1 1.5l-2 1.5 2 3.5 2.4-1a7 7 0 0 0 2.6 1.5L10 22h4l.4-2.5A7 7 0 0 0 17 18l2.4 1 2-3.5-2-1.5ZM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5Z"/></svg>',
        'headset' => '<svg viewBox="0 0 24 24"><path d="M12 3a8 8 0 0 0-8 8v5a3 3 0 0 0 3 3h2v-7H6v-1a6 6 0 0 1 12 0v1h-3v7h2.5A3.5 3.5 0 0 1 14 22h-3v-2h3a1.5 1.5 0 0 0 1.4-1H18a3 3 0 0 0 3-3v-5a8 8 0 0 0-9-8Z"/></svg>',
        'logout' => '<svg viewBox="0 0 24 24"><path d="M11 3h2v10h-2V3Zm-4.8 3.8 1.4 1.4A6 6 0 1 0 16.4 8l1.4-1.4A8 8 0 1 1 6.2 6.8Z"/></svg>',
    ];

    return $icons[$name] ?? $icons['home'];
}

function nav_items(string $role): array
{
    if ($role === 'patient') {
        return [
            'portal' => ['label' => 'หน้าหลัก', 'href' => 'patient/portal.php', 'icon' => 'home'],
            'self_assessment' => ['label' => 'ประเมินสุขภาพ', 'href' => 'patient/self-assessment.php', 'icon' => 'assessment'],
            'timeline' => ['label' => 'นัดหมายของฉัน', 'href' => 'patient/timeline.php', 'icon' => 'calendar'],
            'documents' => ['label' => 'ผลตรวจ / เอกสาร', 'href' => 'patient/documents.php', 'icon' => 'doc'],
            'support' => ['label' => 'ความช่วยเหลือ', 'href' => 'support.php', 'icon' => 'help'],
        ];
    }

    if ($role === 'doctor') {
        return [
            'ai' => ['label' => 'AI Population', 'href' => 'doctor/population-health.php', 'icon' => 'spark'],
            'dashboard' => ['label' => 'หน้าหลัก', 'href' => 'doctor/dashboard.php', 'icon' => 'home'],
            'patient' => ['label' => 'ข้อมูลผู้ป่วย', 'href' => 'doctor/patient-profile.php', 'icon' => 'patient'],
            'treatment' => ['label' => 'เพิ่มการรักษา', 'href' => 'doctor/add-treatment.php', 'icon' => 'plus'],
            'ems' => ['label' => 'EMS MIST/SBAR', 'href' => 'doctor/ems-handover.php', 'icon' => 'ambulance'],
            'rx' => ['label' => 'ยา/ใบสั่งยา', 'href' => 'doctor/prescriptions.php', 'icon' => 'rx'],
            'progress' => ['label' => 'Progress Note', 'href' => 'doctor/progress-note.php', 'icon' => 'note'],
            'referral' => ['label' => 'ส่งตัว/ส่งต่อ', 'href' => 'doctor/referral.php', 'icon' => 'transfer'],
            'icu' => ['label' => 'IPD / ICU / ผ่าตัด', 'href' => 'doctor/care-list.php?type=IPD', 'icon' => 'icu'],
            'documents' => ['label' => 'เวชระเบียน / เอกสาร', 'href' => 'doctor/documents.php', 'icon' => 'doc'],
        ];
    }

    if ($role === 'admin') {
        return [
            'dashboard' => ['label' => 'Dashboard', 'href' => 'admin/dashboard.php', 'icon' => 'dashboard'],
            'users' => ['label' => 'ผู้ใช้งาน', 'href' => 'admin/users.php', 'icon' => 'users'],
            'tickets' => ['label' => 'Support', 'href' => 'admin/tickets.php', 'icon' => 'help'],
        ];
    }

    return [];
}

function topbar(string $title, string $subtitle = ''): void
{
    $user = current_user();

    ?>
    <div class="topbar">
        <div>
            <h1><?= e($title) ?></h1>
            <?php if ($subtitle !== ''): ?>
                <p><?= e($subtitle) ?></p>
            <?php endif; ?>
        </div>

        <?php if ($user): ?>
            <div class="topbar-user">
                <?= e($user['name'] ?? 'User') ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

function stat_card(string $label, string $value, string $badge = ''): void
{
    ?>
    <div class="stat-card" data-animate>
        <span><?= e($label) ?></span>
        <strong><?= e($value) ?></strong>
        <?php if ($badge !== ''): ?>
            <em><?= e($badge) ?></em>
        <?php endif; ?>
    </div>
    <?php
}

function demo_patients(): array
{
    return [
        ['id'=>1,'hn'=>'HN0001','password'=>'123456','full_name'=>'สมชาย ใจดี','gender'=>'ชาย','age'=>58,'phone'=>'081-234-5678','disease'=>'Type 2 Diabetes Mellitus','risk_level'=>'Medium','risk_score'=>62,'next_appointment'=>'12 มิ.ย. 2026','address'=>'อ.เมือง จ.ขอนแก่น','care_area'=>'OPD','department'=>'อายุรกรรม','ward'=>'OPD เบาหวาน','hospital'=>'โรงพยาบาลขอนแก่น','surgery_status'=>'-','high_watch'=>0],
        ['id'=>2,'hn'=>'HN0002','password'=>'123456','full_name'=>'สมหญิง สุขใจ','gender'=>'หญิง','age'=>64,'phone'=>'082-111-2233','disease'=>'Hypertension / CKD stage 3','risk_level'=>'High','risk_score'=>81,'next_appointment'=>'14 มิ.ย. 2026','address'=>'อ.บ้านไผ่ จ.ขอนแก่น','care_area'=>'IPD','department'=>'อายุรกรรมโรคไต','ward'=>'อายุรกรรมหญิง 5A','hospital'=>'โรงพยาบาลศรีนครินทร์','surgery_status'=>'รอประเมิน','high_watch'=>1],
        ['id'=>3,'hn'=>'HN0003','password'=>'123456','full_name'=>'อนันต์ แสงทอง','gender'=>'ชาย','age'=>42,'phone'=>'083-456-7890','disease'=>'Acute appendicitis','risk_level'=>'Medium','risk_score'=>54,'next_appointment'=>'วันนี้ 15:30','address'=>'เขตลาดกระบัง กรุงเทพฯ','care_area'=>'ผ่าตัด','department'=>'ศัลยกรรมทั่วไป','ward'=>'OR 2','hospital'=>'โรงพยาบาลพระจอมเกล้าเจ้าคุณทหาร','surgery_status'=>'กำลังผ่าตัด','high_watch'=>0],
        ['id'=>4,'hn'=>'HN0004','password'=>'123456','full_name'=>'ปรียา วงศ์สวัสดิ์','gender'=>'หญิง','age'=>36,'phone'=>'084-777-9000','disease'=>'Gallstone','risk_level'=>'Low','risk_score'=>28,'next_appointment'=>'18 มิ.ย. 2026','address'=>'เขตราชเทวี กรุงเทพฯ','care_area'=>'คิวผ่าตัด','department'=>'ศัลยกรรมทางเดินอาหาร','ward'=>'Surgical Queue','hospital'=>'โรงพยาบาลราชวิถี','surgery_status'=>'คิวผ่าตัด 18 มิ.ย. 2026','high_watch'=>0],
        ['id'=>5,'hn'=>'HN0005','password'=>'123456','full_name'=>'วิชัย มั่นคง','gender'=>'ชาย','age'=>71,'phone'=>'085-222-4411','disease'=>'COPD exacerbation','risk_level'=>'High','risk_score'=>88,'next_appointment'=>'ติดตามทุก 4 ชม.','address'=>'เขตปทุมวัน กรุงเทพฯ','care_area'=>'ICU','department'=>'เวชบำบัดวิกฤต','ward'=>'ICU เตียง 7','hospital'=>'โรงพยาบาลจุฬาลงกรณ์ สภากาชาดไทย','surgery_status'=>'-','high_watch'=>1],
        ['id'=>6,'hn'=>'HN0006','password'=>'123456','full_name'=>'กมลชนก รัตนสุข','gender'=>'หญิง','age'=>29,'phone'=>'086-333-5566','disease'=>'Pregnancy with GDM','risk_level'=>'Medium','risk_score'=>60,'next_appointment'=>'20 มิ.ย. 2026','address'=>'อ.น้ำพอง จ.ขอนแก่น','care_area'=>'OPD','department'=>'สูติกรรม','ward'=>'ANC Clinic','hospital'=>'โรงพยาบาลขอนแก่น','surgery_status'=>'-','high_watch'=>0],
        ['id'=>7,'hn'=>'HN0007','password'=>'123456','full_name'=>'ธนกร เพชรดี','gender'=>'ชาย','age'=>55,'phone'=>'087-666-1200','disease'=>'NSTEMI post PCI','risk_level'=>'High','risk_score'=>79,'next_appointment'=>'พรุ่งนี้ 09:00','address'=>'อ.เมือง จ.ขอนแก่น','care_area'=>'IPD','department'=>'อายุรกรรมหัวใจ','ward'=>'CCU Stepdown','hospital'=>'โรงพยาบาลศรีนครินทร์','surgery_status'=>'-','high_watch'=>1],
        ['id'=>8,'hn'=>'HN0008','password'=>'123456','full_name'=>'รัชนี พูลผล','gender'=>'หญิง','age'=>49,'phone'=>'088-989-3311','disease'=>'Breast mass workup','risk_level'=>'Medium','risk_score'=>48,'next_appointment'=>'22 มิ.ย. 2026','address'=>'กรุงเทพมหานคร','care_area'=>'คิวผ่าตัด','department'=>'ศัลยกรรมเต้านม','ward'=>'Surgical Queue','hospital'=>'โรงพยาบาลจุฬาลงกรณ์ สภากาชาดไทย','surgery_status'=>'รอคิวผ่าตัด','high_watch'=>0],
        ['id'=>9,'hn'=>'HN0009','password'=>'123456','full_name'=>'ศุภชัย คำดี','gender'=>'ชาย','age'=>33,'phone'=>'089-100-2000','disease'=>'Trauma observation','risk_level'=>'High','risk_score'=>74,'next_appointment'=>'ติดตามทุก 2 ชม.','address'=>'เขตลาดกระบัง กรุงเทพฯ','care_area'=>'คนไข้เฝ้าระวังสูง','department'=>'เวชศาสตร์ฉุกเฉิน','ward'=>'Observation Unit','hospital'=>'โรงพยาบาลพระจอมเกล้าเจ้าคุณทหาร','surgery_status'=>'รอ CT','high_watch'=>1],
        ['id'=>10,'hn'=>'HN0010','password'=>'123456','full_name'=>'มลฤดี ศรีสุข','gender'=>'หญิง','age'=>61,'phone'=>'080-444-8181','disease'=>'Stroke rehabilitation','risk_level'=>'Medium','risk_score'=>57,'next_appointment'=>'25 มิ.ย. 2026','address'=>'จ.ขอนแก่น','care_area'=>'IPD','department'=>'เวชศาสตร์ฟื้นฟู','ward'=>'Rehab Ward','hospital'=>'โรงพยาบาลขอนแก่น','surgery_status'=>'-','high_watch'=>0],
    ];
}

function demo_patient(?string $hn = null): array
{
    if ($hn === null || $hn === '') {
        $hn = $_GET['hn'] ?? ($_SESSION['user']['hn'] ?? 'HN0001');
    }

    foreach (demo_patients() as $patient) {
        if (strcasecmp((string) $patient['hn'], (string) $hn) === 0) {
            return $patient;
        }
    }

    return demo_patients()[0];
}

function demo_doctors(): array
{
    return [
        ['id'=>1,'username'=>'doctor1','password'=>'123456','full_name'=>'นพ.กิตติ ภัทรเวช','license_no'=>'MD-1026588','department'=>'อายุรกรรม','hospital'=>'โรงพยาบาลขอนแก่น'],
        ['id'=>2,'username'=>'doctor2','password'=>'123456','full_name'=>'พญ.ณิชา ศรีแพทย์','license_no'=>'MD-2047712','department'=>'ศัลยกรรมทั่วไป','hospital'=>'โรงพยาบาลศรีนครินทร์'],
        ['id'=>3,'username'=>'doctor3','password'=>'123456','full_name'=>'นพ.ธนดล วัฒนกุล','license_no'=>'MD-3091188','department'=>'เวชบำบัดวิกฤต','hospital'=>'โรงพยาบาลพระจอมเกล้าเจ้าคุณทหาร'],
    ];
}

function demo_doctor(?string $username = null): array
{
    if ($username === null || $username === '') {
        $username = $_SESSION['user']['username'] ?? 'doctor1';
    }

    foreach (demo_doctors() as $doctor) {
        if (strcasecmp((string) $doctor['username'], (string) $username) === 0) {
            return $doctor;
        }
    }

    return demo_doctors()[0];
}

function demo_departments(): array
{
    return [
        'อายุรกรรม',
        'อายุรกรรมหัวใจ',
        'อายุรกรรมโรคไต',
        'ศัลยกรรมทั่วไป',
        'ศัลยกรรมทางเดินอาหาร',
        'ศัลยกรรมเต้านม',
        'สูติกรรม',
        'เวชศาสตร์ฉุกเฉิน',
        'เวชบำบัดวิกฤต',
        'เวชศาสตร์ฟื้นฟู',
    ];
}

function demo_hospitals(): array
{
    return [
        'โรงพยาบาลขอนแก่น',
        'โรงพยาบาลศรีนครินทร์',
        'โรงพยาบาลพระจอมเกล้าเจ้าคุณทหาร',
        'โรงพยาบาลราชวิถี',
        'โรงพยาบาลจุฬาลงกรณ์ สภากาชาดไทย',
    ];
}

function demo_patient_flow_summary(array $patients = []): array
{
    if (empty($patients)) {
        $patients = demo_patients();
    }

    $keys = ['OPD','IPD','ICU','ผ่าตัด','คิวผ่าตัด','คนไข้เฝ้าระวังสูง'];
    $summary = array_fill_keys($keys, 0);

    foreach ($patients as $patient) {
        $area = (string) ($patient['care_area'] ?? 'OPD');
        if (isset($summary[$area])) {
            $summary[$area]++;
        }
        if (!empty($patient['high_watch']) || str_contains((string) ($patient['risk_level'] ?? ''), 'High') || str_contains((string) ($patient['risk_level'] ?? ''), 'สูง')) {
            $summary['คนไข้เฝ้าระวังสูง']++;
        }
    }

    return $summary;
}


function usemed_care_type_map(): array
{
    return [
        'OPD' => ['label' => 'OPD', 'area' => 'OPD', 'icon' => '⌁', 'tone' => 'blue'],
        'IPD' => ['label' => 'IPD', 'area' => 'IPD', 'icon' => 'IPD', 'tone' => 'green'],
        'ICU' => ['label' => 'ICU', 'area' => 'ICU', 'icon' => 'ICU', 'tone' => 'red'],
        'SURGERY' => ['label' => 'ผ่าตัด', 'area' => 'ผ่าตัด', 'icon' => 'OR', 'tone' => 'orange'],
        'SURGERY_QUEUE' => ['label' => 'คิวผ่าตัด', 'area' => 'คิวผ่าตัด', 'icon' => 'Q', 'tone' => 'orange'],
        'HIGH_WATCH' => ['label' => 'คนไข้เฝ้าระวังสูง', 'area' => 'คนไข้เฝ้าระวังสูง', 'icon' => '!', 'tone' => 'red'],
    ];
}

function usemed_care_type_key_from_label(string $label): string
{
    $label = trim($label);
    foreach (usemed_care_type_map() as $key => $meta) {
        if ($label === $key || $label === $meta['label'] || $label === $meta['area']) {
            return $key;
        }
    }
    return 'OPD';
}

function usemed_care_type_meta(string $type): array
{
    $type = strtoupper(trim($type));
    $map = usemed_care_type_map();
    return $map[$type] ?? $map[usemed_care_type_key_from_label($type)] ?? $map['OPD'];
}

function usemed_days_between(string $start, ?string $end = null): int
{
    try {
        $a = new DateTime($start);
        $b = $end ? new DateTime($end) : new DateTime('2026-05-28');
        return max(1, (int) $a->diff($b)->format('%a') + 1);
    } catch (Throwable $e) {
        return 1;
    }
}

function usemed_patient_followup_details(array $patient): array
{
    $hn = (string) ($patient['hn'] ?? 'HN0001');
    $area = (string) ($patient['care_area'] ?? 'OPD');
    $defaults = [
        'admission_date' => '2026-05-27',
        'expected_discharge_date' => '2026-06-01',
        'discharge_date' => '',
        'length_of_stay' => '1 วัน',
        'last_round_date' => '2026-05-28 09:00',
        'bed_status' => (string) ($patient['ward'] ?? '-'),
        'discharge_status' => 'ยังไม่จำหน่าย',
        'additional_medication' => 'ยังไม่มีคำสั่งยาเพิ่ม',
        'followup_plan' => 'ติดตามอาการตามแผนแพทย์เจ้าของไข้',
        'discharge_plan' => 'ประเมินอาการซ้ำก่อนจำหน่าย',
        'daily_note' => 'อาการทั่วไปคงที่ รอประเมินต่อเนื่อง',
        'operation_name' => '-',
        'operation_date' => '',
        'operation_status' => (string) ($patient['surgery_status'] ?? '-'),
        'operation_size' => '-',
        'icu_day' => '',
        'icu_daily_note' => '-',
        'ventilator_status' => '-',
        'vasopressor_status' => '-',
        'fluid_balance' => '-',
        'line_tube_status' => '-',
        'monitoring_frequency' => 'ประเมินทุก 8 ชม.',
        'escalation_plan' => 'แจ้งแพทย์เวรทันทีหากสัญญาณชีพผิดปกติ',
        'round_focus' => 'ยา อาการ ผลตรวจ และแผนจำหน่าย',
    ];

    $map = [
        'HN0001' => ['admission_date'=>'','expected_discharge_date'=>'','length_of_stay'=>'OPD','last_round_date'=>'2026-05-28 10:20','additional_medication'=>'Metformin เดิม / นัดติดตาม HbA1c','followup_plan'=>'OPD เบาหวาน นัดติดตาม 12 มิ.ย. 2026','daily_note'=>'มาตรวจตามนัด อาการคงที่ ให้คำแนะนำอาหารและยา','round_focus'=>'คุมระดับน้ำตาลและทบทวนการใช้ยา'],
        'HN0002' => ['admission_date'=>'2026-05-24','expected_discharge_date'=>'2026-06-02','length_of_stay'=>'5 วัน','last_round_date'=>'2026-05-28 08:45','additional_medication'=>'เพิ่ม Amlodipine 5 mg OD และปรับ Lasix ตาม I/O','followup_plan'=>'ติดตาม Cr/eGFR, BP, urine output ทุกวัน','discharge_plan'=>'คาดจำหน่ายเมื่อ BP คุมได้และ renal function คงที่','daily_note'=>'BP ยังสูงเป็นช่วง ๆ ปัสสาวะดีขึ้น ไม่มีหอบเหนื่อย','round_focus'=>'ความดัน ไต ยาเพิ่ม และแผนจำหน่าย'],
        'HN0003' => ['admission_date'=>'2026-05-28','expected_discharge_date'=>'2026-05-31','length_of_stay'=>'1 วัน','last_round_date'=>'2026-05-28 13:00','additional_medication'=>'Ceftriaxone + Metronidazole, analgesic PRN','operation_name'=>'Appendectomy','operation_date'=>'2026-05-28','operation_status'=>'กำลังผ่าตัด/รอดู Post-op','operation_size'=>'ผ่าตัดเล็ก-กลาง','followup_plan'=>'ติดตาม pain score, fever, wound, bowel movement','discharge_plan'=>'คาดจำหน่ายหลังรับประทานอาหารได้ ไม่มีไข้','daily_note'=>'เตรียมผ่าตัดครบ Consent เรียบร้อย','round_focus'=>'Post-op complication และแผลผ่าตัด'],
        'HN0004' => ['admission_date'=>'','expected_discharge_date'=>'','length_of_stay'=>'รอคิว','last_round_date'=>'2026-05-28 11:10','additional_medication'=>'ให้ยาแก้ปวด PRN / งด NSAIDs ก่อนผ่าตัดตามแพทย์สั่ง','operation_name'=>'Laparoscopic cholecystectomy','operation_date'=>'2026-06-18','operation_status'=>'รอคิวผ่าตัด','operation_size'=>'ผ่าตัดกลาง','followup_plan'=>'Pre-op lab, CXR, EKG, anesthesia consult','discharge_plan'=>'วางแผน admit ก่อนผ่าตัด 1 วัน','daily_note'=>'รอผล pre-op และยืนยันคิว OR','round_focus'=>'ความพร้อมก่อนผ่าตัดและเอกสาร Consent'],
        'HN0005' => ['admission_date'=>'2026-05-22','expected_discharge_date'=>'ยังประเมินไม่ได้','length_of_stay'=>'7 วัน','last_round_date'=>'2026-05-28 07:30','additional_medication'=>'ปรับยาพ่นขยายหลอดลม / IV steroid / antibiotic ตาม culture','icu_day'=>'ICU day 7','icu_daily_note'=>'หอบลดลงแต่ยังต้องเฝ้าระวัง CO2 retention ใกล้ชิด','ventilator_status'=>'HFNC 50 LPM FiO2 0.45 ยังไม่ใส่ท่อ','vasopressor_status'=>'ไม่ใช้ vasopressor','fluid_balance'=>'+450 ml/24h','line_tube_status'=>'PIV x2, Foley catheter','followup_plan'=>'ABG, CXR, SpO2 continuous, wean oxygen ตามอาการ','discharge_plan'=>'ย้ายออก ICU เมื่อ O2 requirement ลดลงและ ABG ดีขึ้น','daily_note'=>'SpO2 94-96% บน HFNC รู้สึกตัวดี ไม่มี shock','monitoring_frequency'=>'ประเมินทุก 1 ชม.','escalation_plan'=>'หาก SpO2 < 90%, ซึมลง หรือ ABG แย่ แจ้ง intensivist ทันที','round_focus'=>'Respiratory support, infection control, fluid balance'],
        'HN0006' => ['admission_date'=>'','expected_discharge_date'=>'','length_of_stay'=>'OPD','last_round_date'=>'2026-05-28 09:40','additional_medication'=>'ปรับโภชนบำบัด / SMBG / พิจารณา insulin หากน้ำตาลสูงต่อเนื่อง','followup_plan'=>'ANC + GDM clinic นัด 20 มิ.ย. 2026','daily_note'=>'ติดตามน้ำตาลระหว่างตั้งครรภ์ ให้ความรู้เรื่องอาหาร','round_focus'=>'FBS/PP glucose และ fetal wellbeing'],
        'HN0007' => ['admission_date'=>'2026-05-26','expected_discharge_date'=>'2026-06-03','length_of_stay'=>'3 วัน','last_round_date'=>'2026-05-28 08:20','additional_medication'=>'DAPT + Statin high intensity / ปรับ beta-blocker ตาม HR/BP','followup_plan'=>'ติดตาม chest pain, EKG, troponin trend, rehab plan','discharge_plan'=>'คาดจำหน่ายหลังอาการคงที่และวางแผน cardiac rehab','daily_note'=>'ไม่มีเจ็บหน้าอกซ้ำ สัญญาณชีพคงที่หลัง PCI','round_focus'=>'DAPT adherence, recurrent chest pain, rehab'],
        'HN0008' => ['admission_date'=>'','expected_discharge_date'=>'','length_of_stay'=>'รอคิว','last_round_date'=>'2026-05-28 12:10','additional_medication'=>'ยาแก้ปวด PRN / นัด review imaging ก่อนผ่าตัด','operation_name'=>'Excision breast mass / biopsy','operation_date'=>'2026-06-22','operation_status'=>'รอคิวผ่าตัด','operation_size'=>'ผ่าตัดเล็ก','followup_plan'=>'รอผล imaging + anesthesia clearance','discharge_plan'=>'Day surgery หากไม่มีภาวะแทรกซ้อน','daily_note'=>'รอยืนยันคิว OR และเอกสาร consent','round_focus'=>'ผล imaging, consent, วันผ่าตัด'],
        'HN0009' => ['admission_date'=>'2026-05-28','expected_discharge_date'=>'2026-05-30','length_of_stay'=>'1 วัน','last_round_date'=>'2026-05-28 14:00','additional_medication'=>'Analgesic PRN / neuro observation protocol','followup_plan'=>'Neuro sign ทุก 2 ชม., รอ CT brain, observe bleeding','discharge_plan'=>'จำหน่ายได้หาก CT ปกติและอาการคงที่ครบ 24-48 ชม.','daily_note'=>'รู้สึกตัวดี ปวดศีรษะเล็กน้อย ไม่มีอาเจียนพุ่ง','monitoring_frequency'=>'ประเมินทุก 2 ชม.','escalation_plan'=>'หาก GCS ลดลง ปวดศีรษะมาก ชัก หรืออาเจียน แจ้ง ER/ศัลย์ประสาททันที','round_focus'=>'Neurological deterioration และผล CT'],
        'HN0010' => ['admission_date'=>'2026-05-25','expected_discharge_date'=>'2026-06-05','length_of_stay'=>'4 วัน','last_round_date'=>'2026-05-28 10:00','additional_medication'=>'Antiplatelet + Statin / DVT prophylaxis ตามข้อบ่งชี้','followup_plan'=>'กายภาพ daily, swallow assessment, BP control','discharge_plan'=>'วางแผนส่งต่อเวชศาสตร์ฟื้นฟู/บ้านเมื่อ ADL ดีขึ้น','daily_note'=>'อ่อนแรงดีขึ้นเล็กน้อย รับประทานอาหารอ่อนได้','round_focus'=>'Rehab goal, aspiration risk, discharge planning'],
    ];

    $details = array_merge($defaults, $map[$hn] ?? []);

    if (($details['length_of_stay'] ?? '') === '' && !empty($details['admission_date'])) {
        $details['length_of_stay'] = usemed_days_between((string) $details['admission_date']) . ' วัน';
    }

    foreach ($details as $key => $value) {
        if (isset($patient[$key]) && $patient[$key] !== null && $patient[$key] !== '') {
            $details[$key] = (string) $patient[$key];
        }
    }

    if ($area === 'OPD') {
        $details['bed_status'] = 'OPD / Clinic';
        $details['discharge_status'] = 'กลับบ้านหลังตรวจ';
    }

    return $details;
}

function usemed_get_care_patients(string $type): array
{
    $type = usemed_care_type_key_from_label($type);
    if (db_is_connected()) {
        $rows = usemed_db_care_patients($type);
        if (!empty($rows)) {
            return $rows;
        }
    }
    return usemed_demo_care_patients($type);
}

function usemed_demo_care_patients(string $type): array
{
    $meta = usemed_care_type_meta($type);
    $type = usemed_care_type_key_from_label($type);
    $items = [];
    foreach (demo_patients() as $patient) {
        $area = (string) ($patient['care_area'] ?? 'OPD');
        $risk = (string) ($patient['risk_level'] ?? '');
        $include = false;
        if ($type === 'HIGH_WATCH') {
            $include = !empty($patient['high_watch']) || $area === 'คนไข้เฝ้าระวังสูง' || str_contains($risk, 'High') || str_contains($risk, 'สูง');
        } else {
            $include = $area === $meta['area'];
        }
        if ($include) {
            $patient['followup'] = usemed_patient_followup_details($patient);
            $items[] = $patient;
        }
    }
    return $items;
}

function usemed_db_care_patients(string $type): array
{
    if (!db_is_connected()) {
        return [];
    }

    usemed_ensure_extended_schema();
    $meta = usemed_care_type_meta($type);
    $columns = usemed_table_columns('patients');
    $where = [];
    $params = [];

    if ($type === 'HIGH_WATCH') {
        if (in_array('high_watch', $columns, true)) {
            $where[] = 'high_watch = 1';
        }
        if (in_array('care_area', $columns, true)) {
            $where[] = 'care_area = :high_area';
            $params['high_area'] = 'คนไข้เฝ้าระวังสูง';
        }
        if (in_array('risk_level', $columns, true)) {
            $where[] = "risk_level IN ('High', 'สูง')";
        }
    } elseif (in_array('care_area', $columns, true)) {
        $where[] = 'care_area = :area';
        $params['area'] = $meta['area'];
    }

    if (empty($where)) {
        return [];
    }

    $sql = 'SELECT * FROM patients WHERE (' . implode(' OR ', $where) . ') ORDER BY ward ASC, full_name ASC, id ASC';
    $rows = db_fetch_all($sql, $params);
    foreach ($rows as &$row) {
        $row['followup'] = usemed_patient_followup_details($row);
    }
    unset($row);
    return $rows;
}

function demo_visits(?string $hn = null): array
{
    $patient = demo_patient($hn);
    $doctor = demo_doctor();
    $risk = (string) ($patient['risk_level'] ?? 'Medium');
    $score = (int) ($patient['risk_score'] ?? 62);

    $saved = $_SESSION['demo_saved_visits'] ?? [];
    $savedItems = [];
    if (is_array($saved)) {
        foreach ($saved as $item) {
            if ($hn === null || $hn === '' || strcasecmp((string) ($item['hn'] ?? ''), (string) $patient['hn']) === 0) {
                $savedItems[] = $item;
            }
        }
    }

    $items = [
        [
            'id' => 1,
            'date' => '27 พ.ค. 2026',
            'visit_date' => '2026-05-27',
            'title' => 'ตรวจติดตาม / ประเมินอาการล่าสุด',
            'doctor' => $doctor['full_name'],
            'doctor_name' => $doctor['full_name'],
            'diagnosis' => $patient['disease'] ?? 'Follow up',
            'treatment_plan' => 'ติดตามอาการ ปรับแผนยา ให้คำแนะนำการดูแลตัวเอง และนัดตรวจซ้ำตามความเหมาะสม',
            'summary' => 'สถานะ: ' . ($patient['care_area'] ?? 'OPD') . ' | แผนก: ' . ($patient['department'] ?? '-'),
            'chief_complaint' => 'มาติดตามอาการและทบทวนแผนการรักษา',
            'present_illness' => 'ผู้ป่วยมาตามนัด อาการโดยรวมคงที่ ไม่มีอาการฉุกเฉินใหม่',
            'review_of_systems' => 'ไม่มีไข้ ไม่มีเจ็บหน้าอก ไม่มีหอบเหนื่อยเฉียบพลัน',
            'past_history' => $patient['disease'] ?? '-',
            'allergy_history' => 'ไม่มีประวัติแพ้ยาที่ทราบ',
            'current_medications' => 'ยาประจำตามโรคเดิม',
            'family_history' => 'ไม่มีประวัติสำคัญเพิ่มเติม',
            'social_history' => 'ประเมินการดื่มสุรา สูบบุหรี่ และการดูแลตนเองแล้ว',
            'physical_exam' => 'รู้สึกตัวดี สัญญาณชีพคงที่ ตรวจร่างกายไม่พบภาวะฉุกเฉิน',
            'assessment' => 'โรคเดิมอยู่ระหว่างติดตาม ประเมินความเสี่ยงและปรับแผนรักษาตามอาการ',
            'procedure_note' => '-',
            'medication_orders' => 'ให้ยาตามแผนเดิม/ปรับตามผลตรวจ',
            'disposition' => 'นัดติดตาม',
            'triage_level' => 'ไม่เร่งด่วน',
            'risk' => $risk,
            'risk_level' => $risk,
            'risk_score' => $score,
            'hn' => $patient['hn'],
            'full_name' => $patient['full_name'],
            'visit_type' => ($patient['care_area'] ?? 'OPD') === 'IPD' ? 'IPD' : 'OPD',
            'visit_reason' => 'ติดตามอาการและประเมินผลการรักษาล่าสุด',
            'care_area' => $patient['care_area'] ?? 'OPD',
            'hospital' => $patient['hospital'] ?? 'โรงพยาบาลขอนแก่น',
            'payment_method' => 'บัตร 30 บาท / UC',
            'insurance_detail' => 'สิทธิหลักประกันสุขภาพแห่งชาติ',
            'blood_group' => 'O+',
            'weight_kg' => 68,
            'height_cm' => 165,
            'temperature' => 36.8,
            'respiratory_rate' => 18,
            'oxygen_saturation' => 98,
            'alcohol_use' => 'ไม่ดื่ม',
            'smoking_status' => 'ไม่สูบ',
            'has_surgery' => 'ไม่มี',
            'surgery_type' => '-',
            'surgery_note' => '-',
            'has_menstruation' => ($patient['gender'] ?? '') === 'หญิง' ? 'มี/สอบถามแล้ว' : 'ไม่เกี่ยวข้อง',
            'last_menstrual_period' => ($patient['gender'] ?? '') === 'หญิง' ? '2026-05-01' : null,
            'investigations' => 'ตรวจเลือด, ตรวจปัสสาวะ, X-ray',
            'lab_results' => 'CBC, FBS, HbA1c, Lipid profile ส่งตรวจ/ติดตามผล',
            'urine_results' => 'Urinalysis ไม่มีภาวะฉุกเฉินเด่นชัด',
            'xray_results' => 'X-ray ตามข้อบ่งชี้ทางคลินิก',
            'mri_results' => '-',
            'imaging_results' => 'ผลภาพถ่ายทางการแพทย์ประกอบการวินิจฉัย',
            'doctor_education' => 'ให้ความรู้เรื่องการใช้ยา การสังเกตอาการผิดปกติ การมาตามนัด และช่องทางติดต่อโรงพยาบาล',
            'next_appointment_detail' => 'นัดหมายเพิ่มเติมตามแผนรักษา หากมีไข้ หอบ เจ็บหน้าอก หรืออาการแย่ลงให้พบแพทย์ทันที',
            'followup_date' => '2026-06-12',
            'systolic' => 148,
            'diastolic' => 92,
            'pulse' => 78,
            'glucose' => 142,
            'hba1c' => 7.8,
            'bmi' => 27.4,
            'cholesterol' => 218,
        ],
        [
            'id' => 2,
            'date' => '10 เม.ย. 2026',
            'visit_date' => '2026-04-10',
            'title' => 'ติดตามผล Lab / รับยา',
            'doctor' => 'พญ.ณิชา ศรีแพทย์',
            'doctor_name' => 'พญ.ณิชา ศรีแพทย์',
            'diagnosis' => 'Follow up ' . ($patient['disease'] ?? ''),
            'treatment_plan' => 'รับยาเดิม ติดตามผลตรวจ และประเมินอาการซ้ำ',
            'summary' => 'อาการทั่วไปคงที่ นัดติดตามตามแผนการรักษา',
            'risk' => $risk === 'High' ? 'Medium' : 'Low',
            'risk_level' => $risk === 'High' ? 'Medium' : 'Low',
            'risk_score' => max(20, $score - 14),
            'hn' => $patient['hn'],
            'full_name' => $patient['full_name'],
            'visit_type' => 'Follow up',
            'visit_reason' => 'ติดตามผลตรวจเดิมและรับยา',
            'care_area' => 'OPD',
            'hospital' => $patient['hospital'] ?? 'โรงพยาบาลขอนแก่น',
            'payment_method' => 'ประกันสังคม',
            'insurance_detail' => 'ใช้สิทธิประกันสังคมตามโรงพยาบาลที่ระบุ',
            'blood_group' => 'O+',
            'weight_kg' => 68,
            'height_cm' => 165,
            'temperature' => 36.7,
            'respiratory_rate' => 18,
            'oxygen_saturation' => 98,
            'alcohol_use' => 'ดื่มเป็นครั้งคราว',
            'smoking_status' => 'ไม่สูบ',
            'has_surgery' => 'ไม่มี',
            'surgery_type' => '-',
            'surgery_note' => '-',
            'has_menstruation' => ($patient['gender'] ?? '') === 'หญิง' ? 'มี/สอบถามแล้ว' : 'ไม่เกี่ยวข้อง',
            'last_menstrual_period' => ($patient['gender'] ?? '') === 'หญิง' ? '2026-04-03' : null,
            'investigations' => 'ตรวจเลือด, ตรวจปัสสาวะ',
            'lab_results' => 'ตรวจเลือดติดตามโรคประจำตัว',
            'urine_results' => 'ตรวจปัสสาวะตามแผน',
            'xray_results' => '-',
            'mri_results' => '-',
            'imaging_results' => '-',
            'doctor_education' => 'ทบทวนการรับประทานยา อาหาร การออกกำลังกาย และการมาตามนัด',
            'next_appointment_detail' => 'นัดติดตามผลตรวจครั้งถัดไป',
            'followup_date' => '2026-05-27',
            'systolic' => 136,
            'diastolic' => 84,
            'pulse' => 76,
            'glucose' => 126,
            'hba1c' => 7.1,
            'bmi' => 26.8,
            'cholesterol' => 202,
        ],
    ];

    return array_merge($savedItems, $items);
}

function demo_documents(?string $hn = null): array
{
    $patient = demo_patient($hn);
    return [
        ['id'=>1,'title'=>'สรุปการรักษา - ' . $patient['hn'],'date'=>'27 พ.ค. 2026','type'=>'PDF','document_type'=>'PDF','status'=>'พร้อมเปิดดู'],
        ['id'=>2,'title'=>'ผลตรวจเลือด','date'=>'10 เม.ย. 2026','type'=>'PDF','document_type'=>'PDF','status'=>'พร้อมเปิดดู'],
        ['id'=>3,'title'=>'ใบนัดหมาย','date'=>(string) ($patient['next_appointment'] ?? '12 มิ.ย. 2026'),'type'=>'PDF','document_type'=>'PDF','status'=>'พร้อมเปิดดู'],
    ];
}

function demo_referrals(): array
{
    $items = $_SESSION['demo_referrals'] ?? [];
    if (is_array($items) && !empty($items)) {
        return $items;
    }

    return [
        ['id'=>1,'hn'=>'HN0002','patient_name'=>'สมหญิง สุขใจ','from_department'=>'อายุรกรรมโรคไต','to_department'=>'เวชบำบัดวิกฤต','to_doctor'=>'นพ.ธนดล วัฒนกุล','to_hospital'=>'โรงพยาบาลศรีนครินทร์','urgency'=>'ด่วน','reason'=>'ไตวายเฉียบพลันร่วมกับความดันสูง ต้องประเมิน ICU','status'=>'รอรับเคส','created_at'=>'2026-05-28 10:30:00'],
        ['id'=>2,'hn'=>'HN0004','patient_name'=>'ปรียา วงศ์สวัสดิ์','from_department'=>'ศัลยกรรมทั่วไป','to_department'=>'ศัลยกรรมทางเดินอาหาร','to_doctor'=>'พญ.ณิชา ศรีแพทย์','to_hospital'=>'โรงพยาบาลราชวิถี','urgency'=>'ปกติ','reason'=>'ส่งต่อเพื่อวางแผนผ่าตัดถุงน้ำดี','status'=>'นัดหมายแล้ว','created_at'=>'2026-05-28 11:15:00'],
    ];
}

function demo_tickets(): array
{
    return [
        ['id'=>1,'user_role'=>'patient','user_name'=>'สมชาย ใจดี','subject'=>'วันนัดไม่ตรงกับเอกสาร','message'=>'ขอให้ตรวจสอบวันนัดหมายในระบบ','status'=>'open'],
        ['id'=>2,'user_role'=>'doctor','user_name'=>'นพ.กิตติ ภัทรเวช','subject'=>'เปิดเอกสาร PDF ไม่ได้','message'=>'ไม่สามารถเปิดไฟล์เอกสารผู้ป่วยได้','status'=>'closed'],
    ];
}

function demo_visit_types(): array
{
    return [
        'OPD', 'IPD', 'Follow up', 'Emergency', 'Refer',
        'Pre-op', 'Post-op', 'Telemedicine', 'Home care', 'Procedure clinic'
    ];
}

function demo_care_areas(): array
{
    return ['OPD', 'IPD', 'ICU', 'ผ่าตัด', 'คิวผ่าตัด', 'คนไข้เฝ้าระวังสูง'];
}

function demo_payment_methods(): array
{
    return ['เงินสด', 'ประกันส่วนตัว', 'บัตร 30 บาท / UC', 'บัตรประกันสังคม', 'ราชการ', 'รัฐวิสาหกิจ'];
}

function demo_blood_groups(): array
{
    return ['ไม่ทราบ', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
}

function demo_investigation_options(): array
{
    return [
        'CBC' => 'Complete blood count',
        'Blood chemistry' => 'FBS, BUN/Cr, Electrolyte, LFT',
        'HbA1c / Lipid' => 'เบาหวานและไขมัน',
        'Coagulation' => 'PT, INR, aPTT ก่อนผ่าตัด/เลือดออกง่าย',
        'Cardiac marker' => 'Troponin, CK-MB ตามข้อบ่งชี้',
        'ตรวจปัสสาวะ' => 'Urinalysis / Urine protein / Pregnancy test',
        'Pregnancy test' => 'UPT / β-hCG',
        'X-ray' => 'Chest X-ray / Plain film ตามข้อบ่งชี้',
        'CT Scan' => 'CT brain/chest/abdomen ตามข้อบ่งชี้',
        'MRI' => 'MRI brain/spine/joint ตามข้อบ่งชี้',
        'Ultrasound' => 'Ultrasound abdomen/OB/soft tissue',
        'EKG' => 'คลื่นไฟฟ้าหัวใจ',
        'Echo' => 'Echocardiogram',
        'Endoscopy' => 'EGD / Colonoscopy / Bronchoscopy',
        'Pathology' => 'ชิ้นเนื้อ/เซลล์วิทยา',
        'Culture' => 'เพาะเชื้อ/ความไวต่อยา',
        'Other' => 'ตรวจอื่น ๆ ตามข้อบ่งชี้',
    ];
}

function demo_emr_dispositions(): array
{
    return ['กลับบ้าน', 'นัดติดตาม', 'Admit IPD', 'ส่ง ER', 'ส่งต่อแผนกอื่น', 'ส่งต่อโรงพยาบาลอื่น', 'เข้าห้องผ่าตัด', 'ICU', 'เสียชีวิต'];
}

function demo_triage_levels(): array
{
    return ['ไม่เร่งด่วน', 'เร่งด่วนเล็กน้อย', 'เร่งด่วน', 'ฉุกเฉิน', 'วิกฤต'];
}

function demo_allergy_types(): array
{
    return ['ไม่มีประวัติแพ้', 'แพ้ยา', 'แพ้อาหาร', 'แพ้วัคซีน', 'แพ้สารทึบรังสี/Contrast', 'แพ้อื่น ๆ'];
}

function usemed_visit_field(array $visit, string $key, string $default = '-'): string
{
    $value = $visit[$key] ?? $default;
    if ($value === null || $value === '') {
        return $default;
    }
    return (string) $value;
}

function usemed_table_columns(string $table): array
{
    static $cache = [];

    if (isset($cache[$table])) {
        return $cache[$table];
    }

    if (!db_is_connected()) {
        return [];
    }

    $rows = db_fetch_all(
        "SELECT column_name FROM information_schema.columns WHERE table_name = :tbl ORDER BY ordinal_position",
        ['tbl' => $table]
    );
    $columns = [];
    foreach ($rows as $row) {
        if (!empty($row['column_name'])) {
            $columns[] = (string) $row['column_name'];
        }
    }
    $cache[$table] = $columns;
    return $columns;
}

function usemed_insert_available(string $table, array $data): bool
{
    $columns = usemed_table_columns($table);
    if (empty($columns)) {
        return false;
    }

    $filtered = [];
    foreach ($data as $key => $value) {
        if (in_array($key, $columns, true)) {
            $filtered[$key] = $value;
        }
    }

    if (empty($filtered)) {
        return false;
    }

    $names = array_keys($filtered);
    $sql = 'INSERT INTO ' . $table . ' (' . implode(',', $names) . ') VALUES (:' . implode(',:', $names) . ')';
    return db_execute($sql, $filtered);
}

function usemed_ensure_extended_schema(): void
{
    $lockFile = sys_get_temp_dir() . '/usemed_schema_done.lock';
    if (file_exists($lockFile)) {
        return;
    }

    if (!db_is_connected()) {
        return;
    }

    // --- patients columns (PostgreSQL: ADD COLUMN IF NOT EXISTS) ---
    $patientCols = [
        "care_area VARCHAR(80) DEFAULT 'OPD'",
        "hospital VARCHAR(255) DEFAULT NULL",
        "ward VARCHAR(255) DEFAULT NULL",
        "surgery_status VARCHAR(255) DEFAULT NULL",
        "high_watch SMALLINT DEFAULT 0",
        "blood_group VARCHAR(20) DEFAULT NULL",
        "payment_method VARCHAR(100) DEFAULT NULL",
        "insurance_detail VARCHAR(255) DEFAULT NULL",
        "department VARCHAR(255) DEFAULT NULL",
        "risk_level VARCHAR(50) DEFAULT NULL",
        "risk_score INT DEFAULT NULL",
        "admission_date DATE DEFAULT NULL",
        "expected_discharge_date VARCHAR(50) DEFAULT NULL",
        "discharge_date DATE DEFAULT NULL",
        "additional_medication TEXT DEFAULT NULL",
        "operation_name VARCHAR(255) DEFAULT NULL",
        "operation_date DATE DEFAULT NULL",
        "operation_status VARCHAR(255) DEFAULT NULL",
        "operation_size VARCHAR(100) DEFAULT NULL",
        "icu_day VARCHAR(80) DEFAULT NULL",
        "icu_daily_note TEXT DEFAULT NULL",
        "ventilator_status VARCHAR(255) DEFAULT NULL",
        "vasopressor_status VARCHAR(255) DEFAULT NULL",
        "fluid_balance VARCHAR(100) DEFAULT NULL",
        "line_tube_status VARCHAR(255) DEFAULT NULL",
        "followup_plan TEXT DEFAULT NULL",
        "discharge_plan TEXT DEFAULT NULL",
        "daily_note TEXT DEFAULT NULL",
        "monitoring_frequency VARCHAR(120) DEFAULT NULL",
        "escalation_plan TEXT DEFAULT NULL",
        "last_round_date TIMESTAMP DEFAULT NULL",
        "email VARCHAR(255) DEFAULT NULL",
        "id_card VARCHAR(30) DEFAULT NULL",
        "birth_date DATE DEFAULT NULL",
        "allergy_history TEXT DEFAULT NULL",
        "registration_source VARCHAR(80) DEFAULT NULL",
        "registration_status VARCHAR(80) DEFAULT 'active'",
        "consent_accepted_at TIMESTAMP DEFAULT NULL",
        "updated_at TIMESTAMP DEFAULT NULL",
    ];

    foreach ($patientCols as $def) {
        db_execute("ALTER TABLE patients ADD COLUMN IF NOT EXISTS {$def}");
    }

    db_execute("ALTER TABLE doctors ADD COLUMN IF NOT EXISTS hospital VARCHAR(255) DEFAULT NULL");

    // --- visits columns ---
    $visitColumns = [
        "visit_type VARCHAR(80) DEFAULT NULL",
        "visit_reason TEXT DEFAULT NULL",
        "care_area VARCHAR(80) DEFAULT NULL",
        "hospital VARCHAR(255) DEFAULT NULL",
        "payment_method VARCHAR(100) DEFAULT NULL",
        "insurance_detail VARCHAR(255) DEFAULT NULL",
        "blood_group VARCHAR(20) DEFAULT NULL",
        "weight_kg DECIMAL(6,2) DEFAULT NULL",
        "height_cm DECIMAL(6,2) DEFAULT NULL",
        "temperature DECIMAL(4,1) DEFAULT NULL",
        "respiratory_rate INT DEFAULT NULL",
        "oxygen_saturation INT DEFAULT NULL",
        "alcohol_use VARCHAR(80) DEFAULT NULL",
        "smoking_status VARCHAR(80) DEFAULT NULL",
        "has_surgery VARCHAR(30) DEFAULT NULL",
        "surgery_type VARCHAR(80) DEFAULT NULL",
        "surgery_note TEXT DEFAULT NULL",
        "has_menstruation VARCHAR(50) DEFAULT NULL",
        "last_menstrual_period DATE DEFAULT NULL",
        "investigations TEXT DEFAULT NULL",
        "lab_results TEXT DEFAULT NULL",
        "urine_results TEXT DEFAULT NULL",
        "xray_results TEXT DEFAULT NULL",
        "mri_results TEXT DEFAULT NULL",
        "imaging_results TEXT DEFAULT NULL",
        "doctor_education TEXT DEFAULT NULL",
        "next_appointment_detail TEXT DEFAULT NULL",
        "followup_date DATE DEFAULT NULL",
        "chief_complaint TEXT DEFAULT NULL",
        "present_illness TEXT DEFAULT NULL",
        "review_of_systems TEXT DEFAULT NULL",
        "past_history TEXT DEFAULT NULL",
        "past_surgical_history TEXT DEFAULT NULL",
        "allergy_type VARCHAR(120) DEFAULT NULL",
        "allergy_history TEXT DEFAULT NULL",
        "current_medications TEXT DEFAULT NULL",
        "family_history TEXT DEFAULT NULL",
        "social_history TEXT DEFAULT NULL",
        "immunization_history TEXT DEFAULT NULL",
        "pregnancy_status VARCHAR(120) DEFAULT NULL",
        "physical_exam TEXT DEFAULT NULL",
        "physical_exam_general TEXT DEFAULT NULL",
        "physical_exam_heent TEXT DEFAULT NULL",
        "physical_exam_chest_lung TEXT DEFAULT NULL",
        "physical_exam_cvs TEXT DEFAULT NULL",
        "physical_exam_abdomen TEXT DEFAULT NULL",
        "physical_exam_neuro TEXT DEFAULT NULL",
        "physical_exam_extremity TEXT DEFAULT NULL",
        "physical_exam_skin TEXT DEFAULT NULL",
        "provisional_diagnosis TEXT DEFAULT NULL",
        "differential_diagnosis TEXT DEFAULT NULL",
        "final_diagnosis TEXT DEFAULT NULL",
        "icd10_code VARCHAR(50) DEFAULT NULL",
        "assessment TEXT DEFAULT NULL",
        "procedure_name VARCHAR(255) DEFAULT NULL",
        "procedure_note TEXT DEFAULT NULL",
        "anesthesia_type VARCHAR(120) DEFAULT NULL",
        "medication_orders TEXT DEFAULT NULL",
        "nursing_instructions TEXT DEFAULT NULL",
        "consult_request TEXT DEFAULT NULL",
        "disposition VARCHAR(120) DEFAULT NULL",
        "admission_ward VARCHAR(255) DEFAULT NULL",
        "triage_level VARCHAR(80) DEFAULT NULL",
        "red_flags TEXT DEFAULT NULL",
        "consent_status VARCHAR(120) DEFAULT NULL",
    ];

    foreach ($visitColumns as $definition) {
        db_execute("ALTER TABLE visits ADD COLUMN IF NOT EXISTS {$definition}");
    }

    db_execute("ALTER TABLE support_tickets ADD COLUMN IF NOT EXISTS problem_type VARCHAR(100) DEFAULT NULL");
    db_execute("ALTER TABLE support_tickets ADD COLUMN IF NOT EXISTS menu_path VARCHAR(255) DEFAULT NULL");

    // --- CREATE TABLE IF NOT EXISTS (PostgreSQL syntax: SERIAL, no ENGINE) ---

    db_execute("CREATE TABLE IF NOT EXISTS referrals (
        id SERIAL PRIMARY KEY,
        patient_id INT DEFAULT NULL REFERENCES patients(id) ON DELETE SET NULL,
        doctor_id INT DEFAULT NULL REFERENCES doctors(id) ON DELETE SET NULL,
        from_department VARCHAR(255) DEFAULT NULL,
        to_department VARCHAR(255) NOT NULL,
        to_doctor VARCHAR(255) DEFAULT NULL,
        to_hospital VARCHAR(255) NOT NULL,
        urgency VARCHAR(50) DEFAULT 'ปกติ',
        reason TEXT NOT NULL,
        status VARCHAR(80) DEFAULT 'รอรับเคส',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    db_execute("CREATE TABLE IF NOT EXISTS patient_self_assessments (
        id SERIAL PRIMARY KEY,
        patient_id INT DEFAULT NULL REFERENCES patients(id) ON DELETE SET NULL,
        hn VARCHAR(50) DEFAULT NULL,
        systolic INT DEFAULT NULL,
        diastolic INT DEFAULT NULL,
        fasting_glucose DECIMAL(8,2) DEFAULT NULL,
        hba1c DECIMAL(5,2) DEFAULT NULL,
        weight_kg DECIMAL(6,2) DEFAULT NULL,
        height_cm DECIMAL(6,2) DEFAULT NULL,
        bmi DECIMAL(6,2) DEFAULT NULL,
        symptoms TEXT DEFAULT NULL,
        medication_adherence VARCHAR(80) DEFAULT NULL,
        risk_score INT DEFAULT NULL,
        risk_level VARCHAR(50) DEFAULT NULL,
        advice TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    db_execute("CREATE TABLE IF NOT EXISTS prescriptions (
        id SERIAL PRIMARY KEY,
        patient_id INT DEFAULT NULL REFERENCES patients(id) ON DELETE SET NULL,
        doctor_id INT DEFAULT NULL REFERENCES doctors(id) ON DELETE SET NULL,
        visit_id INT DEFAULT NULL REFERENCES visits(id) ON DELETE SET NULL,
        rx_no VARCHAR(80) DEFAULT NULL,
        diagnosis TEXT DEFAULT NULL,
        payment_method VARCHAR(100) DEFAULT NULL,
        note TEXT DEFAULT NULL,
        status VARCHAR(80) DEFAULT 'จ่ายยาแล้ว',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    db_execute("CREATE TABLE IF NOT EXISTS prescription_items (
        id SERIAL PRIMARY KEY,
        prescription_id INT NOT NULL REFERENCES prescriptions(id) ON DELETE CASCADE,
        medication_name VARCHAR(255) NOT NULL,
        strength VARCHAR(100) DEFAULT NULL,
        dose VARCHAR(255) DEFAULT NULL,
        route VARCHAR(80) DEFAULT NULL,
        frequency VARCHAR(255) DEFAULT NULL,
        duration VARCHAR(120) DEFAULT NULL,
        quantity VARCHAR(80) DEFAULT NULL,
        instruction TEXT DEFAULT NULL
    )");

    db_execute("CREATE TABLE IF NOT EXISTS ems_cases (
        id SERIAL PRIMARY KEY,
        patient_id INT DEFAULT NULL REFERENCES patients(id) ON DELETE SET NULL,
        doctor_id INT DEFAULT NULL REFERENCES doctors(id) ON DELETE SET NULL,
        case_type VARCHAR(80) DEFAULT 'medical',
        ems_unit VARCHAR(255) DEFAULT NULL,
        arrival_time TIMESTAMP DEFAULT NULL,
        chief_complaint TEXT DEFAULT NULL,
        mechanism TEXT DEFAULT NULL,
        injuries TEXT DEFAULT NULL,
        signs_vitals TEXT DEFAULT NULL,
        treatment_given TEXT DEFAULT NULL,
        sbar_situation TEXT DEFAULT NULL,
        sbar_background TEXT DEFAULT NULL,
        sbar_assessment TEXT DEFAULT NULL,
        sbar_recommendation TEXT DEFAULT NULL,
        height_cm DECIMAL(6,2) DEFAULT NULL,
        weight_kg DECIMAL(6,2) DEFAULT NULL,
        bp VARCHAR(50) DEFAULT NULL,
        pulse INT DEFAULT NULL,
        rr INT DEFAULT NULL,
        spo2 INT DEFAULT NULL,
        temp DECIMAL(4,1) DEFAULT NULL,
        gcs VARCHAR(20) DEFAULT NULL,
        progress_note TEXT DEFAULT NULL,
        status VARCHAR(80) DEFAULT 'รับเคสใหม่',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    db_execute("CREATE TABLE IF NOT EXISTS ai_population_scores (
        id SERIAL PRIMARY KEY,
        patient_id INT DEFAULT NULL REFERENCES patients(id) ON DELETE SET NULL,
        hn VARCHAR(50) DEFAULT NULL,
        model_version VARCHAR(80) DEFAULT 'rule-v1',
        risk_score INT NOT NULL DEFAULT 0,
        priority_level VARCHAR(20) NOT NULL DEFAULT 'P3',
        priority_label VARCHAR(120) DEFAULT NULL,
        recommended_sla VARCHAR(120) DEFAULT NULL,
        trajectory_status VARCHAR(120) DEFAULT NULL,
        cohort_tags TEXT DEFAULT NULL,
        feature_snapshot TEXT DEFAULT NULL,
        recommendation_summary TEXT DEFAULT NULL,
        calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT NULL,
        UNIQUE (patient_id)
    )");
    db_execute("CREATE INDEX IF NOT EXISTS idx_ai_population_priority ON ai_population_scores (priority_level)");
    db_execute("CREATE INDEX IF NOT EXISTS idx_ai_population_hn ON ai_population_scores (hn)");

    db_execute("CREATE TABLE IF NOT EXISTS ai_population_reasons (
        id SERIAL PRIMARY KEY,
        score_id INT DEFAULT NULL REFERENCES ai_population_scores(id) ON DELETE CASCADE,
        patient_id INT DEFAULT NULL REFERENCES patients(id) ON DELETE SET NULL,
        hn VARCHAR(50) DEFAULT NULL,
        reason_type VARCHAR(80) DEFAULT NULL,
        reason_text TEXT NOT NULL,
        source_feature VARCHAR(120) DEFAULT NULL,
        source_value VARCHAR(255) DEFAULT NULL,
        source_table VARCHAR(120) DEFAULT NULL,
        contribution INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    db_execute("CREATE INDEX IF NOT EXISTS idx_ai_reason_patient ON ai_population_reasons (patient_id)");

    db_execute("CREATE TABLE IF NOT EXISTS followup_tasks (
        id SERIAL PRIMARY KEY,
        patient_id INT DEFAULT NULL REFERENCES patients(id) ON DELETE SET NULL,
        hn VARCHAR(50) DEFAULT NULL,
        priority_level VARCHAR(20) DEFAULT NULL,
        task_type VARCHAR(120) DEFAULT NULL,
        task_title VARCHAR(255) NOT NULL,
        task_detail TEXT DEFAULT NULL,
        due_date DATE DEFAULT NULL,
        assigned_to VARCHAR(255) DEFAULT NULL,
        status VARCHAR(80) DEFAULT 'รอติดตาม',
        source VARCHAR(80) DEFAULT 'AI Population',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT NULL
    )");
    db_execute("CREATE INDEX IF NOT EXISTS idx_followup_patient ON followup_tasks (patient_id)");
    db_execute("CREATE INDEX IF NOT EXISTS idx_followup_status ON followup_tasks (status)");
    file_put_contents($lockFile, '1');
}

function usemed_seed_demo_data(): void
{
    $lockFile = sys_get_temp_dir() . '/usemed_seed_done.lock';
    if (file_exists($lockFile)) {
        return;
    }

    if (!db_is_connected()) {
        return;
    }

    usemed_ensure_extended_schema();

    $columns = usemed_table_columns('patients');

    foreach (demo_patients() as $p) {
        $details = usemed_patient_followup_details($p);
        $patientData = [
            'hn' => $p['hn'],
            'password' => $p['password'] ?? '123456',
            'full_name' => $p['full_name'],
            'gender' => $p['gender'] ?? null,
            'age' => (int) ($p['age'] ?? 0),
            'phone' => $p['phone'] ?? null,
            'disease' => $p['disease'] ?? null,
            'address' => $p['address'] ?? null,
            'care_area' => $p['care_area'] ?? 'OPD',
            'hospital' => $p['hospital'] ?? null,
            'ward' => $p['ward'] ?? null,
            'surgery_status' => $p['surgery_status'] ?? null,
            'high_watch' => !empty($p['high_watch']) ? 1 : 0,
            'department' => $p['department'] ?? null,
            'risk_level' => $p['risk_level'] ?? null,
            'risk_score' => $p['risk_score'] ?? null,
            'admission_date' => ($details['admission_date'] ?? '') !== '' ? $details['admission_date'] : null,
            'expected_discharge_date' => $details['expected_discharge_date'] ?? null,
            'discharge_date' => ($details['discharge_date'] ?? '') !== '' ? $details['discharge_date'] : null,
            'additional_medication' => $details['additional_medication'] ?? null,
            'operation_name' => $details['operation_name'] ?? null,
            'operation_date' => ($details['operation_date'] ?? '') !== '' ? $details['operation_date'] : null,
            'operation_status' => $details['operation_status'] ?? null,
            'operation_size' => $details['operation_size'] ?? null,
            'icu_day' => $details['icu_day'] ?? null,
            'icu_daily_note' => $details['icu_daily_note'] ?? null,
            'ventilator_status' => $details['ventilator_status'] ?? null,
            'vasopressor_status' => $details['vasopressor_status'] ?? null,
            'fluid_balance' => $details['fluid_balance'] ?? null,
            'line_tube_status' => $details['line_tube_status'] ?? null,
            'followup_plan' => $details['followup_plan'] ?? null,
            'discharge_plan' => $details['discharge_plan'] ?? null,
            'daily_note' => $details['daily_note'] ?? null,
            'monitoring_frequency' => $details['monitoring_frequency'] ?? null,
            'escalation_plan' => $details['escalation_plan'] ?? null,
            'last_round_date' => str_replace(' ', ' ', (string) ($details['last_round_date'] ?? '')) ?: null,
        ];

        $filtered = [];
        foreach ($patientData as $key => $value) {
            if (in_array($key, $columns, true)) {
                $filtered[$key] = $value;
            }
        }
        if (!empty($filtered)) {
            $names = array_keys($filtered);
            $updateParts = [];
            foreach ($names as $name) {
                if ($name !== 'hn') {
                    $updateParts[] = $name . ' = EXCLUDED.' . $name;
                }
            }
            $sql = 'INSERT INTO patients (' . implode(',', $names) . ') VALUES (:' . implode(',:', $names) . ') ON CONFLICT (hn) DO UPDATE SET ' . implode(', ', $updateParts);
            db_execute($sql, $filtered);
        }
    }

    foreach (demo_doctors() as $d) {
        db_execute(
            'INSERT INTO doctors (username, password, full_name, license_no, department, hospital)
             VALUES (:username, :password, :full_name, :license_no, :department, :hospital)
             ON CONFLICT (username) DO UPDATE SET
                password = EXCLUDED.password, full_name = EXCLUDED.full_name, license_no = EXCLUDED.license_no,
                department = EXCLUDED.department, hospital = EXCLUDED.hospital',
            [
                'username' => $d['username'],
                'password' => $d['password'] ?? '123456',
                'full_name' => $d['full_name'],
                'license_no' => $d['license_no'] ?? null,
                'department' => $d['department'] ?? null,
                'hospital' => $d['hospital'] ?? null,
            ]
        );
    }

    file_put_contents($lockFile, '1');
}


function usemed_chronic_self_assessment(array $data): array
{
    $score = 0;
    $factors = [];
    $advice = [];

    $sbp = (float) ($data['systolic'] ?? 0);
    $dbp = (float) ($data['diastolic'] ?? 0);
    $glucose = (float) ($data['fasting_glucose'] ?? 0);
    $hba1c = (float) ($data['hba1c'] ?? 0);
    $weight = (float) ($data['weight_kg'] ?? 0);
    $height = (float) ($data['height_cm'] ?? 0);
    $bmi = (float) ($data['bmi'] ?? 0);
    $symptoms = implode(' ', (array) ($data['symptoms'] ?? []));
    $adherence = (string) ($data['medication_adherence'] ?? '');

    if ($bmi <= 0 && $weight > 0 && $height > 0) {
        $bmi = round($weight / (($height / 100) ** 2), 1);
    }

    if ($sbp >= 180 || $dbp >= 120) {
        $score += 35;
        $factors[] = 'ความดันสูงมาก ควรประเมินซ้ำและพิจารณาพบแพทย์ทันทีหากมีอาการผิดปกติ';
        $advice[] = 'วัดความดันซ้ำหลังพัก 5-10 นาที หากยังสูงมากหรือมีเจ็บหน้าอก เหนื่อย แขนขาอ่อนแรง ให้ไปโรงพยาบาลทันที';
    } elseif ($sbp >= 140 || $dbp >= 90) {
        $score += 18;
        $factors[] = 'ความดันอยู่ในช่วงสูง';
        $advice[] = 'ลดเค็ม บันทึกค่าความดันต่อเนื่อง และติดตามตามนัด';
    }

    if ($glucose >= 250) {
        $score += 30;
        $factors[] = 'น้ำตาลปลายนิ้ว/น้ำตาลอดอาหารสูงมาก';
        $advice[] = 'ดื่มน้ำ ตรวจซ้ำ และติดต่อสถานพยาบาลหากมีซึม อาเจียน หอบ หรือปัสสาวะบ่อยมาก';
    } elseif ($glucose >= 126) {
        $score += 16;
        $factors[] = 'ค่าน้ำตาลสูงกว่าช่วงเป้าหมาย';
        $advice[] = 'ทบทวนอาหาร ยา และการออกกำลังกาย พร้อมบันทึกค่าไว้ติดตาม';
    }

    if ($hba1c >= 9) {
        $score += 24;
        $factors[] = 'HbA1c สูงมาก สะท้อนการควบคุมเบาหวานระยะยาวยังไม่ดี';
        $advice[] = 'ควรนัดพบทีมรักษาเพื่อทบทวนแผนยา อาหาร และพฤติกรรม';
    } elseif ($hba1c >= 7) {
        $score += 12;
        $factors[] = 'HbA1c สูงกว่าเป้าหมายทั่วไป';
    }

    if ($bmi >= 30) {
        $score += 12;
        $factors[] = 'BMI อยู่ในกลุ่มอ้วน';
    } elseif ($bmi >= 25) {
        $score += 7;
        $factors[] = 'BMI อยู่ในกลุ่มน้ำหนักเกิน';
    }

    if (str_contains($symptoms, 'เจ็บหน้าอก') || str_contains($symptoms, 'หอบ') || str_contains($symptoms, 'อ่อนแรง') || str_contains($symptoms, 'ซึม')) {
        $score += 25;
        $factors[] = 'มีอาการเตือนที่ควรประเมินโดยบุคลากรทางการแพทย์';
        $advice[] = 'หากอาการเป็นมากขึ้นหรือเกิดเฉียบพลัน ควรไปห้องฉุกเฉิน/โทร EMS';
    }

    if (str_contains($adherence, 'ลืม') || str_contains($adherence, 'ไม่สม่ำเสมอ')) {
        $score += 8;
        $factors[] = 'รับประทานยาไม่สม่ำเสมอ';
        $advice[] = 'ตั้งเตือนกินยา หรือปรึกษาเภสัชกร/แพทย์หากมีผลข้างเคียง';
    }

    $score = min(100, max(0, $score));
    if ($score >= 70) {
        $level = 'สูง';
        $color = 'red';
    } elseif ($score >= 40) {
        $level = 'ปานกลาง';
        $color = 'orange';
    } else {
        $level = 'ต่ำ';
        $color = 'green';
    }

    if (empty($factors)) {
        $factors[] = 'ยังไม่พบสัญญาณเสี่ยงเด่นจากข้อมูลที่กรอก';
    }
    if (empty($advice)) {
        $advice[] = 'ดูแลอาหาร ออกกำลังกายสม่ำเสมอ กินยาตามแพทย์สั่ง และติดตามตามนัด';
        $advice[] = 'บันทึกค่าความดัน/น้ำตาลไว้เทียบแนวโน้มระยะยาว';
    }

    return [
        'score' => $score,
        'level' => $level,
        'color' => $color,
        'bmi' => $bmi,
        'factors' => $factors,
        'advice' => $advice,
        'summary' => 'ผลนี้เป็นการประเมินเพื่อให้คำแนะนำส่วนตัว ไม่ส่งข้อมูลไปยังแพทย์โดยอัตโนมัติ',
    ];
}

function usemed_mist_labels(string $caseType): array
{
    $caseType = strtolower($caseType);
    if ($caseType === 'trauma') {
        return [
            'mechanism' => 'M: Mechanism กลไกการบาดเจ็บ',
            'injuries' => 'I: Injuries บาดเจ็บที่พบ',
            'signs' => 'S: Signs/Vital signs อาการและสัญญาณชีพ',
            'treatment' => 'T: Treatment การรักษาที่ EMS ให้แล้ว',
        ];
    }
    return [
        'mechanism' => 'M: Medical illness / เหตุเจ็บป่วย',
        'injuries' => 'I: Inspection / อาการสำคัญที่ตรวจพบ',
        'signs' => 'S: Signs/Vital signs อาการและสัญญาณชีพ',
        'treatment' => 'T: Treatment การรักษาที่ EMS ให้แล้ว',
    ];
}

function usemed_population_recommendation(array $patient): array
{
    $score = (int) ($patient['risk_score'] ?? 0);
    $age = (int) ($patient['age'] ?? 0);
    $disease = strtolower((string) ($patient['disease'] ?? ''));
    $area = (string) ($patient['care_area'] ?? 'OPD');
    $reasons = [];
    $actions = [];

    if ($score >= 80 || !empty($patient['high_watch']) || $area === 'ICU') {
        $priority = 'P1';
        $level = 'เร่งด่วนมาก';
        $reasons[] = 'อยู่ในกลุ่มเฝ้าระวังสูง/ICU หรือ risk score สูง';
        $actions[] = 'ติดตามภายในวันนี้และ review medication/lab ล่าสุด';
    } elseif ($score >= 65 || $area === 'IPD') {
        $priority = 'P2';
        $level = 'ควรติดตามก่อน';
        $reasons[] = 'มี risk score ปานกลางถึงสูงหรือกำลังนอนโรงพยาบาล';
        $actions[] = 'นัด follow-up ระยะสั้นและตรวจแนวโน้ม vital/lab';
    } else {
        $priority = 'P3';
        $level = 'ติดตามตามรอบ';
        $reasons[] = 'ยังไม่พบสัญญาณเสี่ยงเร่งด่วนจากข้อมูลล่าสุด';
        $actions[] = 'ติดตามตามนัดและส่งความรู้สุขภาพแบบรายบุคคล';
    }

    if (str_contains($disease, 'diabetes') || str_contains($disease, 'gdm') || str_contains($disease, 'เบาหวาน')) {
        $reasons[] = 'มีโรคเบาหวาน/ภาวะน้ำตาลสูง ต้องติดตาม HbA1c และ adherence';
        $actions[] = 'จัด cohort เบาหวานและส่งคำแนะนำอาหาร/ยา';
    }
    if (str_contains($disease, 'hypertension') || str_contains($disease, 'ความดัน') || str_contains($disease, 'ckd')) {
        $reasons[] = 'มีความดัน/ไต ต้องติดตาม BP, Cr/eGFR และ urine';
        $actions[] = 'ติดตามความดันที่บ้านและตรวจ lab ตามรอบ';
    }
    if ($age >= 60) {
        $reasons[] = 'อายุมากกว่า 60 ปี เพิ่มความเสี่ยงต่อภาวะแทรกซ้อน';
    }

    return [
        'priority' => $priority,
        'level' => $level,
        'reasons' => array_values(array_unique($reasons)),
        'actions' => array_values(array_unique($actions)),
    ];
}

function render_empty_state(string $title, string $message): void
{
    ?>
    <div class="empty-state">
        <div class="empty-icon">🗂️</div>
        <h3><?= e($title) ?></h3>
        <p><?= e($message) ?></p>
    </div>
    <?php
}