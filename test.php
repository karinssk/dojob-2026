<?php
// CloudFlare tunnel support
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = 443;
}

echo "<h1>Server Test Page</h1>";

// Test 1: PHP Version
echo "<h2>1. PHP Version</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br><br>";

// Test 2: CloudFlare Headers
echo "<h2>2. CloudFlare Headers</h2>";
echo "X-Forwarded-Proto: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'Not set') . "<br>";
echo "X-Forwarded-Host: " . ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? 'Not set') . "<br>";
echo "X-Forwarded-For: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'Not set') . "<br>";
echo "CF-Connecting-IP: " . ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? 'Not set') . "<br>";
echo "CF-Ray: " . ($_SERVER['HTTP_CF_RAY'] ?? 'Not set') . "<br>";
echo "CF-Country: " . ($_SERVER['HTTP_CF_IPCOUNTRY'] ?? 'Not set') . "<br><br>";

// Test 3: Database Connection
echo "<h2>3. Database Connection Test</h2>";
$db_configs = [
    'default' => [
        'host' => 'localhost',
        'username' => 'root',
        'password' => 'root258369', // Add your password
        'database' => 'rubyshop.co.th_dojob'
    ],
    'rubyshop_sale' => [
        'host' => 'localhost',
        'username' => 'root',
        'password' => 'root258369', // Add your password
        'database' => 'rubyshop.co.th_dojob'
    ]
];

foreach ($db_configs as $name => $config) {
    echo "<strong>Testing $name database:</strong><br>";
    try {
        $dsn = "mysql:host={$config['host']};charset=utf8mb4";
        if (!empty($config['database'])) {
            $dsn .= ";dbname={$config['database']}";
        }
        
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "✅ Connection successful<br>";
        
        // Test query
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "MySQL Version: " . $version['version'] . "<br>";
        
        // List databases
        $stmt = $pdo->query("SHOW DATABASES");
        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Available databases: " . implode(', ', $databases) . "<br>";
        
    } catch(PDOException $e) {
        echo "❌ Connection failed: " . $e->getMessage() . "<br>";
    }
    echo "<br>";
}

// Test 4: PHP Extensions
echo "<h2>4. PHP Extensions</h2>";
$required_extensions = ['mysqli', 'pdo', 'pdo_mysql', 'curl', 'gd', 'mbstring', 'xml', 'zip', 'intl', 'bcmath'];
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? '✅' : '❌';
    echo "$status $ext<br>";
}
echo "<br>";

// Test 5: File Permissions
echo "<h2>5. File Permissions</h2>";
$paths = [
    $_SERVER['DOCUMENT_ROOT'],
    $_SERVER['DOCUMENT_ROOT'] . '/system',
    $_SERVER['DOCUMENT_ROOT'] . '/app',
    $_SERVER['DOCUMENT_ROOT'] . '/themes',
    '/tmp'
];

foreach ($paths as $path) {
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $writable = is_writable($path) ? '✅' : '❌';
        echo "$writable $path (permissions: $perms)<br>";
    } else {
        echo "❌ $path (not found)<br>";
    }
}
echo "<br>";

// Test 6: Session Test
echo "<h2>6. Session Test</h2>";
session_start();
if (!isset($_SESSION['test_count'])) {
    $_SESSION['test_count'] = 0;
}
$_SESSION['test_count']++;
echo "Session working: Page visited {$_SESSION['test_count']} times<br><br>";

// Test 7: URL Detection
echo "<h2>7. URL Detection</h2>";
$protocol = (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$current_url = $protocol . '://' . $host . $_SERVER['REQUEST_URI'];
echo "Current URL: $current_url<br>";
echo "Protocol: $protocol<br>";
echo "Host: $host<br><br>";

// Test 8: Environment Variables
echo "<h2>8. Environment Variables</h2>";
echo "Environment: " . (defined('ENVIRONMENT') ? ENVIRONMENT : 'Not defined') . "<br>";
echo "Timezone: " . date_default_timezone_get() . "<br>";
echo "Current time: " . date('Y-m-d H:i:s') . "<br><br>";

// Test 9: Memory and Limits
echo "<h2>9. System Info</h2>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "Max Execution Time: " . ini_get('max_execution_time') . "<br>";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "Post Max Size: " . ini_get('post_max_size') . "<br><br>";

// Test 10: Simple form test
echo "<h2>10. Form Test</h2>";
if ($_POST) {
    echo "POST data received:<br>";
    foreach ($_POST as $key => $value) {
        echo "$key: " . htmlspecialchars($value) . "<br>";
    }
} else {
    echo '<form method="post">
        <input type="text" name="test_input" placeholder="Test input" value="Hello World">
        <button type="submit">Test POST</button>
    </form>';
}

echo "<br><hr>";
echo "<small>Generated on: " . date('Y-m-d H:i:s') . "</small>";
?>
