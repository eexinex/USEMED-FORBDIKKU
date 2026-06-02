#!/usr/bin/env php
<?php
// Refresh cached ML predictions for patients imported into USE MED.

declare(strict_types=1);

require_once __DIR__ . '/../shared/layout.php';
require_once __DIR__ . '/../shared/ai_engine.php';

$limit = (int) ($argv[1] ?? 500);
$limit = max(1, min(5000, $limit));

if (!db_is_connected()) {
    fwrite(STDERR, "Database is not connected.\n");
    exit(1);
}

$patients = db_fetch_all(
    'SELECT * FROM patients ORDER BY high_watch DESC, risk_score DESC, id ASC LIMIT ' . $limit
);

$ok = 0;
foreach ($patients as $patient) {
    $result = usemed_ai_score_patient($patient, true);
    if (!empty($result['model_version']) && str_starts_with((string) $result['model_version'], 'usemed-xgb')) {
        $ok++;
    }
}

echo "Refreshed {$ok}/" . count($patients) . " ML-backed predictions\n";
