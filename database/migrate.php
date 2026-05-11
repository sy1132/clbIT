<?php
/**
 * Database Migration Tool
 * Truy cập: http://localhost/clbIT/database/migrate.php
 * Chạy các migration script để cập nhật database
 */

require_once __DIR__ . '/../config/database.php';

$messages = [];
$success = true;

try {
    $pdo = db();
    
    // Migration 1: Add slug to categories
    try {
        $pdo->exec("ALTER TABLE categories ADD COLUMN slug VARCHAR(120) UNIQUE NULL AFTER name");
        $messages[] = "✅ Added slug column to categories";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        $messages[] = "ℹ️ slug column already exists in categories";
    }
    
    // Migration 2: Add slug to posts
    try {
        $pdo->exec("ALTER TABLE posts ADD COLUMN slug VARCHAR(255) UNIQUE NULL AFTER title");
        $messages[] = "✅ Added slug column to posts";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        $messages[] = "ℹ️ slug column already exists in posts";
    }
    
    // Migration 3: Add slug to events
    try {
        $pdo->exec("ALTER TABLE events ADD COLUMN slug VARCHAR(255) UNIQUE NULL AFTER event_name");
        $messages[] = "✅ Added slug column to events";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        $messages[] = "ℹ️ slug column already exists in events";
    }
    
    // Migration 4: Update slug values from existing data
    $pdo->exec("UPDATE categories SET slug = LOWER(REPLACE(REPLACE(REPLACE(name, ' ', '-'), 'ă', 'a'), 'đ', 'd')) WHERE slug IS NULL");
    $pdo->exec("UPDATE posts SET slug = LOWER(REPLACE(REPLACE(REPLACE(title, ' ', '-'), 'ă', 'a'), 'đ', 'd')) WHERE slug IS NULL");
    $pdo->exec("UPDATE events SET slug = LOWER(REPLACE(REPLACE(REPLACE(event_name, ' ', '-'), 'ă', 'a'), 'đ', 'd')) WHERE slug IS NULL");
    $messages[] = "✅ Updated slug values in all tables";
    
    // Migration 5: Make slug columns NOT NULL
    try {
        $pdo->exec("ALTER TABLE categories MODIFY COLUMN slug VARCHAR(120) NOT NULL");
        $pdo->exec("ALTER TABLE posts MODIFY COLUMN slug VARCHAR(255) NOT NULL");
        $pdo->exec("ALTER TABLE events MODIFY COLUMN slug VARCHAR(255) NOT NULL");
        $messages[] = "✅ Set slug columns as NOT NULL";
    } catch (Exception $e) {
        $messages[] = "⚠️ Could not set NOT NULL on slug: " . $e->getMessage();
    }
    
} catch (Exception $e) {
    $messages[] = "❌ Migration failed: " . $e->getMessage();
    $success = false;
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - CLB IT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .migration-container {
            background: white;
            border-radius: 10px;
            padding: 40px;
            max-width: 600px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .migration-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .migration-header h1 {
            color: #667eea;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .migration-header p {
            color: #666;
            font-size: 0.95rem;
        }
        .message-list {
            list-style: none;
            padding: 0;
        }
        .message-list li {
            padding: 12px 15px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            border-radius: 5px;
            font-size: 0.95rem;
            font-family: 'Courier New', monospace;
        }
        .message-list li:before {
            content: '';
        }
        .success-badge {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            margin-top: 20px;
        }
        .error-badge {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            margin-top: 20px;
        }
        .action-links {
            margin-top: 30px;
            text-align: center;
        }
        .action-links a {
            display: inline-block;
            margin: 0 10px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: 0.3s;
        }
        .action-links a:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="migration-container">
        <div class="migration-header">
            <h1>🔧 Database Migration</h1>
            <p>CLB IT Database Schema Update</p>
        </div>
        
        <ul class="message-list">
            <?php foreach ($messages as $msg): ?>
                <li><?php echo htmlspecialchars($msg); ?></li>
            <?php endforeach; ?>
        </ul>
        
        <?php if ($success): ?>
            <div style="text-align: center;">
                <span class="success-badge">✅ Migration Successful!</span>
            </div>
        <?php else: ?>
            <div style="text-align: center;">
                <span class="error-badge">❌ Migration Failed</span>
            </div>
        <?php endif; ?>
        
        <div class="action-links">
            <a href="../index.php">← Back to Home</a>
            <a href="../posts.php">View Posts →</a>
        </div>
    </div>
</body>
</html>
