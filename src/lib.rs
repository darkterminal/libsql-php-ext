use libc::{c_char, c_int, c_void};
use libsql::{version, version_number, Builder, Cipher, Connection, EncryptionConfig, OpenFlags, Value};
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
pub extern "C" fn libsql_php_connect_local(path: *const c_char, flags: *const c_char, encryption_key: *const c_char) -> *mut Connection {
    fn from_cstring(c_str: *const c_char) -> Option<String> {
        if c_str.is_null() {
            return None;
        }
    
        unsafe {
            match CStr::from_ptr(c_str).to_str() {
                Ok(str) => Some(str.to_string()),
                Err(_) => {
                    eprintln!("Error converting CString to string");
                    None
                }
            }
        }
    }

    if path.is_null() {
        libsql_php_error("Error: Path is not defined", "ERR_PATH_IS_EMPTY");
        return ptr::null_mut();
    }

    let flags_str = match from_cstring(flags) {
        Some(str) => str,
        None => {
            libsql_php_error("Error: Failed to convert flags to string", "ERR_INVALID_FLAGS_CONVERT");
            return ptr::null_mut();
        },
    };

    let open_flags = match flags_str {
        Some("LIBSQLPHP_OPEN_READONLY") => OpenFlags::SQLITE_OPEN_READ_ONLY,
        Some("LIBSQLPHP_OPEN_READWRITE") => OpenFlags::SQLITE_OPEN_READ_WRITE,
        Some("LIBSQLPHP_OPEN_CREATE") => OpenFlags::SQLITE_OPEN_CREATE,
        Some("LIBSQLPHP_OPEN_READWRITE_LIBSQLPHP_OPEN_CREATE") => OpenFlags::default(),
        Some("LIBSQLPHP_OPEN_READONLY_LIBSQLPHP_OPEN_CREATE") => OpenFlags::SQLITE_OPEN_READ_ONLY | OpenFlags::SQLITE_OPEN_CREATE,
        _ => OpenFlags::default()
    };

    let c_str = unsafe {
        CStr::from_ptr(path)
    };

    let path_str = match from_cstring(path) {
        Some(str) => str,
        None => {
            libsql_php_error("Error: Failed to convert path to string", "ERR_INVALID_PATH_CONVERT");
            return ptr::null_mut();
        },
    };

    let encryption_config = if let Some(key_str) = from_cstring(encryption_key) {
        Some(EncryptionConfig::new(Cipher::Aes256Cbc, key_str.as_bytes().to_vec().into()))
    } else {
        libsql_php_error("Error: Failed to convert encryption key to string", "ERR_INVALID_KEY_CONVERT");
        None
    };

    let rt = runtime();

    let conn = rt.block_on(async {
        let mut builder = Builder::new_local(path_str)
            .flags(open_flags);

        if let Some(enc_config) = encryption_config {
            builder = builder.encryption_config(enc_config);
        }

        let db = builder.build().await.unwrap();
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

#[no_mangle]
pub extern "C" fn libsql_php_exec(client_ptr: *mut c_void, query: *const c_char) -> *const i64 {
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

    let exec_result = runtime().block_on(async { client.execute(query_str, ()).await });

    match exec_result {
        Ok(_) => {
            let rows_affected = Box::new(client.last_insert_rowid());
            Box::into_raw(rows_affected)
        },
        Err(_) => {
            libsql_php_error("Error: Query execution failed", "ERR_QUERY_EXECUTION");
            std::ptr::null_mut()
        }
    }
}

#[no_mangle]
pub extern "C" fn libsql_php_affected_rows(client_ptr: *mut c_void) -> *const u64 {
    if client_ptr.is_null() {
        libsql_php_error("Error: Client pointer is null", "ERR_INVALID_ARGUMENTS");
        return ptr::null_mut();
    }

    let client = unsafe {
        &mut *(client_ptr as *mut Connection)
    };

    let affected_rows: u64 = client.changes();
    Box::into_raw(Box::new(affected_rows))
}

#[no_mangle]
pub extern "C" fn libsql_php_reset(client_ptr: *mut c_void) -> *const c_int {
    if client_ptr.is_null() {
        libsql_php_error("Error: Client pointer is null", "ERR_INVALID_ARGUMENTS");
        return ptr::null_mut();
    }

    let client = unsafe {
        &mut *(client_ptr as *mut Connection)
    };

    runtime().block_on(client.reset());
    std::ptr::null()
}

#[no_mangle]
pub extern "C" fn libsql_version() -> *const c_char {
    let version = "LibSQL version ".to_string() + ": " + &version() + "-" + &version_number().to_string();
    let c_version = CString::new(version).expect("CString::new failed");
    c_version.into_raw() as *const c_char
}
