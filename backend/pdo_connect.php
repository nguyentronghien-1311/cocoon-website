<?php
/** Shared PDO connection for storefront cart and order processing. */
function cocoon_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('COCOON_DB_HOST') ?: 'localhost';
    $database = getenv('COCOON_DB_NAME') ?: 'cocoon_db';
    $username = getenv('COCOON_DB_USER') ?: 'root';
    $password = getenv('COCOON_DB_PASSWORD') !== false ? (string) getenv('COCOON_DB_PASSWORD') : '';
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $database);

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
