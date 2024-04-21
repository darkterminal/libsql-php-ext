#define FFI_LIB "libsql_php_client.so"

void libsql_php_close(void *client_ptr);

Connection *libsql_php_connect_local(const char *path);

const char *libsql_php_query(void *client_ptr, const char *query);
