<?php

namespace Darkterminal\LibSQLPHPExtension\Responses;

use FFI;

/**
 * Represents a database transaction.
 */
class Transaction
{
    /**
     * The transaction handle.
     */
    protected $transaction;

    /**
     * Transaction constructor.
     *
     * @param FFI $ffi The FFI instance.
     * @param mixed $db The database handle.
     * @param string $behavior The behavior of the transaction.
     */
    public function __construct(
        protected FFI $ffi,
        protected $db,
        string $behavior
    )
    {
        $this->ffi = $ffi;
        $this->db = $db;

        $this->begin($behavior);
    }

    /**
     * Begins a transaction with the specified behavior.
     *
     * @param string $behavior The behavior of the transaction.
     *
     * @return Transaction The Transaction instance.
     */
    private function begin(string $behavior): Transaction {
        $this->transaction = $this->ffi->libsql_php_transaction($this->db, $behavior);
        return $this;
    }

    /**
     * Executes a SQL query within the transaction.
     *
     * @param string $query The SQL query to execute.
     * @param array $params Optional parameters for the query.
     *
     * @return Transaction The Transaction instance.
     */
    public function exec(string $query, array $params = []): Transaction {
        $query_params_len = count($params);
        $ffi_query_params = $this->ffi->new("const char*[{$query_params_len}]", false);
        foreach ($params as $i => $param) {
            $ffi_query_params[$i] = FFI::new("char[" . (strlen($param) + 1) . "]", false);
            FFI::memcpy($ffi_query_params[$i], $param, strlen($param));
        }
        $this->ffi->libsql_php_transaction_exec($this->transaction, $query, FFI::addr($ffi_query_params[0]), $query_params_len);
        return $this;
    }

    /**
     * Checks whether the transaction is in autocommit mode.
     *
     * @return bool True if the transaction is in autocommit mode, false otherwise.
     */
    public function is_autocommit(): bool {
        return $this->ffi->libsql_php_is_autocommit($this->db);
    }

    /**
     * Retrieves the number of rows affected by the transaction.
     *
     * @return int The number of rows affected by the transaction.
     */
    public function changes(): int {
        return $this->ffi->libsql_php_affected_rows($this->db);
    }

    /**
     * Retrieves the rowid of the most recently inserted row in the transaction.
     *
     * @return int The rowid of the most recently inserted row.
     */
    public function last_insert_rowid(): int {
        return $this->ffi->libsql_php_last_insert_rowid($this->db);
    }

    /**
     * Resets the transaction.
     *
     * @return void
     */
    public function reset(): void {
        $this->ffi->libsql_php_reset($this->db);
    }

    /**
     * Commits the transaction.
     *
     * @return bool True if the transaction is committed successfully, false otherwise.
     */
    public function commit(): bool {
        $result = $this->ffi->libsql_php_transaction_commit($this->transaction);
        $this->ffi->free($this->transaction);
        return $result;
    }

    /**
     * Rolls back the transaction.
     *
     * @return bool True if the transaction is rolled back successfully, false otherwise.
     */
    public function rollback(): bool {
        $result = $this->ffi->libsql_php_transaction_rollback($this->transaction);
        $this->ffi->free($this->transaction);
        return $result;
    }
}
