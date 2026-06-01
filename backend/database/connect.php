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

    try {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $pdo;
    } catch (Throwable $e) {
        $failed = true;

        if (DEMO_MODE === true) {
            return null;
        }

        die('Database connection failed: ' . e($e->getMessage()));
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