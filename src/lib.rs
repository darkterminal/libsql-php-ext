use libc::{c_char, c_void};
use libsql::{Builder, Connection, Value};
use once_cell::sync::OnceCell;
use tokio::runtime::Runtime;
use std::{collections::HashMap, ffi::{CStr, CString}, ptr};

fn runtime() -> &'static Runtime {
    static RUNTIME: OnceCell<Runtime> = OnceCell::new();

    RUNTIME.get_or_try_init(Runtime::new).unwrap()
}

fn libsql_php_error(msg: &str, code: &str) {
    let code_upper = code.to_uppercase();
    let message = format!("{msg} - {code_upper}");
    eprintln!("{}", message);
    std::process::exit(1);
}

#[no_mangle]
pub extern "C" fn libsql_php_close(client_ptr: *mut c_void) {
    if client_ptr.is_null() {
        libsql_php_error("Error: Client pointer is null", "ERR_NULL_CLIENT_PTR");
        return;
    }

    let client = unsafe { Box::from_raw(client_ptr as *mut Connection) };
    drop(client);
}

#[no_mangle]
pub extern "C" fn libsql_php_connect_local(path: *const c_char) -> *mut Connection {
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

    let rt = runtime();

    let conn = rt.block_on(async {
        let db = Builder::new_local(path_str).build().await.unwrap();
        let conn = db.connect().unwrap();

        Box::new(conn)
    });

    Box::into_raw(conn)
}

#[no_mangle]
pub extern "C" fn libsql_php_query(client_ptr: *mut c_void, query: *const c_char) -> *const c_char {
    if client_ptr.is_null() || query.is_null() {
        libsql_php_error("Error: Client pointer or query is null", "ERR_INVALID_ARGUMENTS");
        return ptr::null_mut();
    }

    let client = unsafe {
        &mut *(client_ptr as *mut Connection)
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

    let query_result = runtime().block_on(async {
        let mut rows = client.query(query_str, ()).await.unwrap();

        let mut results: Vec<HashMap<String, Value>> = Vec::new();
        while let Some(row) = rows.next().await.unwrap() {
            let mut result = HashMap::new();
            for idx in 0..rows.column_count() {
                let column_name = row.column_name(idx as i32).unwrap();
                let value = row.get_value(idx).unwrap();
                result.insert(column_name.to_string(), value);
            }
            results.push(result);
        }

        results
    });

    let json = serde_json::to_string(&query_result).unwrap();
    let c_json = CString::new(json).unwrap();
    c_json.into_raw()
}

