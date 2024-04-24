<?php

/**
 * LibSQLPHP - A lightweight PHP database library utilizing LibSQL.
 *
 * This class provides a simple interface to interact with databases using LibSQL.
 *
 * @package Darkterminal\LibSQLPHPExtension
 */

namespace Darkterminal\LibSQLPHPExtension;

use Darkterminal\LibSQLPHPExtension\Responses\LibSQLPHPResult;
use Darkterminal\LibSQLPHPExtension\Responses\LibSQLPHPStmt;
use Darkterminal\LibSQLPHPExtension\Responses\Transaction;
use Darkterminal\LibSQLPHPExtension\Utils\QueryParams;
use Darkterminal\LibSQLPHPExtension\Utils\TransactionBehavior;

/**
 * LibSQLPHP class.
 */
class LibSQLPHP
{
    /**
     * FFI instance for interacting with the C library.
     *
     * @var \FFI
     */
    public \FFI $ffi;

    /**
     * Database connection handle.
     *
     * @var mixed Database connection handle.
     */
    public $db;

    /**
     * Indicates whether a connection is established or not.
     *
     * @var bool True if connected, false otherwise.
     */
    public bool $is_connected;

    /**
     * The connection mode used for establishing database connections.
     *
     * @var string
     */
    public string $connection_mode;

    /**
     * Constructor.
     *
     * @param string $path Path to the database file.
     * @param int $flags Flags to control database opening mode.
     * @param string $encryptionKey Encryption key for database (if applicable).
     *
     * @throws \Exception If invalid flags are provided.
     */
    public function __construct(
        string $path,
        int $flags = LIBSQLPHP_OPEN_READWRITE | LIBSQLPHP_OPEN_CREATE,
        string $encryptionKey = ""
    ) {
        // Check if provided flags are allowed
        if ($this->checkFlags($flags) === false) {
            throw new \Exception("LibSQLPHP Flags are not allowed to be used");
        }

        // Load the FFI interface
        $this->ffi = \FFI::cdef(
            file_get_contents(__DIR__ . '/libsql_php.def'),
            __DIR__ . '/../libs/libsql_php_client.so'
        );

        // Open the database
        $this->open($path, $flags, $encryptionKey);
    }

    /**
     * Open a connection to a local database.
     *
     * @param string $path The path to the local database file.
     * @param int $flags Flags to control database opening mode.
     * @param string $encryptionKey Encryption key for the database (optional).
     *
     * @return void
     *
     * @throws \Exception If the connection mode is not 'local'.
     */
    public function open(string $path, int $flags, string $encryptionKey = ""): void
    {
        $conn = $this->checkConnectionMode($path);
        if ($conn === false || $conn['mode'] !== 'local') {
            throw new \Exception("Error: Connection failed available mode: local://");
        }
        $this->db = $this->ffi->libsql_php_connect_local($conn['uri'], $this->checkFlags($flags), $encryptionKey);
        $this->is_connected = ($this->db) ? true : false;
    }

    /**
     * Checks whether the database connection is in autocommit mode.
     *
     * @return bool True if the database connection is in autocommit mode, false otherwise.
     */
    public function is_autocommit(): bool
    {
        return $this->ffi->libsql_php_is_autocommit($this->db);
    }

    /**
     * Check if a connection to the database is established.
     *
     * @return bool True if connected, false otherwise.
     */
    public function is_connected(): bool
    {
        return $this->is_connected;
    }

    /**
     * Close the connection to the database.
     *
     * @return void
     */
    public function close(): void
    {
        if ($this->db) {
            $this->ffi->libsql_php_close($this->db);
        }
    }

    /**
     * Execute a query on the database.
     *
     * @param string $stmt The SQL statement to execute.
     *
     * @return LibSQLPHPResult The result of the query.
     */
    public function query(string $stmt): LibSQLPHPResult
    {
        $data = $this->ffi->libsql_php_query($this->db, $stmt);
        $object = json_decode($data, true);
        return new LibSQLPHPResult($this->ffi, $this->db, $object);
    }

    /**
     * Execute a query and retrieve a single result from the database.
     *
     * @param string $stmt The SQL statement to execute.
     * @param bool $entireRow Whether to return the entire row or a single value.
     *
     * @return array|string The result of the query.
     *
     * @throws \Exception If the query is not designed for single results.
     */
    public function querySingle(string $stmt, bool $entireRow = false): array|string
    {
        $end = substr($stmt, -5);
        if (strpos($stmt, 'WHERE') !== false && $end !== 'WHERE') {
            $data = $this->ffi->libsql_php_query($this->db, $stmt);
            $object = json_decode($data, true);
            $result = new LibSQLPHPResult($this->ffi, $this->db, $object);
            $handle = current($result->fetchArray(LIBSQLPHP_ASSOC));
            $arr = array_map(fn ($value) => $value, array_values($handle));
            if ($entireRow !== true && count($arr) === 1) {
                $result = $arr[0];
            } else {
                $result = $arr;
            }
            return $entireRow ? $handle : $result;
        } else {
            throw new \Exception("Error: querySingle is only for single results");
        }
    }

    /**
     * Prepare a SQL statement for execution.
     *
     * @param string $query The SQL query to prepare.
     *
     * @return LibSQLPHPStmt A prepared statement object.
     */
    public function prepare(string $query): LibSQLPHPStmt
    {
        return new LibSQLPHPStmt($this->ffi, $this->db, $query);
    }

    /**
     * Get the number of rows affected by the last operation.
     *
     * @return int The number of affected rows.
     */
    public function changes(): int
    {
        $row_affected = $this->ffi->libsql_php_affected_rows($this->db);
        return $row_affected[0];
    }

    /**
     * Retrieves the rowid of the most recently inserted row in the database.
     *
     * @return int The rowid of the most recently inserted row.
     */
    public function last_insert_rowid(): int
    {
        return $this->ffi->libsql_php_last_insert_rowid($this->db);
    }

    /**
     * Initiates a database transaction with the specified behavior.
     *
     * @param string $behavior The behavior of the transaction (optional, defaults to TransactionBehavior::Deferred).
     *
     * @return Transaction A Transaction instance representing the initiated transaction.
     */
    public function transaction(string $behavior = TransactionBehavior::Deferred): Transaction
    {
        return new Transaction($this->ffi, $this->db, $behavior);
    }

    /**
     * Execute a SQL statement.
     *
     * @param string $query The SQL statement to execute.
     * @param array $params The SQL parameters to execute.
     *
     * @return bool True if the execution was successful, false otherwise.
     */
    public function exec(string $query, array $params = []): bool
    {
        $queryParams = new QueryParams($params);
        $exec = $this->ffi->libsql_php_exec($this->db, $query, $queryParams->getData(), $queryParams->getLength());
        $queryParams->freeParams();
        return $exec[0] === 0;
    }

    /**
     * Executes a batch of SQL queries.
     *
     * @param string $query The batch of SQL queries to execute.
     *
     * @return void
     */
    public function execute_batch(string $query): void
    {
        $this->ffi->libsql_php_execute_batch($this->db, $query);
    }

    /**
     * Synchronize changes with the database server.
     *
     * @return int The result of the synchronization operation.
     *
     * @throws \Exception If attempting to sync with a local file connection.
     */
    public function sync(): int
    {
        if ($this->connection_mode === 'local') {
            throw new \Exception("Error: Sync not work for local file connection.");
        }

        $exec = $this->ffi->libsql_php_sync($this->db);
        return $exec[0] === 0;
    }

    /**
     * Get the version of the LibSQL library.
     *
     * @return string The version string.
     */
    public function version(): string
    {
        return $this->ffi->libsql_version();
    }

    /**
     * Escape special characters in a string for use in SQL statements.
     *
     * @param mixed $value The value to escape.
     *
     * @return string The escaped string.
     */
    public static function escapeString($value)
    {
        // DISCUSSION: Open PR if you have best approach
        $escaped_value = str_replace(
            ["\\", "\x00", "\n", "\r", "\x1a", "'", '"'],
            ["\\\\", "\\0", "\\n", "\\r", "\\Z", "\\'", '\\"'],
            $value
        );

        return $escaped_value;
    }

    /**
     * Check the flags provided for database opening mode.
     *
     * @param int $flags The flags to check.
     *
     * @return string|false The string representation of the flags, or false if invalid.
     */
    private function checkFlags(int $flags): string|false
    {
        $flag_string = 'LIBSQLPHP_OPEN_READWRITE_LIBSQLPHP_OPEN_CREATE';
        switch ($flags) {
            case LIBSQLPHP_OPEN_READONLY:
                $flag_string = 'LIBSQLPHP_OPEN_READONLY';
                break;
            case LIBSQLPHP_OPEN_READWRITE:
                $flag_string = 'LIBSQLPHP_OPEN_READWRITE';
                break;
            case LIBSQLPHP_OPEN_CREATE:
                $flag_string = 'LIBSQLPHP_OPEN_CREATE';
                break;
            case (LIBSQLPHP_OPEN_READWRITE | LIBSQLPHP_OPEN_CREATE):
                $flag_string = 'LIBSQLPHP_OPEN_READWRITE_LIBSQLPHP_OPEN_CREATE';
                break;
            case (LIBSQLPHP_OPEN_READONLY | LIBSQLPHP_OPEN_CREATE):
                $flag_string = 'LIBSQLPHP_OPEN_READONLY_LIBSQLPHP_OPEN_CREATE';
                break;
            default:
                $flag_string = false;
                break;
        }
        return $flag_string;
    }

    /**
     * Check the connection mode based on the provided path.
     *
     * @param string $path The database connection path.
     *
     * @return array|false The connection mode details, or false if not applicable.
     */
    private function checkConnectionMode($path): array|false
    {
        if (strpos($path, "file:") !== false) {
            $this->connection_mode = 'local';
            $path = [
                'mode' => $this->connection_mode,
                'uri' => str_replace("file:", "", $path)
            ];
        } else {
            $path = false;
        }

        return $path;
    }
}
