#[allow(deprecated)]

use std::ffi::{CStr, CString};
use std::ptr;
use libc::{c_char, c_int, c_void};
use libsql_client::local::Client as LocalClient;
use serde_json::to_string;
use once_cell::sync::OnceCell;
use tokio::runtime::Runtime;

fn runtime() -> &'static Runtime {
    static RUNTIME: OnceCell<Runtime> = OnceCell::new();

    RUNTIME.get_or_try_init(Runtime::new).unwrap()
}

pub const FFI_LIB: &str = "libsql_php_client.so";

pub fn libsql_php_error(msg: &str, code: &str) {
    let code_upper = code.to_uppercase();
    eprintln!("{msg} - {code_upper}");
    std::process::exit(1);
}

#[no_mangle]
pub extern "C" fn libsql_php_close(client_ptr: *mut c_void) {
    if client_ptr.is_null() {
        libsql_php_error("Error: Client pointer is null", "ERR_NULL_CLIENT_PTR");
        return;
    }

    let client = unsafe { Box::from_raw(client_ptr as *mut LocalClient) };
    drop(client);
}

#[no_mangle]
pub extern "C" fn libsql_php_open_file(path: *const c_char) -> *mut LocalClient {
    if path.is_null() {
        libsql_php_error("Error: Path is not defined", "ERR_PATH_IS_EMPTY");
        return ptr::null_mut();
    }

    let c_str = unsafe {
        CStr::from_ptr(path)
    };

    let path_str = match c_str.to_str() {
        Ok(str) => str,
        Err(_) => {
            libsql_php_error("Error: Failed to convert path to string", "ERR_INVALID_PATH_CONVERT");
            return ptr::null_mut();
        },
    };

    let client = if path_str == ":memory:" {
        match LocalClient::in_memory() {
            Ok(client) => Box::new(client),
            Err(_) => {
                libsql_php_error("Error: Failed to create in-memory database", "ERR_CREATE_INMERORY_DATABASE");
                return ptr::null_mut();
            },
        }
    } else {
        match LocalClient::new(path_str) {
            Ok(client) => Box::new(client),
            Err(_) => {
                libsql_php_error("Error: Failed to create database client", "ERR_CREATE_DATABASE_CLIENT");
                return ptr::null_mut();
            },
        }
    };

    Box::into_raw(client)
}

#[no_mangle]
pub extern "C" fn libsql_php_query(client_ptr: *mut c_void, query: *const c_char) -> *const c_char {
    if client_ptr.is_null() || query.is_null() {
        libsql_php_error("Error: Client pointer or query is null", "ERR_INVALID_ARGUMENTS");
        return ptr::null();
    }

    let client = unsafe {
        &mut *(client_ptr as *mut LocalClient)
    };

    let c_str_query = unsafe {
        CStr::from_ptr(query)
    };

    let query_str = match c_str_query.to_str() {
        Ok(str) => str,
        Err(_) => {
            libsql_php_error("Error: Failed to convert query to string", "ERR_INVALID_QUERY_CONVERT");
            return ptr::null();
        },
    };

    let result = match client.execute(query_str) {
        Ok(result_set) => {
            match to_string(&result_set) {
                Ok(json_str) => {
                    let c_str = CString::new(json_str).unwrap();

                    let ptr = unsafe { libc::malloc(c_str.as_bytes_with_nul().len()) } as *mut c_char;
                    
                    if ptr.is_null() {
                        libsql_php_error("Error: Failed to allocate memory for result", "ERR_MEMORY_ALLOCATION");
                        return ptr::null();
                    }

                    unsafe {
                        ptr.copy_from_nonoverlapping(c_str.as_ptr(), c_str.as_bytes_with_nul().len());
                    }
                    
                    ptr
                },
                Err(_) => {
                    libsql_php_error("Error: Failed to serialize result set to JSON", "ERR_JSON_SERIALIZATION");
                    return ptr::null();
                },
            }
        },
        Err(_) => {
            libsql_php_error("Error: Query execution failed", "ERR_QUERY_EXECUTION");
            return ptr::null();
        },
    };

    result as *const c_char
}

#[no_mangle]
pub extern "C" fn libsql_php_exec(client_ptr: *mut c_void, query: *const c_char) -> *const c_int {
    if client_ptr.is_null() || query.is_null() {
        libsql_php_error("Error: Client pointer or query is null", "ERR_INVALID_ARGUMENTS");
        return ptr::null();
    }

    let client = unsafe {
        &mut *(client_ptr as *mut LocalClient)
    };

    let c_str_query = unsafe {
        CStr::from_ptr(query)
    };

    let query_str = match c_str_query.to_str() {
        Ok(str) => str,
        Err(_) => {
            libsql_php_error("Error: Failed to convert query to string", "ERR_INVALID_QUERY_CONVERT");
            return ptr::null();
        },
    };

    let result = match client.execute(query_str) {
        Ok(_) => {
            let success_code: c_int = 0;
            let success_ptr = Box::into_raw(Box::new(success_code));
            success_ptr
        },
        Err(_) => {
            let error_code: c_int = 1;
            let error_ptr = Box::into_raw(Box::new(error_code));
            error_ptr
        }
    };

    result as *const c_int
}

#[no_mangle]
pub extern "C" fn libsql_php_sync(client_ptr: *mut c_void) -> c_int {
    if client_ptr.is_null() {
        libsql_php_error("Error: Client pointer is null", "ERR_NULL_CLIENT_PTR");
        return 1; // Error code for null client pointer
    }

    let client = unsafe {
        &mut *(client_ptr as *mut LocalClient)
    };

    let result = runtime().block_on(client.sync());

    match result {
        Ok(_) => 0, // Success
        Err(_) => {
            libsql_php_error("Error: Sync operation failed", "ERR_SYNC_OPERATION");
            2 // Error code for sync operation failure
        }
    }
}
