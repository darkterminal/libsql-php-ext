<?php

class FFI
{
    /**
     * Rolls back a transaction.
     *
     * @param $trx_ptr A raw pointer to the transaction object.
     * @return int Returns 1 if the transaction is successfully rolled back, 0 otherwise.
     */
    public function libsql_php_transaction_rollback($trx_ptr)
    {
    }

    /**
     * Commits a transaction.
     *
     * @param $trx_ptr A raw pointer to the transaction object.
     * @return int Returns 1 if the transaction is successfully committed, 0 otherwise.
     */
    public function libsql_php_transaction_commit($trx_ptr)
    {
    }

    /**
     * Executes a LibSQL query within a transaction.
     *
     * @param $trx_ptr A raw pointer to the transaction object.
     * @param string $query A string representing the LibSQL query to execute.
     * @param array $query_params An array of strings representing query parameters.
     * @return Transaction|null Returns the transaction object if successful, null otherwise.
     */
    public function libsql_php_transaction_exec($trx_ptr, string $query, array $query_params)
    {
    }

    /**
     * Initiates a LibSQL transaction with the specified behavior.
     *
     * @param $client_ptr A raw pointer to the LibSQL connection client.
     * @param string|null $behavior A string representing the transaction behavior ("DEFERRED", "WRITE", "READ"), or null.
     * @return Transaction|null Returns the transaction object if successful, null otherwise.
     */
    public function libsql_php_transaction($client_ptr, ?string $behavior)
    {
    }

    /**
     * Retrieves the last inserted row ID from a SQL connection.
     *
     * @param $client_ptr A raw pointer to the LibSQL connection client.
     * @return int Returns the last inserted row ID as an integer, or 0 if an error occurs.
     */
    public function libsql_php_last_insert_rowid($client_ptr)
    {
    }

    /**
     * Checks if autocommit is enabled for a LibSQL connection.
     *
     * @param $client_ptr A raw pointer to the LibSQL connection client.
     * @return int Returns 1 if autocommit is enabled, 0 otherwise.
     */
    public function libsql_php_is_autocommit($client_ptr)
    {
    }

    /**
     * Resets a LibSQL connection.
     *
     * @param $client_ptr A raw pointer to the LibSQL connection client.
     * @return int|null Returns 1 if the reset operation is successful, null otherwise.
     */
    public function libsql_php_reset($client_ptr)
    {
    }

    /**
     * Executes a batch SQL query, interfacing with a provided LibSQL connection.
     *
     * @param $client_ptr A raw pointer to the LibSQL connection client.
     * @param string $query A string representing the batch SQL query to execute.
     * @return int|null Returns 1 if the batch execution is successful, null otherwise.
     */
    public function libsql_php_execute_batch($client_ptr, string $query)
    {
    }

    /**
     * Executes a SQL query, interfacing with a provided LibSQL connection.
     *
     * @param $client_ptr A raw pointer to the LibSQL connection client.
     * @param string $query A string representing the SQL query to execute.
     * @param array $query_params An array of strings representing query parameters.
     * @return int|null Returns the number of rows affected by the query execution, null otherwise.
     */
    public function libsql_php_exec($client_ptr, string $query, array $query_params)
    {
    }

    /**
     * Executes an LibSQL query using the provided client pointer, query string, and query parameters,
     * returning the query result in JSON format.
     *
     * @param $client_ptr A raw pointer to the SQL connection client.
     * @param string $query A string representing the SQL query.
     * @param array $query_params An array of strings representing query parameters.
     * @return string|null A string representing the JSON-formatted query result, or null if an error occurs.
     */
    public function libsql_php_query($client_ptr, string $query, array $query_params)
    {
    }

    /**
     * Establishes a connection to a local LibSQL database with optional encryption.
     *
     * @param string $path A string representing the path to the database.
     * @param string $flags A string representing the flags for database opening.
     * @param string $encryption_key A string representing the encryption key.
     * @return Connection|null Returns a connection object if the connection is successfully established, null otherwise.
     */
    public function libsql_php_connect_local(string $path, string $flags, string $encryption_key)
    {
    }

    /**
     * Synchronizes the remote replica database associated with the provided `DbConnPair`.
     *
     * @param DbConnPair $pair A pair of database and connection pointers.
     * @return int Returns 0 if synchronization is successful, otherwise returns an error code.
     */
    public function libsql_php_sync($pair)
    {
    }

    /**
     * Connects to a new remote replica.
     *
     * @param string $path A string representing the path.
     * @param string $url A string representing the URL.
     * @param string $token A string representing the token.
     * @param int $sync_duration The synchronization duration in seconds.
     * @param int $read_your_writes An integer representing whether "read your writes" mode is enabled (1) or not (0).
     * @return DbConnPair|null Returns a pair of database connection objects if successful, null otherwise.
     */
    public function libsql_php_connect_new_remote_replica(string $path, string $url, string $token, int $sync_duration, int $read_your_writes)
    {
    }

    /**
     * Calls the `libsql_php_error` public function if the provided client pointer is null,
     * otherwise closes the LibSQL connection and deallocates memory.
     *
     * @param $client_ptr A raw pointer to the LibSQL connection client.
     */
    public function libsql_php_close($client_ptr)
    {
    }

    /**
     * Retrieves the number of affected rows after executing a LibSQL query.
     *
     * @param $client_ptr A raw pointer to the SQL connection client.
     * @return int|null Returns the number of affected rows, null if an error occurs.
     */
    public function libsql_php_affected_rows($client_ptr)
    {
    }

    /**
     * Retrieves the version of the LibSQL library in use.
     *
     * @return string Returns a string containing the version information of the LibSQL library.
     */
    public function libsql_version()
    {
    }
}
