<?php
// Run this ONCE to import the database schema on Railway
require_once __DIR__ . '/../config/db_connect.php';

$schemaFile = __DIR__ . '/../database/init.sql';

if (!file_exists($schemaFile)) {
    die("❌ init.sql not found at " . $schemaFile);
}

$sql = file_get_contents($schemaFile);

// Split SQL by semicolons (basic – works for this dump)
$statements = explode(';', $sql);

$success = 0;
$errors = [];

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt)) continue;
    try {
        $pdo->exec($stmt);
        $success++;
    } catch (PDOException $e) {
        $errors[] = $e->getMessage();
    }
}

echo "<h2>Schema import completed</h2>";
echo "Executed $success statements.<br>";
if (count($errors) > 0) {
    echo "<p style='color:orange'>Some errors (may be harmless if tables already exist):</p><ul>";
    foreach (array_slice($errors, 0, 5) as $err) echo "<li>$err</li>";
    echo "</ul>";
}
echo "<a href='login.php'>Go to login →</a>";
?>