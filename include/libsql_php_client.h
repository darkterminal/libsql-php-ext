#ifndef LIBSQL_PHP_CLIENT_H
#define LIBSQL_PHP_CLIENT_H

#include <stdlib.h>

#ifdef __cplusplus
extern "C" {
#endif

#define FFI_LIB "libsql_php_client.so"

void* libsql_php_connect(const char* path);

const char* libsql_php_query(void* client_ptr, const char* query);

const int* libsql_php_exec(void* client_ptr, const char* query);

void free(void* ptr);

#ifdef __cplusplus
}
#endif

#endif // LIBSQL_PHP_CLIENT_H