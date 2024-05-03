<?php

/**
 * LibSQLPHP - A lightweight PHP database library utilizing LibSQL.
 *
 * This class provides a simple interface to interact with databases using LibSQL.
 *
 * @package Darkterminal\LibSQLPHPExtension
 */

namespace Darkterminal\LibSQLPHPExtension;

use Darkterminal\LibSQLPHPExtension\Responses\LibSQLPHPClientResult;
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

    protected $dbPair;

    /**
     * Constructor.
     * 
     * The constructor of the LibSQLPHP class ensures that the provided
     * parameters are valid, checks for the existence of necessary files, initializes
     * the FFI interface for interacting with the LibSQL library, and opens a connection to the database. 
     * It encapsulates the initialization logic required for setting up a new instance of the LibSQLPHP class.
     * 
     * ## Example Local File Connection
     * 
     * ```
     * // Minimal option
     * $db = new LibSQLPHP("file:database.db");
     * 
     * // Full option
     * $db = new LibSQLPHP("file:database.db", LIBSQLPHP_OPEN_READWRITE | LIBSQLPHP_OPEN_CREATE, "encryptionKey");
     * ```
     * 
     * ## Example In-Memory Connection
     * 
     * ```
     * $db = new LibSQLPHP(":memory:");
     * ```
     * 
     * ## Example Remote Replica Connection
     * 
     * ```
     * // Minimal option
     * $db = new LibSQLPHP(path: "file:database.db", url: $url, token: $token);
     * 
     * // Full option
     * $db = new LibSQLPHP(path: "file:database.db", url: $url, token: $token, sync_interval: 10, read_your_writes: true);
     * ```
     *
     * @param string $path **(Local/Remote Replica)** Path to the database file.
     * @param int $flags **(Local)** Flags to control database opening mode. Default: LIBSQLPHP_OPEN_READWRITE | LIBSQLPHP_OPEN_CREATE
     * @param string $encryptionKey **(Local/Remote Replica)** Encryption key for database (if applicable).
     * @param string $url **(Remote Replica)** Base URL for HTTP connection (if applicable).
     * @param string $token **(Remote Replica)** Authentication token for HTTP connection (if applicable).
     * @param int $sync_interval **(Remote Replica)** Database sync duration in seconds (if applicable).
     * @param bool $read_your_writes **(Remote Replica)** Enable read-your-writes consistency (if applicable).
     *
     * @throws \Exception If invalid flags are provided or if LibSQLPHP definition and extension files do not exist.
     */
    public function __construct(
        string $path = "",
        int $flags = LIBSQLPHP_OPEN_READWRITE | LIBSQLPHP_OPEN_CREATE,
        string $encryptionKey = "",
        string $url = "",
        string $token = "",
        int $sync_interval = 5,
        bool $read_your_writes = true
    ) {
        // Check if provided flags are allowed
        if ($this->checkFlags($flags) === false) {
            throw new \Exception("LibSQLPHP Flags are not allowed to be used");
        }

        if (!file_exists(__DIR__ . '/libsql_php.def') && !file_exists(__DIR__ . '/../libs/libsql_php_client.so')) {
            throw new \Exception("LibSQLPHP definition and extension is not exits!");
        }

        // Load the FFI interface
        $this->ffi = \FFI::cdef(
            file_get_contents(__DIR__ . '/libsql_php.def'),
            __DIR__ . '/../libs/libsql_php_client.so'
        );

        // Open the database
        $this->open($path, $flags, $encryptionKey, $url, $token, $sync_interval, $read_your_writes);
    }

    /**
     * Open a connection to a local database.
     * 
     * ## Example Local File Connection
     * 
     * ```
     * // Minimal option
     * $db = new LibSQLPHP("file:database.db");
     * 
     * // Full option
     * $db = new LibSQLPHP("file:database.db", LIBSQLPHP_OPEN_READWRITE | LIBSQLPHP_OPEN_CREATE, "encryptionKey");
     * ```
     * 
     * ## Example In-Memory Connection
     * 
     * ```
     * $db = new LibSQLPHP(":memory:");
     * ```
     * 
     * ## Example Remote Replica Connection
     * 
     * ```
     * // Minimal option
     * $db = new LibSQLPHP(path: "file:database.db", url: $url, token: $token);
     * 
     * // Full option
     * $db = new LibSQLPHP(path: "file:database.db", url: $url, token: $token, sync_interval: 10, read_your_writes: true);
     * ```
     *
     * @param string $path **(Local/Remote Replica)** Path to the database file.
     * @param int $flags **(Local)** Flags to control database opening mode. Default: LIBSQLPHP_OPEN_READWRITE | LIBSQLPHP_OPEN_CREATE
     * @param string $encryptionKey **(Local/Remote Replica)** Encryption key for database (if applicable).
     * @param string $url **(Remote Replica)** Base URL for HTTP connection (if applicable).
     * @param string $token **(Remote Replica)** Authentication token for HTTP connection (if applicable).
     * @param int $sync_interval **(Remote Replica)** Database sync duration in seconds (if applicable).
     * @param bool $read_your_writes **(Remote Replica)** Enable read-your-writes consistency (if applicable).
     *
     * @throws \Exception If invalid flags are provided or if LibSQLPHP definition and extension files do not exist.
     */
    public function open(
        string $path,
        int $flags,
        string $encryptionKey = "",
        string $url = "",
        string $token = "",
        int $sync_interval = 0,
        bool $read_your_writes = true
    ): void {
        // Check if provided flags are allowed
        if ($this->checkFlags($flags) === false) {
            throw new \Exception("LibSQLPHP Flags are not allowed to be used");
        }

        $conn = $this->checkConnectionMode($path, $url, $token);
        if ($conn === false || !in_array($conn['mode'], ['local', 'memory', 'remote_replica'])) {
            throw new \Exception("Error: Connection failed available mode: Local or in-momory");
        }

        if ($conn['mode'] !== "remote_replica") {
            $this->db = $this->ffi->libsql_php_connect_local($conn['uri'], $this->checkFlags($flags), $encryptionKey);
            $this->is_connected = ($this->db) ? true : false;
        } else {
            $this->dbPair = $this->ffi->libsql_php_connect_new_remote_replica(
                $path,
                $url,
                $token,
                (int) $sync_interval,
                (int) $read_your_writes
            );
            $this->db = $this->dbPair->conn;
            $this->is_connected = ($this->dbPair->conn) ? true : false;
        }
    }

    /**
     * Checks whether the database connection is in autocommit mode.
     * 
     * **Example**
     * 
     * ```
     * $db = new LibSQLPHP("file:database.db");
     * $db->is_autocommit();
     * ```
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
     * **Example**
     * 
     * ```
     * $db = new LibSQLPHP("file:database.db");
     * $db->is_connected();
     * ```
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
     * **Example**
     * 
     * ```
     * $db = new LibSQLPHP("file:database.db");
     * if ($db->is_connected()) {
     *     echo $db->version() . PHP_EOL;
     * }
     * $db->close(); // Always close the database connection
     * ```
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
     * **Example**
     * 
     * ```
     * $result = $db->query("SELECT * FROM users LIMIT 5");
     * ```
     *
     * @param string $stmt The SQL statement to execute.
     * @param array $params The SQL statement to parameters.
     *
     * @return LibSQLPHPResult The result of the query.
     */
    public function query(string $stmt, array $params = []): LibSQLPHPResult
    {
        if (is_array_assoc($params)) {
            $queryParams = new QueryParams([]);
            $intoParams = intoParams($stmt, $params);
            var_dump($intoParams);
            $data = $this->ffi->libsql_php_query($this->db, $intoParams, $queryParams->getData(), $queryParams->getLength());
            $queryParams->freeParams();
        } else {
            $queryParams = new QueryParams($params);
            $data = $this->ffi->libsql_php_query($this->db, $stmt, $queryParams->getData(), $queryParams->getLength());
            $queryParams->freeParams();
        }
        $object = json_decode($data, true);
        return new LibSQLPHPResult($this->ffi, $this->db, $object);
    }

    /**
     * Execute a query and retrieve a single result from the database.
     * 
     * **Example**
     * 
     * ```
     * $result = $db->querySingle("SELECT name FROM users WHERE id = 1");
     * $result2 = $db->querySingle("SELECT name FROM users WHERE id = 2", true);
     * ```
     *
     * @param string $stmt The SQL statement to execute.
     * @param string $params The SQL statement parameters to execute.
     * @param bool $entireRow Whether to return the entire row or a single value.
     *
     * @return array|string The result of the query.
     *
     * @throws \Exception If the query is not designed for single results.
     */
    public function querySingle(string $stmt, array $params = [], bool $entireRow = false): array|string
    {
        $end = substr($stmt, -5);
        if (strpos($stmt, 'WHERE') !== false && $end !== 'WHERE') {
            $queryParams = new QueryParams($params);
            $data = $this->ffi->libsql_php_query($this->db, $stmt, $queryParams->getData(), $queryParams->getLength());
            $queryParams->freeParams();

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
     * **Example**
     * 
     * ```
     * // Bind Param
     * $stmt = $db->prepare("INSERT INTO persons (name, age) VALUES (:name, @age)");
     * $stmt->bindParam(':name', $baz, LIBSQLPHP_TEXT);
     * $stmt->bindParam('@age', $foo, LIBSQLPHP_INTEGER);
     * $baz = "Sarah";
     * $foo = 22;
     * $stmt->execute();
     * 
     * // Bind Value
     * $stmt = $db->prepare("INSERT INTO foo VALUES (?, ?)");
     * $stmt->bindValue(1, "baz", LIBSQLPHP_TEXT);
     * $stmt->bindValue(2, 5, LIBSQLPHP_INTEGER);
     * $stmt->execute();
     * ```
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
     * **Example**
     * 
     * ```
     * $db->exec("DELETE FROM users WHERE id = 3");
     * 
     * $changes = $db->changes();
     * echo "The DELETE statement removed $changes rows";
     * ```
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
     * **Example**
     * 
     * ```
     * $operations_successful = false;
     * $tx = $db->transaction(TransactionBehavior::Deferred);
     * $tx->exec("INSERT INTO users (name) VALUES (?)", ["Emanuel"]);
     * $tx->exec("INSERT INTO users (name) VALUES (?)", ["Darren"]);
     * 
     * if ($operations_successful) {
     *     $tx->commit();
     *     echo "Commit the changes" . PHP_EOL;
     * } else {
     *     $tx->rollback();
     *     echo "Rollback the changes" . PHP_EOL;
     * }
     * ```
     * NOTE: After `commit` or `rollback` the `$tx` will be free from memory
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
     * **Example**
     * 
     * ```
     * $db->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");
     * $db->exec("INSERT INTO users (name) VALUES ('Handoko')");
     * $db->exec("INSERT INTO users (name) VALUES ('Karlina')");
     * ```
     *
     * @param string $query The SQL statement to execute.
     * @param array $params The SQL parameters to execute. Optional
     *
     * @return bool True if the execution was successful, false otherwise.
     */
    public function exec(string $query, array $params = []): bool
    {
        $queryParams = new QueryParams($params);
        $exec = $this->ffi->libsql_php_exec($this->db, $query, $queryParams->getData(), $queryParams->getLength());
        $queryParams->freeParams();
        return $exec[0] > 0;
    }

    /**
     * Executes a client query and returns the result. - LibSQL Client PHP Usage Only
     *
     * This method executes a client query using the provided SQL statement and parameters.
     * It then creates a new LibSQLPHPClientResult instance based on the query result.
     *
     * @param string $query The SQL query statement to execute.
     * @param array $params The parameters to bind to the SQL query.
     * @return LibSQLPHPClientResult The result of the client query.
     */
    public function client_exec(string $query, array $params): LibSQLPHPClientResult
    {
        $data = $this->query($query, $params);
        return new LibSQLPHPClientResult(
            $data->columName(),
            $data->columnType(),
            $data->fetchArray(LIBSQLPHP_ASSOC),
            $this->changes(),
            $this->last_insert_rowid()
        );
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

        $exec = $this->ffi->libsql_php_sync($this->dbPair);
        return $exec === 0;
    }

    /**
     * Get the version of the LibSQL Binary.
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
    private function checkConnectionMode(string $path, string $url = "", string $token = ""): array|false
    {
        if (strpos($path, "file:") !== false && !empty($url) && !empty($token)) {
            $this->connection_mode = 'remote_replica';
            $path = [
                'mode' => $this->connection_mode,
                'uri' => $path,
                'url' => $url,
                'token' => $token
            ];
        } else if (strpos($path, "file:") !== false) {
            $this->connection_mode = 'local';
            $path = [
                'mode' => $this->connection_mode,
                'uri' => str_replace("file:", "", $path)
            ];
        } else if ($path === ":memory:") {
            $this->connection_mode = 'memory';
            $path = [
                'mode' => $this->connection_mode,
                'uri' => $path
            ];
        } else {
            $path = false;
        }

        return $path;
    }
}
