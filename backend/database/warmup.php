#!/usr/bin/env php
<?php
// backend/database/warmup.php
// Keeps the Supabase connection alive and prevents the free-tier DB from pausing.
// Called by supervisord on container start, then every 4 minutes.

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/connect.php';

$interval = (int) ($argv[1] ?? 240); // default 4 minutes

echo "[warmup] Starting DB keep-alive (interval={$interval}s)\n";

while (true) {
    $t0 = microtime(true);

    try {
        $pdo = db();

        if ($pdo) {
            $pdo->query('SELECT 1');
            $ms = round((microtime(true) - $t0) * 1000);
            echo "[warmup] DB ping OK ({$ms}ms)\n";
        } else {
            echo "[warmup] DB unavailable, will retry\n";
        }
    } catch (Throwable $e) {
        echo "[warmup] DB error: " . $e->getMessage() . "\n";
    }

    if ($interval <= 0) {
        break; // one-shot mode
    }

    sleep($interval);
}
