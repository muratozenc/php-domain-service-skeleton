<?php

declare(strict_types=1);

namespace Tests\Helper;

use App\Infrastructure\Database\Database;
use PDO;

final class DatabaseHelper
{
    public static function resetDatabase(Database $database): void
    {
        $pdo = $database->getConnection();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $pdo->exec('TRUNCATE TABLE order_items');
        $pdo->exec('TRUNCATE TABLE order_audit');
        $pdo->exec('TRUNCATE TABLE orders');
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}

