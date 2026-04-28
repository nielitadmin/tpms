<?php
/**
 * NIELIT TPS - Database Migration Runner
 * 
 * Usage (CLI):  php database/migrate.php
 * Usage (browser): http://localhost/database/migrate.php
 * 
 * Runs all pending SQL migration files from database/migrations/ in order.
 * Tracks executed migrations in a `migrations` table to avoid re-running.
 */

// ─── Load .env ───────────────────────────────────────────────────────────────
$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    die("[ERROR] .env file not found at: $envPath\n");
}

$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    if (!strpos($line, '=')) continue;
    [$key, $value] = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value);
}

// ─── Connect to DB ───────────────────────────────────────────────────────────
$conn = new mysqli(
    $_ENV['DB_HOST'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS'],
    $_ENV['DB_NAME']
);

if ($conn->connect_error) {
    die("[ERROR] DB Connection Failed: " . $conn->connect_error . "\n");
}

$conn->set_charset('utf8mb4');

// ─── Create migrations tracking table if not exists ──────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS `migrations` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `filename` varchar(255) NOT NULL,
        `executed_at` timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `filename` (`filename`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ─── Get already-executed migrations ─────────────────────────────────────────
$executed = [];
$result = $conn->query("SELECT filename FROM migrations ORDER BY filename ASC");
while ($row = $result->fetch_assoc()) {
    $executed[] = $row['filename'];
}

// ─── Discover migration files ─────────────────────────────────────────────────
$migrationsDir = __DIR__ . '/migrations/';
$files = glob($migrationsDir . '*.sql');
sort($files);

if (empty($files)) {
    echo "[INFO] No migration files found in database/migrations/\n";
    exit;
}

// ─── Run pending migrations ───────────────────────────────────────────────────
$ran = 0;
$skipped = 0;

echo "=== NIELIT TPS Migration Runner ===\n\n";

foreach ($files as $filepath) {
    $filename = basename($filepath);

    if (in_array($filename, $executed)) {
        echo "[SKIP]  $filename (already executed)\n";
        $skipped++;
        continue;
    }

    $sql = file_get_contents($filepath);

    // Strip comments and split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($s) => !empty($s) && !preg_match('/^--/', trim($s))
    );

    $success = true;
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        if (!$conn->query($statement)) {
            echo "[ERROR] $filename — " . $conn->error . "\n";
            echo "        Statement: " . substr($statement, 0, 100) . "...\n";
            $success = false;
            break;
        }
    }

    if ($success) {
        $safe = $conn->real_escape_string($filename);
        $conn->query("INSERT INTO migrations (filename) VALUES ('$safe')");
        echo "[OK]    $filename\n";
        $ran++;
    }
}

// ─── Summary ─────────────────────────────────────────────────────────────────
echo "\n=== Done: $ran migration(s) executed, $skipped skipped ===\n";

$conn->close();
