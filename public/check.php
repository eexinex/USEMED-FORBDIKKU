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
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = :table_name"
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
        $stmt = $pdo->query('SELECT COUNT(*) FROM ' . $table);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return null;
    }
}

try {
    $pdo = db();
    if ($pdo instanceof PDO) {
        $dbConnected = true;
        $currentDb = (string) $pdo->query('SELECT current_database()')->fetchColumn();

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
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = :table_name"
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
        $stmt = $pdo->query('SELECT COUNT(*) FROM ' . $table);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return null;
    }
}

try {
    $pdo = db();
    if ($pdo instanceof PDO) {
        $dbConnected = true;
        $currentDb = (string) $pdo->query('SELECT current_database()')->fetchColumn();

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

page_start('ตรวจระบบ | USE MED', 'guest');
?>
<div style="max-width: 1180px; margin: 0 auto; padding: 28px 16px 56px;">
    <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
        <div>
            <h1 style="margin: 0 0 8px; font-size: 32px; color: var(--ink);">ตรวจระบบ USE MED</h1>
            <p style="margin: 0; color: var(--muted);">เช็กไฟล์หลัก ฐานข้อมูล และตารางที่ต้องใช้สำหรับบันทึกจริง</p>
        </div>
        <div style="display: flex; gap: 12px; align-items: center;">
            <a class="btn" style="background: white; border: 1px solid var(--line); color: var(--primary);" href="<?= e(app_url('index.php')) ?>">หน้าแรก</a>
            <a class="btn btn-primary" href="<?= e(app_url('check.php')) ?>">Refresh</a>
        </div>
    </div>

    <section class="card">
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
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

    <section class="card" style="margin-top: 24px;">
        <h2>ไฟล์หลัก</h2>
        <table class="table">
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

    <section class="card" style="margin-top: 24px;">
        <h2>Database Tables</h2>
        <table class="table">
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
<?php page_end(); ?>
