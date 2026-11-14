<?php

// Creates ONE database connection (PDO) and reuses it for the whole request.
// All queries in the Models use prepared statements to prevent SQL injection.
class Database
{
    private static $connection = null;

    public static function connection()
    {
        // Only connect the first time; after that, return the same connection.
        if (self::$connection === null) {
            $config = require __DIR__ . '/../../config/database.php';

            $dsn = 'mysql:host=' . $config['host']
                 . ';port=' . $config['port']
                 . ';dbname=' . $config['database']
                 . ';charset=' . $config['charset'];

            try {
                self::$connection = new PDO($dsn, $config['username'], $config['password']);

                // Throw an error if a query fails, instead of failing silently.
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                // Return rows as ['column' => value] arrays.
                self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                // Use real prepared statements in MySQL (better SQL-injection protection).
                self::$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            } catch (PDOException $e) {
                // Write the real error to the server log, but never show
                // database details to the visitor.
                error_log('Database connection failed: ' . $e->getMessage());
                http_response_code(500);
                die('The service is temporarily unavailable. Please try again later.');
            }
        }

        return self::$connection;
    }
}
