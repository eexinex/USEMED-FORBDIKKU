<?php
// public/admin/ml-refresh.php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/shared/layout.php';
require_once __DIR__ . '/../../backend/shared/ai_engine.php';

require_login('admin');

$limit = max(1, min(20, (int) ($_GET['limit'] ?? $_POST['limit'] ?? 10)));
$offset = max(0, (int) ($_GET['offset'] ?? $_POST['offset'] ?? 0));
$processed = 0;
$mlBacked = 0;
$fallback = 0;
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

if (is_post() && db_is_connected()) {
    $patients = db_fetch_all(
        'SELECT * FROM patients ORDER BY id ASC LIMIT ' . $limit . ' OFFSET ' . $offset
    );

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
$buttonOffset = is_post() ? $nextOffset : $offset;
$buttonEnd = min($totalPatients, $buttonOffset + $limit);
$isComplete = $totalPatients > 0 && $cachedMl >= $totalPatients;

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
            Refresh predictions in small batches so Hugging Face does not time out.
            Keep clicking the next batch button until Cached ML equals Patients.
        </p>

        <?php if (!db_is_connected()): ?>
            <div class="notice bad mt-2">Database is not connected. Check DB secrets on Hugging Face.</div>
        <?php elseif ($isComplete): ?>
            <div class="notice success mt-2">All patients have XGBoost cached predictions.</div>
        <?php else: ?>
            <form method="post" class="mt-2">
                <input type="hidden" name="offset" value="<?= e((string) $buttonOffset) ?>">
                <input type="hidden" name="limit" value="<?= e((string) $limit) ?>">
                <button class="btn" type="submit">
                    Refresh next batch: <?= e((string) $buttonOffset) ?> - <?= e((string) $buttonEnd) ?>
                </button>
            </form>

            <form method="post" class="mt-1">
                <input type="hidden" name="offset" value="0">
                <input type="hidden" name="limit" value="<?= e((string) $limit) ?>">
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
page_end();
