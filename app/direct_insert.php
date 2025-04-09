<?php
/**
 * Direct database insert script to test database connectivity
 * Run with: docker-compose exec php php /var/www/app/direct_insert.php
 */

// Database connection parameters
$host = 'mysql';
$dbname = 'symfony';
$username = 'symfony';
$password = 'symfony';

echo "Starting direct database insert test...\n";

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    echo "Connecting to database at $host...\n";
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "Connected successfully!\n";
    
    // Create timestamp
    $now = date('Y-m-d H:i:s');
    echo "Current timestamp: $now\n";
    
    // Parameters for insert
    $params = [
        'start' => 1,
        'limit_value' => 20,
        'divisor1' => 3,
        'divisor2' => 5,
        'str1' => 'fizz',
        'str2' => 'buzz',
        'hits' => 1,
        'version' => 1,
        'tracking_state' => 'processed',
        'created_at' => $now,
        'updated_at' => $now,
        'processed_at' => $now,
    ];
    
    echo "Preparing parameters:\n";
    print_r($params);
    
    // Build the SQL query
    $columns = implode(', ', array_keys($params));
    $placeholders = implode(', ', array_map(fn($key) => ":$key", array_keys($params)));
    
    $sql = "INSERT INTO fizz_buzz_requests 
            ($columns) 
            VALUES 
            ($placeholders)
            ON DUPLICATE KEY UPDATE
            hits = hits + 1,
            updated_at = :updated_at,
            processed_at = :processed_at,
            version = version + 1";
    
    echo "Executing SQL query:\n$sql\n";
    
    // Execute the query
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    // Check results
    $lastInsertId = $pdo->lastInsertId();
    echo "Query executed successfully!\n";
    echo "Last insert ID: " . ($lastInsertId ?: "No new record, existing record updated") . "\n";
    
    // Verify records in database
    $checkSql = "SELECT COUNT(*) FROM fizz_buzz_requests";
    $count = $pdo->query($checkSql)->fetchColumn();
    echo "Total records in database: $count\n";
    
    // Show the most recent record
    echo "Most recent record:\n";
    $recentSql = "SELECT * FROM fizz_buzz_requests ORDER BY id DESC LIMIT 1";
    $recent = $pdo->query($recentSql)->fetch(PDO::FETCH_ASSOC);
    print_r($recent);
    
    echo "Direct database insert test completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    
    if ($e->getCode() === 2002) {
        echo "Cannot connect to MySQL server. Check that the MySQL server is running and accessible.\n";
    }
    
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 