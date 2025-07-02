<?php
// tests/bootstrap.php

$rootDir = dirname(__DIR__);

// 1. Load Composer autoloader if present
// $vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';
// if (file_exists($vendorAutoload)) {
//     require_once $vendorAutoload;
// }

$_ENV['TEST_DB'] = dirname(__DIR__) . '/tests/test_db.sqlite';

// 3. Bring in the application bootstrap (creates container, etc.)
require_once $rootDir . '/src/config.php';

// 4. Recreate database schema in SQLite (in-memory or file) for each test run
$pdo = Database::getConnection();
$sqlDumpPath = $rootDir . '/org_plus.sqlite.sql';
if (!file_exists($sqlDumpPath)) {
    fwrite(STDERR, "\n[bootstrap] Could not find SQL dump file at {$sqlDumpPath}. Tests may fail.\n");
} else {
    $sql = file_get_contents($sqlDumpPath);
    // Split queries by semicolon on new line to execute sequentially (simple approach)
    $queries = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
    foreach ($queries as $query) {
        if ($query !== '') {
            try {
                // echo $query; // debug
                $pdo->exec($query);
            } catch (PDOException $e) {
                // Ignore errors for CREATE TABLE IF EXISTS etc.
                // echo $e;
            }
        }
    }
}
