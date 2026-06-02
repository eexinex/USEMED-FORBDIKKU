<?php
// backend/database/connect.php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

function db(): ?PDO
{
    static $pdo = null;
    static $failed = false;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if ($failed) {
        return null;
    }

    if (DB_HOST === '' || DB_NAME === '' || DB_USER === '') {
        $failed = true;
        return null;
    }

    try {
        $host = DB_HOST;
        $port = defined('DB_PORT') && DB_PORT !== '' ? DB_PORT : '5432';

        if (strpos($host, ':') !== false) {
            $parts = explode(':', $host, 2);
            $host = $parts[0];
            $port = $parts[1];
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;connect_timeout=1',
            $host,
            $port,
            DB_NAME
        );

        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 1,
        ]);

        try {
            $pdo->exec('SET statement_timeout = 2000');
        } catch (Throwable $e) {
            // Some PostgreSQL-compatible providers may not support this setting.
        }

        return $pdo;
    } catch (Throwable $e) {
        $failed = true;
        error_log('Database connection failed: ' . $e->getMessage());
        return null;
    }
}

function db_fetch_one(string $sql, array $params = []): ?array
{
    $pdo = db();

    if (!$pdo) {
        return null;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch();

        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function db_fetch_all(string $sql, array $params = []): array
{
    $pdo = db();

    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function db_execute(string $sql, array $params = []): bool
{
    $pdo = db();

    if (!$pdo) {
        return false;
    }

    try {
        $stmt = $pdo->prepare($sql);

        return $stmt->execute($params);
    } catch (Throwable $e) {
        return false;
    }
}

function db_last_id(): string
{
    $pdo = db();

    if (!$pdo) {
        return '0';
    }

    return $pdo->lastInsertId();
}

function db_is_connected(): bool
{
    return db() instanceof PDO;
}
