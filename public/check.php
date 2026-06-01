<?php
// public/check.php
// Robust system checker for USE MED

declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../backend/shared/layout.php';
usemed_ensure_extended_schema();

$root = dirname(__DIR__);

$files = [
    'backend/config.php' => $root . '/backend/config.php',
    'backend/config.local.php หรือ backend/config/local.php' => is_file($root . '/backend/config.local.php') ? $root . '/backend/config.local.php' : $root . '/backend/config/local.php',
    'backend/database/connect.php' => $root . '/backend/database/connect.php',
    'backend/database/schema.sql' => $root . '/backend/database/schema.sql',
    'backend/shared/layout.php' => $root . '/backend/shared/layout.php',
    'public/index.php' => __DIR__ . '/index.php',
    'public/assets/usemed.css' => __DIR__ . '/assets/usemed.css',
    'public/patient/login.php' => __DIR__ . '/patient/login.php',
    'public/doctor/login.php' => __DIR__ . '/doctor/login.php',
];

$requiredTables = [
    'patients',
    'doctors',
    'admin_users',
    'visits',
    'documents',
    'support_tickets',
];

$optionalTables = [
    'treatments',
    'referrals',
    'ai_risk_logs',
    'patient_self_assessments',
    'prescriptions',
    'prescription_items',
    'ems_cases',
    'ai_population_scores',
    'ai_population_reasons',
    'followup_tasks',
];

$dbConnected = false;
$dbError = '';
$currentDb = defined('DB_NAME') ? DB_NAME : '';
$tableStatus = [];

function check_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name'
    );
    $stmt->execute(['table_name' => $table]);
    return (int) $stmt->fetchColumn() > 0;
}

function count_table_rows(PDO $pdo, string $table): ?int
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return null;
    }

    try {
        $stmt = $pdo->query('SELECT COUNT(*) FROM `' . $table . '`');
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return null;
    }
}

try {
    $pdo = db();
    if ($pdo instanceof PDO) {
        $dbConnected = true;
        $currentDb = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();

        foreach (array_merge($requiredTables, $optionalTables) as $table) {
            $exists = check_table_exists($pdo, $table);
            $tableStatus[$table] = [
                'exists' => $exists,
                'rows' => $exists ? count_table_rows($pdo, $table) : null,
                'required' => in_array($table, $requiredTables, true),
            ];
        }
    }
} catch (Throwable $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
}

$requiredOk = true;
foreach ($requiredTables as $table) {
    if (empty($tableStatus[$table]['exists'])) {
        $requiredOk = false;
        break;
    }
}

?><!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ตรวจระบบ | USE MED</title>
    <link rel="stylesheet" href="<?= e(app_url('assets/usemed.css')) ?>?v=step11-check-table-fix">
    <style>
        body { background: linear-gradient(135deg,#eefdfa,#f8fbff); }
        .check-page { max-width: 1180px; margin: 0 auto; padding: 28px 16px 56px; }
        .check-hero { display:flex; justify-content:space-between; gap:16px; align-items:flex-start; margin-bottom:18px; }
        .check-hero h1 { margin:0 0 6px; font-size:34px; }
        .check-hero p { margin:0; color:#64748b; }
        .check-card { background:#fff; border:1px solid #dbeee9; border-radius:24px; padding:22px; box-shadow:0 18px 50px rgba(15,23,42,.08); margin-top:18px; }
        .check-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px; }
        .mini { background:#f8fafc; border:1px solid #e2e8f0; border-radius:18px; padding:16px; }
        .mini span { display:block; color:#64748b; font-size:13px; margin-bottom:6px; }
        .mini strong { font-size:18px; word-break:break-all; }
        .check-table { width:100%; border-collapse:separate; border-spacing:0 10px; }
        .check-table th { text-align:left; color:#64748b; padding:0 14px 4px; font-size:13px; }
        .check-table td { background:#fff; border-top:1px solid #dbeee9; border-bottom:1px solid #dbeee9; padding:14px; vertical-align:middle; }
        .check-table td:first-child { border-left:1px solid #dbeee9; border-radius:16px 0 0 16px; font-weight:800; }
        .check-table td:last-child { border-right:1px solid #dbeee9; border-radius:0 16px 16px 0; }
        .badge { display:inline-flex; align-items:center; justify-content:center; border-radius:999px; padding:7px 12px; font-weight:900; font-size:12px; letter-spacing:.04em; }
        .badge.green { background:#dcfce7; color:#166534; }
        .badge.red { background:#ffe4e6; color:#be123c; }
        .badge.orange { background:#ffedd5; color:#9a3412; }
        .muted { color:#64748b; word-break:break-all; }
        .actions { display:flex; gap:10px; flex-wrap:wrap; }
        .btn-check { display:inline-flex; align-items:center; justify-content:center; min-height:42px; padding:0 16px; border-radius:999px; background:#0f766e; color:white; text-decoration:none; font-weight:900; }
        .btn-check.secondary { background:white; color:#0f766e; border:1px solid #cde7e3; }
        .notice { border-radius:18px; padding:16px 18px; margin-top:14px; font-weight:700; line-height:1.6; }
        .notice.ok { background:#ecfdf5; color:#166534; }
        .notice.warn { background:#fff7ed; color:#9a3412; }
        .notice.bad { background:#fff1f2; color:#be123c; }
        @media(max-width:760px){ .check-hero{display:block}.actions{margin-top:14px}.check-table{font-size:14px} }
    </style>
</head>
<body>
<div class="check-page">
    <div class="check-hero">
        <div>
            <h1>ตรวจระบบ USE MED</h1>
            <p>เช็กไฟล์หลัก ฐานข้อมูล และตารางที่ต้องใช้สำหรับบันทึกจริง</p>
        </div>
        <div class="actions">
            <a class="btn-check secondary" href="<?= e(app_url('index.php')) ?>">หน้าแรก</a>
            <a class="btn-check" href="<?= e(app_url('check.php')) ?>">Refresh</a>
        </div>
    </div>

    <section class="check-card">
        <div class="check-grid">
            <div class="mini"><span>Database</span><strong><?= $dbConnected ? 'CONNECTED ✅' : 'NOT CONNECTED ❌' ?></strong></div>
            <div class="mini"><span>DB_NAME ที่ระบบใช้จริง</span><strong><?= e($currentDb ?: '-') ?></strong></div>
            <div class="mini"><span>Demo Mode</span><strong><?= defined('DEMO_MODE') && DEMO_MODE ? 'ON' : 'OFF' ?></strong></div>
            <div class="mini"><span>PHP</span><strong><?= e(PHP_VERSION) ?></strong></div>
        </div>

        <?php if (!$dbConnected): ?>
            <div class="notice bad">ยังต่อฐานข้อมูลไม่ได้: <?= e($dbError ?: 'ตรวจ DB_HOST / DB_NAME / DB_USER / DB_PASS') ?></div>
        <?php elseif ($requiredOk): ?>
            <div class="notice ok">พร้อมใช้งานแล้ว: ตารางหลักครบ สามารถลองบันทึกผู้ป่วยใหม่ได้</div>
        <?php else: ?>
            <div class="notice warn">ต่อ DB ได้แล้ว แต่ตารางหลักยังไม่ครบ ให้ import <strong>backend/database/schema.sql</strong> เข้า DB นี้อีกครั้ง หรือกดดูใน phpMyAdmin ว่าตารางถูกสร้างใน database <strong><?= e($currentDb) ?></strong> หรือไม่</div>
        <?php endif; ?>
    </section>

    <section class="check-card">
        <h2>ไฟล์หลัก</h2>
        <table class="check-table">
            <thead><tr><th>ไฟล์</th><th>สถานะ</th><th>Path</th></tr></thead>
            <tbody>
                <?php foreach ($files as $name => $path): ?>
                    <?php $ok = is_file($path); ?>
                    <tr>
                        <td><?= e($name) ?></td>
                        <td><span class="badge <?= $ok ? 'green' : 'red' ?>"><?= $ok ? 'FOUND' : 'MISSING' ?></span></td>
                        <td class="muted"><?= e($path) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="check-card">
        <h2>Database Tables</h2>
        <table class="check-table">
            <thead><tr><th>Table</th><th>สถานะ</th><th>Rows</th></tr></thead>
            <tbody>
                <?php foreach ($tableStatus as $table => $info): ?>
                    <tr>
                        <td><?= e($table) ?><?= $info['required'] ? '' : ' <span class="muted">(optional)</span>' ?></td>
                        <td>
                            <span class="badge <?= $info['exists'] ? 'green' : ($info['required'] ? 'red' : 'orange') ?>">
                                <?= $info['exists'] ? 'FOUND' : ($info['required'] ? 'MISSING' : 'OPTIONAL') ?>
                            </span>
                        </td>
                        <td><?= $info['exists'] ? e((string) $info['rows']) : '-' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
</body>
</html>
