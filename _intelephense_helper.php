<?php

class FFI
{
    /**
     * Open file connection to LibSQL Client
     *
     * @param string $path
     * @param int $flags
     * @param string $encryptionKey
     * @return Connection
     */
    public function libsql_php_connect_local(string $path, int $flags, $encryptionKey = "") {}
    
    public function libsql_php_close($db) {}

    /**
     * Undocumented function
     *
     * @param Connection $db
     * @return bool
     */
    public function libsql_php_is_autocommit($db) {}

    /**
     * Undocumented function
     *
     * @param Connection $db
     * @return int
     */
    public function libsql_php_last_insert_rowid($db) {}

    /**
     * Sync database
     *
     * @return int
     */
    public function libsql_php_sync($db) {}

    /**
     * Query the database with positional params
     *
     * @param Connection $db
     * @param string $stmt
     * @param array $args
     * @param int $args_len
     * @return string
     */
    public function libsql_php_query($db, $stmt, $args, $args_len) {}

    /**
     * Query the database
     *
     * @param Connection $db
     * @param string $query
     * @param array $query_params
     * @param int $query_params_len
     * @return int
     */
    public function libsql_php_exec($db, $query, $query_params, $query_params_len) {}

    /**
     * Undocumented function
     *
     * @param Connection $db
     * @param string $query
     * @return void
     */
    public function libsql_php_execute_batch($db, $query) {}

    /**
     * Query the database
     *
     * @param Connection $db
     * @return int
     */
    public function libsql_php_affected_rows($db) {}

    public function libsql_php_reset($db) {}

    /**
     * Undocumented function
     *
     * @param Connection $db
     * @param int $milliseconds
     * @return bool|string
     */
    public function libsql_php_busy_timeout($db, $milliseconds) {}

    /**
     * Undocumented function
     *
     * @return string
     */
    public function libsql_version() {}

    /**
     * Undocumented function
     *
     * @param Connection $db
     * @param TransactionBehavior $behavior
     * @return Transaction
     */
    public function libsql_php_transaction($db, $behavior) {}

    /**
     * Undocumented function
     *
     * @param Transaction $trx
     * @param string $query
     * @param array $query_params
     * @param int $query_params_len
     * @return Transaction
     */
    public function libsql_php_transaction_exec($trx, $query, $query_params, $query_params_len) {}

    /**
     * Undocumented function
     *
     * @param Transaction $trx
     * @return bool
     */
    public function libsql_php_transaction_commit($trx) {}
    
    /**
     * Undocumented function
     *
     * @param Transaction $trx
     * @return bool
     */
    public function libsql_php_transaction_rollback($trx) {}
}
