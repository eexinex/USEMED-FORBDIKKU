<?php
// public/admin/ml-refresh.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';
require_once __DIR__ . '/../../backend/shared/ai_engine.php';

require_login('admin');
if (session_status() === PHP_SESSION_ACTIVE && (is_post() || empty($_SESSION['flash']))) {
    session_write_close();
}

$limit = max(1, min(20, (int) ($_GET['limit'] ?? $_POST['limit'] ?? 10)));
$offset = max(0, (int) ($_GET['offset'] ?? $_POST['offset'] ?? 0));
$autoRun = ((string) ($_GET['auto'] ?? $_POST['auto'] ?? '0')) === '1';
$processed = 0;
$mlBacked = 0;
$fallback = 0;
$batchRan = false;
$errors = [];

$totalPatients = 0;
$cachedMl = 0;
$cachedAny = 0;

if (db_is_connected()) {
    $totalRow = db_fetch_one('SELECT COUNT(*) AS total FROM patients');
    $totalPatients = (int) ($totalRow['total'] ?? 0);

    $cachedAnyRow = db_fetch_one('SELECT COUNT(*) AS total FROM ai_population_scores');
    $cachedAny = (int) ($cachedAnyRow['total'] ?? 0);

    $cachedMlRow = db_fetch_one("SELECT COUNT(*) AS total FROM ai_population_scores WHERE model_version LIKE 'usemed-xgb%'");
    $cachedMl = (int) ($cachedMlRow['total'] ?? 0);
}

if ((is_post() || $autoRun) && db_is_connected() && $offset < $totalPatients) {
    $patients = db_fetch_all(
        'SELECT * FROM patients ORDER BY id ASC LIMIT ' . $limit . ' OFFSET ' . $offset
    );
    $batchRan = true;

    foreach ($patients as $patient) {
        try {
            $result = usemed_ai_score_patient($patient, true);
            $processed++;
            $version = (string) ($result['model_version'] ?? '');
            if (str_starts_with($version, 'usemed-xgb')) {
                $mlBacked++;
            } else {
                $fallback++;
            }
        } catch (Throwable $e) {
            $errors[] = (string) ($patient['hn'] ?? 'unknown') . ': ' . $e->getMessage();
        }
    }

    $cachedAnyRow = db_fetch_one('SELECT COUNT(*) AS total FROM ai_population_scores');
    $cachedAny = (int) ($cachedAnyRow['total'] ?? 0);

    $cachedMlRow = db_fetch_one("SELECT COUNT(*) AS total FROM ai_population_scores WHERE model_version LIKE 'usemed-xgb%'");
    $cachedMl = (int) ($cachedMlRow['total'] ?? 0);
}

$nextOffset = min($totalPatients, $offset + $limit);
$buttonOffset = $batchRan ? $nextOffset : $offset;
$buttonEnd = min($totalPatients, $buttonOffset + $limit);
$isCacheReady = $totalPatients > 0 && $cachedAny >= $totalPatients;
$isMlComplete = $totalPatients > 0 && $cachedMl >= $totalPatients;
$cachePercent = $totalPatients > 0 ? min(100, (int) round(($cachedAny / $totalPatients) * 100)) : 0;
$mlPercent = $totalPatients > 0 ? min(100, (int) round(($cachedMl / $totalPatients) * 100)) : 0;

page_start('ML Refresh', 'admin', 'ml_refresh');
topbar('ML Refresh', 'Refresh cached XGBoost predictions after importing Supabase data.');
?>

<section class="stat-grid">
    <?php stat_card('Patients', (string) $totalPatients, 'Total rows'); ?>
    <?php stat_card('Cached ML', (string) $cachedMl, 'usemed-xgb'); ?>
    <?php stat_card('Cached Any', (string) $cachedAny, 'All versions'); ?>
    <?php stat_card('Last Batch', (string) $processed, $mlBacked . ' ML / ' . $fallback . ' fallback'); ?>
</section>

<section class="grid grid-2">
    <div class="card">
        <h2>Run Batch</h2>
        <p class="text-muted">
            สร้าง cache เป็น batch สั้น ๆ เพื่อให้หน้า AI Population เปิดเร็วทันทีและลดโอกาส Hugging Face timeout.
            Cached Any คือ cache ที่ช่วยความเร็วหน้า doctor ได้ทันที ส่วน Cached ML คือผลจาก XGBoost จริง.
        </p>

        <div class="cache-progress mt-2">
            <div>
                <strong>Speed cache</strong>
                <span><?= e((string) $cachePercent) ?>%</span>
            </div>
            <i><b style="width:<?= e((string) $cachePercent) ?>%"></b></i>
            <small><?= e((string) $cachedAny) ?> / <?= e((string) $totalPatients) ?> patients ready for fast page load</small>
        </div>

        <div class="cache-progress mt-2">
            <div>
                <strong>ML cache</strong>
                <span><?= e((string) $mlPercent) ?>%</span>
            </div>
            <i><b style="width:<?= e((string) $mlPercent) ?>%"></b></i>
            <small><?= e((string) $cachedMl) ?> / <?= e((string) $totalPatients) ?> patients have XGBoost results</small>
        </div>

        <?php if (!db_is_connected()): ?>
            <div class="notice bad mt-2">Database is not connected. Check DB secrets on Hugging Face.</div>
        <?php elseif ($isMlComplete): ?>
            <div class="notice success mt-2">All patients have XGBoost cached predictions.</div>
        <?php else: ?>
            <?php if ($isCacheReady): ?>
                <div class="notice success mt-2">Speed cache is ready. Doctor AI Population should open from cache.</div>
            <?php endif; ?>

            <form id="mlRefreshBatchForm" method="post" class="mt-2" data-loading-title="กำลังสร้าง AI cache" data-loading-detail="ระบบกำลังประเมินผู้ป่วย batch นี้และบันทึก cache">
                <input type="hidden" name="offset" value="<?= e((string) $buttonOffset) ?>">
                <input type="hidden" name="limit" value="<?= e((string) $limit) ?>">
                <input type="hidden" name="auto" value="<?= $autoRun ? '1' : '0' ?>">
                <button class="btn" type="submit">
                    Refresh next batch: <?= e((string) $buttonOffset) ?> - <?= e((string) $buttonEnd) ?>
                </button>
                <?php if (!$autoRun): ?>
                    <a class="btn secondary" href="<?= e(app_url('admin/ml-refresh.php?auto=1&limit=' . urlencode((string) $limit) . '&offset=' . urlencode((string) $buttonOffset))) ?>" data-loading-title="กำลังเริ่ม Auto cache" data-loading-detail="ระบบจะรัน batch ต่อเนื่องจน cache พร้อม">
                        Auto-run batches
                    </a>
                <?php else: ?>
                    <a class="btn secondary" href="<?= e(app_url('admin/ml-refresh.php?limit=' . urlencode((string) $limit) . '&offset=' . urlencode((string) $buttonOffset))) ?>">
                        Stop auto-run
                    </a>
                <?php endif; ?>
            </form>

            <form method="post" class="mt-1" data-loading-title="กำลังเริ่มสร้าง cache ใหม่" data-loading-detail="ระบบกำลังกลับไปเริ่ม batch แรก">
                <input type="hidden" name="offset" value="0">
                <input type="hidden" name="limit" value="<?= e((string) $limit) ?>">
                <input type="hidden" name="auto" value="<?= $autoRun ? '1' : '0' ?>">
                <button class="btn secondary" type="submit">Start from first batch</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Status</h2>
        <div class="document-grid mt-2">
            <div class="document-card">
                <div>
                    <strong>ML Service URL</strong>
                    <span><?= e((string) (function_exists('envv') ? envv('USEMED_ML_URL', 'http://127.0.0.1:8000/predict') : 'http://127.0.0.1:8000/predict')) ?></span>
                </div>
                <span class="badge blue">FastAPI</span>
            </div>
            <div class="document-card">
                <div>
                    <strong>Batch Size</strong>
                    <span><?= e((string) $limit) ?> patients per click</span>
                </div>
                <span class="badge orange">Batch</span>
            </div>
            <div class="document-card">
                <div>
                    <strong>Next Offset</strong>
                    <span><?= e((string) $nextOffset) ?></span>
                </div>
                <span class="badge green">Ready</span>
            </div>
        </div>
    </div>
</section>

<?php if ($errors): ?>
    <section class="card mt-2">
        <h2>Errors</h2>
        <ul class="factor-list">
            <?php foreach (array_slice($errors, 0, 20) as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<?php
if ($autoRun && db_is_connected() && !$isMlComplete && $buttonOffset < $totalPatients): ?>
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('mlRefreshBatchForm');
            if (!form) {
                return;
            }
            window.setTimeout(function () {
                if (window.USEMEDLoading) {
                    window.USEMEDLoading.show('กำลังสร้าง AI cache ต่อ', 'ระบบกำลังรัน batch ถัดไปโดยอัตโนมัติ');
                }
                form.submit();
            }, 650);
        });
    </script>
    <script>
        (function () {
            var nextUrl = <?= json_encode(app_url('admin/ml-refresh.php?auto=1&limit=' . urlencode((string) $limit) . '&offset=' . urlencode((string) $buttonOffset)), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            window.setTimeout(function () {
                window.location.assign(nextUrl);
            }, 1200);
        })();
    </script>
<?php endif;
page_end();
