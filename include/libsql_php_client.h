#define FFI_LIB "libsql_php_client.so"

/**
 * Represents a pair of pointers to a database and a connection.
 *
 * This struct is marked with #[repr(C)] to ensure its memory layout is compatible
 * with C code.
 */
typedef struct DbConnPair {
  /**
   * A raw pointer to the database object.
   */
  Database *db;
  /**
   * A raw pointer to the connection object.
   */
  Connection *conn;
} DbConnPair;

/**
 * Rolls back a transaction in a PHP extension.
 *
 * # Safety
 *
 * This function is marked as unsafe because it dereferences raw pointers.
 *
 * # Arguments
 *
 * * `trx_ptr` - A raw pointer to the transaction object.
 *
 * # Returns
 *
 * Returns `1` if the transaction is successfully rolled back. If an error occurs or a null pointer is provided, returns `0`.
 */
int64_t libsql_php_transaction_rollback(void *trx_ptr);

/**
 * Commits a transaction in a PHP extension.
 *
 * # Safety
 *
 * This function is marked as unsafe because it dereferences raw pointers.
 *
 * # Arguments
 *
 * * `trx_ptr` - A raw pointer to the transaction object.
 *
 * # Returns
 *
 * Returns `1` if the transaction is successfully committed. If an error occurs or a null pointer is provided, returns `0`.
 */
int64_t libsql_php_transaction_commit(void *trx_ptr);

/**
 * Executes a LibSQL query within a transaction in a PHP extension.
 *
 * # Safety
 *
 * This function is marked as unsafe because it dereferences raw pointers.
 *
 * # Arguments
 *
 * * `trx_ptr` - A raw pointer to the transaction object.
 * * `query` - A raw pointer to a C-style string representing the LibSQL query to execute.
 * * `query_params` - A pointer to an array of raw pointers to C-style strings representing query parameters.
 * * `query_params_len` - The length of the `query_params` array.
 *
 * # Returns
 *
 * Returns a pointer to the transaction object if successful. If an error occurs or a null pointer is provided, returns a null pointer.
 */
Transaction *libsql_php_transaction_exec(void *trx_ptr,
                                         const char *query,
                                         const char *const *query_params,
                                         uintptr_t query_params_len);

/**
 * Initiates a LibSQL transaction in a PHP extension with the specified behavior.
 *
 * # Safety
 *
 * This function is marked as unsafe because it dereferences raw pointers.
 *
 * # Arguments
 *
 * * `client_ptr` - A raw pointer to the LibSQL connection client.
 * * `behavior` - A raw pointer to a C-style string representing the transaction behavior. Can be "DEFERRED", "WRITE", "READ", or null.
 *
 * # Returns
 *
 * Returns a pointer to a memory location containing the transaction object if successful. If an error occurs or the client pointer is null, returns a null pointer.
 */
Transaction *libsql_php_transaction(void *client_ptr,
                                    const char *behavior);

/**
 * Retrieves the last inserted row ID from a SQL connection in a PHP extension.
 *
 * # Safety
 *
 * This function is marked as unsafe because it dereferences raw pointers.
 *
 * # Arguments
 *
 * * `client_ptr` - A raw pointer to the LibSQL connection client.
 *
 * # Returns
 *
 * Returns the last inserted row ID as an integer. If an error occurs or the client pointer is null, returns `0`.
 */
int64_t libsql_php_last_insert_rowid(void *client_ptr);

/**
 * Checks if autocommit is enabled for a LibSQL connection in a PHP extension.
 *
 * # Safety
 *
 * This function is marked as unsafe because it dereferences raw pointers.
 *
 * # Arguments
 *
 * * `client_ptr` - A raw pointer to the LibSQL connection client.
 *
 * # Returns
 *
 * Returns an integer representing whether autocommit is enabled (`1`) or not (`0`). If an error occurs or the client pointer is null, returns `0`.
 */
int64_t libsql_php_is_autocommit(void *client_ptr);

/**
 * Resets a LibSQL connection in a PHP extension.
 *
 * # Safety
 *
 * This function is marked as unsafe because it dereferences raw pointers.
 *
 * # Arguments
 *
 * * `client_ptr` - A raw pointer to the LibSQL connection client.
 *
 * # Returns
 *
 * Returns a pointer to a memory location indicating the success of the reset operation. If an error occurs or the client pointer is null, returns a null pointer.
 */
const int *libsql_php_reset(void *client_ptr);

/**
 * Executes a batch SQL query in a PHP extension, interfacing with a provided LibSQL connection.
 *
 * # Safety
 *
 * This function is marked as unsafe because it dereferences raw pointers and performs FFI operations.
 *
 * # Arguments
 *
 * * `client_ptr` - A raw pointer to the LibSQL connection client.
 * * `query` - A raw pointer to a C-style string representing the batch SQL query to execute.
 *
 * # Returns
 *
 * Returns a pointer to a memory location indicating the success of the batch execution. If an error occurs, returns a null pointer.
 */
const int *libsql_php_execute_batch(void *client_ptr,
                                    const char *query);

/**
 * Executes a SQL query in a PHP extension, interfacing with a provided LibSQL connection.
 *
 * # Safety
 *
 * This function is marked as unsafe because it dereferences raw pointers and performs FFI operations.
 *
 * # Arguments
 *
 * * `client_ptr` - A raw pointer to the LibSQL connection client.
 * * `query` - A raw pointer to a C-style string representing the SQL query to execute.
 * * `query_params` - A pointer to an array of raw pointers to C-style strings representing query parameters.
 * * `query_params_len` - The length of the `query_params` array.
 *
 * # Returns
 *
 * Returns a pointer to a memory location containing the number of rows affected by the query execution. If an error occurs, returns a null pointer.
 */
const int64_t *libsql_php_exec(void *client_ptr,
                               const char *query,
                               const char *const *query_params,
                               uintptr_t query_params_len);

/**
 * Executes an LibSQL query using the provided client pointer, query string, and query parameters,
 * returning the query result in JSON format.
 *
 * This function is marked with #[no_mangle] to ensure its symbol is preserved for
 * use in external C code.
 *
 * # Safety
 *
 * This function manipulates raw pointers and interfaces with asynchronous code,
 * requiring careful handling to ensure memory safety and avoid undefined behavior.
 *
 * # Arguments
 *
 * * `client_ptr` - A raw pointer to the SQL connection client.
 * * `query` - A pointer to a null-terminated C string representing the SQL query.
 * * `query_params` - A pointer to an array of null-terminated C strings representing query parameters.
 * * `query_params_len` - The number of query parameters in the array.
 *
 * # Returns
 *
 * A pointer to a null-terminated C string representing the JSON-formatted query result,
 * or a null pointer if an error occurs.
 *
 * # Examples
 *
 * ```
 * use std::ptr;
 *
 * let client_ptr: *mut std::ffi::c_void = ptr::null_mut();
 * let query = "SELECT * FROM table\0".as_ptr();
 * let query_params: *const *const std::os::raw::c_char = ptr::null();
 * let query_params_len = 0;
 * let result = libsql_php_query(client_ptr, query, query_params, query_params_len);
 * assert!(!result.is_null());
 * ```
 */
const char *libsql_php_query(void *client_ptr,
                             const char *query,
                             const char *const *query_params,
                             uintptr_t query_params_len);

/**
 * Establishes a connection to a local LibSQL database with optional encryption,
 * using the provided path, flags, and encryption key.
 *
 * This function is marked with #[no_mangle] to ensure its symbol is preserved for
 * use in external C code.
 *
 * # Safety
 *
 * This function manipulates raw pointers and interfaces with asynchronous code,
 * requiring careful handling to ensure memory safety and avoid undefined behavior.
 *
 * # Arguments
 *
 * * `path` - A pointer to a null-terminated C string representing the path to the database.
 * * `flags` - A pointer to a null-terminated C string representing the flags for database opening.
 * * `encryption_key` - A pointer to a null-terminated C string representing the encryption key.
 *
 * # Returns
 *
 * A raw pointer to a `Connection` object if the connection is successfully established,
 * otherwise a null pointer.
 *
 * # Examples
 *
 * ```
 * use std::ptr;
 *
 * let path = "example_path\0".as_ptr();
 * let flags = "LIBSQLPHP_OPEN_READWRITE\0".as_ptr();
 * let encryption_key = "example_key\0".as_ptr();
 * let conn_ptr = libsql_php_connect_local(path, flags, encryption_key);
 * assert!(!conn_ptr.is_null());
 * ```
 */
Connection *libsql_php_connect_local(const char *path,
                                     const char *flags,
                                     const char *encryption_key);

/**
 * Synchronizes the remote replica database associated with the provided `DbConnPair`.
 *
 * This function is marked with #[no_mangle] to ensure its symbol is preserved for
 * use in external C code.
 *
 * # Safety
 *
 * This function manipulates raw pointers and interfaces with asynchronous code,
 * requiring careful handling to ensure memory safety and avoid undefined behavior.
 *
 * # Arguments
 *
 * * `pair` - A raw pointer to a `DbConnPair` struct containing database and connection pointers.
 *
 * # Returns
 *
 * * `0` - If synchronization is successful.
 * * `-1` - If the provided database pair pointer is null.
 * * `-2` - If there is an error during synchronization.
 *
 * # Examples
 *
 * ```
 * use std::ptr;
 *
 * let pair: *mut DbConnPair = ptr::null_mut();
 * let result = libsql_php_sync(pair);
 * assert_eq!(result, -1);
 * ```
 */
int32_t libsql_php_sync(struct DbConnPair *pair);

/**
 * Connects to a new remote replica in a PHP extension.
 *
 * # Arguments
 *
 * * `path` - A pointer to a C-style string representing the path.
 * * `url` - A pointer to a C-style string representing the URL.
 * * `token` - A pointer to a C-style string representing the token.
 * * `sync_duration` - The synchronization duration in seconds.
 * * `read_your_writes` - An integer representing whether "read your writes" mode is enabled (1) or not (0).
 *
 * # Returns
 *
 * Returns a pointer to a memory location containing a pair of database connection objects (`DbConnPair`). If an error occurs or null pointers are provided, returns a null pointer.
 */
struct DbConnPair *libsql_php_connect_new_remote_replica(const char *path,
                                                         const char *url,
                                                         const char *token,
                                                         uintptr_t sync_duration,
                                                         int read_your_writes);

/**
 * Calls the `libsql_php_error` function if the provided client pointer is null,
 * otherwise closes the LibSQL connection and deallocates memory.
 *
 * # Safety
 *
 * This function dereferences raw pointers and requires proper handling to ensure
 * memory safety and avoid undefined behavior.
 *
 * # Arguments
 *
 * * `client_ptr` - A raw pointer to the LibSQL connection client.
 *
 * # Examples
 *
 * ```
 * use std::ptr;
 *
 * let client_ptr: *mut std::ffi::c_void = ptr::null_mut();
 * libsql_php_close(client_ptr);
 * ```
 */
void libsql_php_close(void *client_ptr);

/**
 * Retrieves the number of affected rows after executing a LibSQL query in a PHP extension.
 *
 * # Safety
 *
 * This function is marked as unsafe because it dereferences raw pointers.
 *
 * # Arguments
 *
 * * `client_ptr` - A raw pointer to the SQL connection client.
 *
 * # Returns
 *
 * Returns a pointer to a memory location containing the number of affected rows. If an error occurs or the client pointer is null, returns a null pointer.
 */
const uint64_t *libsql_php_affected_rows(void *client_ptr);

/**
 * Retrieves the version of the LibSQL library in use.
 *
 * # Returns
 *
 * Returns a pointer to a C-style string containing the version information of the LibSQL library.
 */
const char *libsql_version(void);
