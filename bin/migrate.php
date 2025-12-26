#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Database\Database;
use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

$database = new Database(
    $_ENV['DB_HOST'],
    (int) $_ENV['DB_PORT'],
    $_ENV['DB_NAME'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);

$pdo = $database->getConnection();

$pdo->exec("
    CREATE TABLE IF NOT EXISTS schema_migrations (
        version VARCHAR(255) PRIMARY KEY,
        applied_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$stmt = $pdo->query('SELECT version FROM schema_migrations');
$applied = $stmt->fetchAll(PDO::FETCH_COLUMN);

$migrationDir = __DIR__ . '/../migrations';
$files = glob($migrationDir . '/*.sql');
sort($files);

$appliedCount = 0;

foreach ($files as $file) {
    $version = basename($file);

    if (in_array($version, $applied, true)) {
        echo "Skipping {$version} (already applied)\n";
        continue;
    }

    echo "Applying {$version}...\n";

    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new \RuntimeException("Failed to read migration file: {$file}");
    }

    $pdo->beginTransaction();
    try {
        $pdo->exec($sql);
        $stmt = $pdo->prepare('INSERT INTO schema_migrations (version, applied_at) VALUES (?, ?)');
        $stmt->execute([$version, date('Y-m-d H:i:s')]);
        $pdo->commit();
        $appliedCount++;
        echo "Applied {$version}\n";
    } catch (\Exception $e) {
        $pdo->rollBack();
        echo "Error applying {$version}: {$e->getMessage()}\n";
        exit(1);
    }
}

echo "Migration complete. Applied {$appliedCount} migration(s).\n";

