<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Step 1: Starting setup...<br>";

require_once __DIR__ . '/../config/db_connect.php';
echo "Step 2: Database connection loaded.<br>";

$schemaFile = __DIR__ . '/../database/schema.sql';
echo "Step 3: Looking for schema at: " . $schemaFile . "<br>";

if (!file_exists($schemaFile)) {
    die("❌ schema.sql not found at " . $schemaFile);
}

$sql = file_get_contents($schemaFile);
echo "Step 4: Schema file read. Length: " . strlen($sql) . " bytes.<br>";

// Split SQL by semicolon (simple – works for this dump)
$statements = explode(';', $sql);
$success = 0;
echo "Step 5: Found " . count($statements) . " statements.<br>";

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt)) continue;
    try {
        $pdo->exec($stmt);
        $success++;
    } catch (PDOException $e) {
        echo "Error in statement: " . $e->getMessage() . "<br>";
    }
}
echo "Step 6: Executed $success statements successfully.<br>";
echo "<a href='login.php'>Go to login →</a>";
?>