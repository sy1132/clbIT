<?php
// Migration runner - adds missing slug columns

require_once __DIR__ . '/../config/database.php';

$pdo = db();

try {
    // Read migration SQL
    $sql = file_get_contents(__DIR__ . '/migration-add-slug.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            // Skip comments
            if (strpos($statement, '--') === 0) continue;
            
            echo "Executing: " . substr($statement, 0, 80) . "...\n";
            $pdo->exec($statement);
        }
    }
    
    echo "\n✅ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
