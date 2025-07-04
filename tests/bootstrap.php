<?php

$rootDir = dirname(__DIR__);

$_ENV['TEST_DB'] = dirname(__DIR__) . '/tests/test_db.sqlite';

require_once $rootDir . '/src/config.php';

$pdo = Database::getConnection();
$sqlDumpPath = $rootDir . '/org_plus.sqlite.sql';
if (!file_exists($sqlDumpPath)) {
    fwrite(STDERR, "\n[bootstrap] Could not find SQL dump file at {$sqlDumpPath}. Tests may fail.\n");
} else {
    $sql = file_get_contents($sqlDumpPath);
    $queries = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
    foreach ($queries as $query) {
        if ($query !== '') {
            try {
                $pdo->exec($query);
            } catch (PDOException $e) {
            }
        }
    }
}
