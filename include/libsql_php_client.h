#define FFI_LIB "libsql_php_client.so"

void libsql_php_close(void *client_ptr);

Connection *libsql_php_connect_local(const char *path,
                                     const char *flags,
                                     const char *encryption_key);

const char *libsql_php_query(void *client_ptr, const char *query);

const int64_t *libsql_php_exec(void *client_ptr, const char *query);

const uint64_t *libsql_php_affected_rows(void *client_ptr);

const int *libsql_php_reset(void *client_ptr);

const char *libsql_version(void);
