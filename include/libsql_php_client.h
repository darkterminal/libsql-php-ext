#define FFI_LIB "libsql_php_client.so"

void libsql_php_close(void *client_ptr);

LocalClient *libsql_php_open_file(const char *path);

const char *libsql_php_query(void *client_ptr, const char *query);

const int *libsql_php_exec(void *client_ptr, const char *query);

int libsql_php_sync(void *client_ptr);
