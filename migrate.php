<?php
declare(strict_types=1);

use App\Application\Bootstrap;

require __DIR__ . '/vendor/autoload.php';

$bootstrap = new Bootstrap(__DIR__ . '/.env', false);
$db = $bootstrap->get('db')->pdo();

// --- 1. get migrations list ---
$migrationsDir = __DIR__ . '/migrations';
$files = glob($migrationsDir . '/*.sql');
sort($files);

// --- 2. run migrations ---
foreach ($files as $file) {
    $filename = basename($file);

    if ($filename === '000_create_migrations_table.sql') {
        echo "[run] $filename ... ";
        $sql = file_get_contents($file);
        $db->exec($sql);
        echo "OK\n";
        continue;
    }

    // --- 3. check if table migrations exits ---
    $stmt = $db->query("SHOW TABLES LIKE 'migrations'");
    if (!$stmt->fetch()) {
        echo "[error] Table 'migrations' does not exist. Run 000_create_migrations_table.sql first.\n";
        exit(1);
    }

    $applied = $db->query("SELECT filename FROM migrations")->fetchAll(PDO::FETCH_COLUMN);

    if (in_array($filename, $applied, true)) {
        echo "[skip] $filename already applied\n";
        continue;
    }

    // --- 4. run migration ---
    echo "[run] $filename ... ";
    $sql = file_get_contents($file);

    try {
        $db->exec($sql);
        $stmt = $db->prepare("INSERT INTO migrations (filename) VALUES (:filename)");
        $stmt->execute(['filename' => $filename]);
        echo "OK\n";
    } catch (Throwable $e) {
        echo "[fail] $filename: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "All migrations applied successfully.\n";
