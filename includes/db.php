<?php

function db() {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $cfg = $GLOBALS['__cfg'] ?? array();
    $host = $cfg['db_host'] ?? 'localhost';
    $port = isset($cfg['db_port']) && $cfg['db_port'] !== '' ? $cfg['db_port'] : '3306';
    $name = $cfg['db_name'] ?? '';
    $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=utf8mb4';
    $pdo = new PDO($dsn, $cfg['db_user'] ?? '', $cfg['db_pass'] ?? '', array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ));
    return $pdo;
}
