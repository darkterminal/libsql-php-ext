#define FFI_LIB "libsql_php_client.so"

void libsql_php_close(void *client_ptr);

Connection *libsql_php_connect_local(const char *path,
                                     const char *flags,
                                     const char *encryption_key);

const char *libsql_php_query(void *client_ptr,
                             const char *query,
                             const char *const *query_params,
                             uintptr_t query_params_len);

const int64_t *libsql_php_exec(void *client_ptr,
                               const char *query,
                               const char *const *query_params,
                               uintptr_t query_params_len);

const int *libsql_php_execute_batch(void *client_ptr, const char *query);

const uint64_t *libsql_php_affected_rows(void *client_ptr);

const int *libsql_php_reset(void *client_ptr);

const char *libsql_version(void);

int64_t libsql_php_is_autocommit(void *client_ptr);

int64_t libsql_php_last_insert_rowid(void *client_ptr);

Transaction *libsql_php_transaction(void *client_ptr, const char *behavior);

Transaction *libsql_php_transaction_exec(void *trx_ptr,
                                         const char *query,
                                         const char *const *query_params,
                                         uintptr_t query_params_len);

int64_t libsql_php_transaction_commit(void *trx_ptr);

int64_t libsql_php_transaction_rollback(void *trx_ptr);
