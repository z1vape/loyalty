<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "<h2>1. PHP Info</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "SQLite3: " . (extension_loaded('sqlite3') ? '<b style="color:green">Active</b>' : '<b style="color:red">Missing</b>') . "<br>";
echo "PDO SQLite: " . (extension_loaded('pdo_sqlite') ? '<b style="color:green">Active</b>' : '<b style="color:red">Missing</b>') . "<br>";
echo "Fileinfo: " . (extension_loaded('fileinfo') ? '<b style="color:green">Active</b>' : '<b style="color:red">Missing</b>') . "<br>";

echo "<h2>2. Folder Writable Checks</h2>";
$paths = [
    'storage' => __DIR__ . '/storage',
    'storage/logs' => __DIR__ . '/storage/logs',
    'storage/framework' => __DIR__ . '/storage/framework',
    'storage/framework/views' => __DIR__ . '/storage/framework/views',
    'bootstrap/cache' => __DIR__ . '/bootstrap/cache',
    'database' => __DIR__ . '/database',
];

foreach ($paths as $name => $path) {
    if (!file_exists($path)) {
        echo "$name: <b style='color:red'>NOT FOUND ($path)</b><br>";
    } elseif (is_writable($path)) {
        echo "$name: <b style='color:green'>Writable</b><br>";
    } else {
        echo "$name: <b style='color:red'>NOT WRITABLE (chmod 775/777 needed)</b><br>";
    }
}

echo "<h2>3. Autoload & Bootstrap Test</h2>";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    echo "vendor/autoload.php: <b style='color:green'>Loaded successfully</b><br>";
} catch (\Throwable $e) {
    echo "vendor/autoload.php Error: <b style='color:red'>" . htmlspecialchars($e->getMessage()) . "</b><br>";
}

try {
    $app = require_once __DIR__ . '/bootstrap/app.php';
    echo "bootstrap/app.php: <b style='color:green'>Bootstrap OK</b><br>";
} catch (\Throwable $e) {
    echo "bootstrap/app.php Error: <b style='color:red'>" . htmlspecialchars($e->getMessage()) . "</b><br>";
}
