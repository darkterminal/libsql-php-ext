use libc::{c_char, c_int, c_void};
use libsql::{version, version_number, Builder, Cipher, Connection, EncryptionConfig, OpenFlags, Transaction, TransactionBehavior, Value};
use once_cell::sync::OnceCell;
use tokio::runtime::Runtime;
use std::{collections::HashMap, ffi::{CStr, CString}, ptr, slice};

const ERR_NULL_CLIENT_PTR: &str = "Error: Client pointer is null";
const ERR_PATH_IS_EMPTY: &str = "Error: Path is not defined";
// const ERR_INVALID_FLAGS_CONVERT: &str = "Error: Failed to convert flags to string";
const ERR_INVALID_PATH_CONVERT: &str = "Error: Failed to convert path to string";
// const ERR_INVALID_KEY_CONVERT: &str = "Error: Failed to convert encryption key to string";
const ERR_INVALID_ARGUMENTS: &str = "Error: Client pointer or query is null";
const ERR_INVALID_QUERY_CONVERT: &str = "Error: Failed to convert query to string";
const ERR_QUERY_EXECUTION: &str = "Error: Query execution failed";

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
        libsql_php_error(ERR_NULL_CLIENT_PTR, "ERR_NULL_CLIENT_PTR");
        return;
    }

    let client = unsafe { Box::from_raw(client_ptr as *mut Connection) };
    drop(client);
}

#[no_mangle]
pub extern "C" fn libsql_php_connect_local(path: *const c_char, flags: *const c_char, encryption_key: *const c_char) -> *mut Connection {
    if path.is_null() {
        libsql_php_error(ERR_PATH_IS_EMPTY, "ERR_PATH_IS_EMPTY");
        return ptr::null_mut();
    }

    let flags_str = if flags.is_null() {
        None
    } else {
        Some(unsafe { CStr::from_ptr(flags) }.to_str().unwrap_or(""))
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

    let path_str = match c_str.to_str() {
        Ok(str) => str,
        Err(_) => {
            libsql_php_error(ERR_INVALID_PATH_CONVERT, "ERR_INVALID_PATH_CONVERT");
            return ptr::null_mut();
        },
    };

    let encryption_config = if !encryption_key.is_null() {
        let key_c_str = unsafe { CStr::from_ptr(encryption_key) };
        let key_str = key_c_str.to_str().unwrap_or("");
        Some(EncryptionConfig::new(Cipher::Aes256Cbc, key_str.as_bytes().to_vec().into()))
    } else {
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
            libsql_php_error(ERR_INVALID_QUERY_CONVERT, "ERR_INVALID_QUERY_CONVERT");
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
pub extern "C" fn libsql_php_exec(
    client_ptr: *mut c_void,
    query: *const c_char,
    query_params: *const *const c_char, 
    query_params_len: usize
) -> *const i64 {
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
            libsql_php_error(ERR_INVALID_QUERY_CONVERT, "ERR_INVALID_QUERY_CONVERT");
            return ptr::null();
        },
    };

    let params = if !query_params.is_null() && query_params_len > 0 {
        let params_slice = unsafe { slice::from_raw_parts(query_params, query_params_len) };
        params_slice.iter().filter_map(|&param_ptr| {
            if param_ptr.is_null() {
                None
            } else {
                let param_cstr = unsafe { CStr::from_ptr(param_ptr) };
                param_cstr.to_str().ok().map(|s| libsql::Value::from(s.to_string()))
            }
        }).collect::<Vec<libsql::Value>>()
    } else {
        Vec::new()
    };

    let exec_result = runtime().block_on(async { 
        client.execute(query_str, params).await
    });

    match exec_result {
        Ok(_) => {
            let rows_affected = Box::new(client.last_insert_rowid());
            Box::into_raw(rows_affected)
        },
        Err(_) => {
            libsql_php_error(ERR_INVALID_QUERY_CONVERT, "ERR_QUERY_EXECUTION");
            std::ptr::null_mut()
        }
    }
}

#[no_mangle]
pub extern "C" fn libsql_php_execute_batch(client_ptr: *mut c_void, query: *const c_char) -> *const c_int {
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

    let exec_result = runtime().block_on(async { 
        client.execute_batch(query_str).await
    });

    match exec_result {
        Ok(_) => std::ptr::null(),
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

#[no_mangle]
pub extern "C" fn libsql_php_is_autocommit(client_ptr: *mut c_void) -> i64 {
    if client_ptr.is_null() {
        libsql_php_error("Error: Client pointer is null", "ERR_INVALID_ARGUMENTS");
        return 0;
    }

    let client = unsafe {
        &mut *(client_ptr as *mut Connection)
    };

    let is_autocommit = client.is_autocommit();
    if is_autocommit {
        1
    } else {
        0
    }
}

#[no_mangle]
pub extern "C" fn libsql_php_last_insert_rowid(client_ptr: *mut c_void) -> i64 {
    if client_ptr.is_null() {
        libsql_php_error("Error: Client pointer is null", "ERR_INVALID_ARGUMENTS");
        return 0;
    }

    let client = unsafe {
        &mut *(client_ptr as *mut Connection)
    };

    let last_insert_rowid = client.last_insert_rowid();
    last_insert_rowid
}

#[no_mangle]
pub extern "C" fn libsql_php_transaction(client_ptr: *mut c_void, behavior: *const c_char) -> *mut Transaction {
    if client_ptr.is_null() {
        libsql_php_error("Error: Client pointer is null", "ERR_INVALID_ARGUMENTS");
        return ptr::null_mut();
    }

    let client = unsafe {
        &mut *(client_ptr as *mut Connection)
    };

    let behavior_str = if behavior.is_null() {
        None
    } else {
        Some(unsafe { CStr::from_ptr(behavior) }.to_str().unwrap_or(""))
    };

    let trx_behavior = match behavior_str {
        Some("DEFERRED") => TransactionBehavior::Deferred,
        Some("WRITE") => TransactionBehavior::Immediate,
        Some("READ") => TransactionBehavior::ReadOnly,
        _ => TransactionBehavior::Deferred
    };

    let trx = runtime().block_on(async {
        let transaction = client.transaction_with_behavior(trx_behavior).await.unwrap();
        transaction
    });

    Box::into_raw(Box::new(trx))
}

#[no_mangle]
pub extern "C" fn libsql_php_transaction_exec(
    trx_ptr: *mut c_void,
    query: *const c_char,
    query_params: *const *const c_char, 
    query_params_len: usize
) -> *mut Transaction {
    if trx_ptr.is_null() || query.is_null() {
        libsql_php_error("Error: Null pointer provided", "ERR_INVALID_ARGUMENTS");
        return ptr::null_mut();
    }

    let transaction = unsafe {
        &mut *(trx_ptr as *mut Transaction)
    };

    let c_str_query = unsafe {
        CStr::from_ptr(query)
    };

    let query_str = match c_str_query.to_str() {
        Ok(str) => str,
        Err(_) => {
            libsql_php_error("Error: Failed to convert query to string", "ERR_INVALID_QUERY_CONVERT");
            return ptr::null_mut();
        },
    };

    let params = if !query_params.is_null() && query_params_len > 0 {
        let params_slice = unsafe { slice::from_raw_parts(query_params, query_params_len) };
        params_slice.iter().filter_map(|&param_ptr| {
            if param_ptr.is_null() {
                None
            } else {
                let param_cstr = unsafe { CStr::from_ptr(param_ptr) };
                param_cstr.to_str().ok().map(|s| libsql::Value::from(s.to_string()))
            }
        }).collect::<Vec<libsql::Value>>()
    } else {
        Vec::new()
    };

    let result = runtime().block_on(async {
        transaction.execute(query_str, params).await
    });

    match result {
        Ok(_) => transaction,
        Err(e) => {
            libsql_php_error(&format!("Error executing transaction: {:?}", e), "ERR_EXECUTION_FAILED");
            ptr::null_mut()
        }
    }
}

#[no_mangle]
pub extern "C" fn libsql_php_transaction_commit(trx_ptr: *mut c_void) -> i64 {
    if trx_ptr.is_null() {
        libsql_php_error("Error: Null pointer provided", "ERR_INVALID_ARGUMENTS");
        return 0;
    }

    let transaction = unsafe {
        std::ptr::read(trx_ptr as *mut Transaction)
    };

    let commited = runtime().block_on(async { transaction.commit().await });

    match commited {
        Ok(_) => 1,
        Err(_) => {
            libsql_php_error("Error: Transaction commit failed", "ERR_COMMIT_FAILED");
            0
        }
    }
}

#[no_mangle]
pub extern "C" fn libsql_php_transaction_rollback(trx_ptr: *mut c_void) -> i64 {
    if trx_ptr.is_null() {
        libsql_php_error("Error: Null pointer provided", "ERR_INVALID_ARGUMENTS");
        return 0;
    }

    let transaction = unsafe {
        std::ptr::read(trx_ptr as *mut Transaction)
    };

    let rollback = runtime().block_on(async { transaction.rollback().await });

    match rollback {
        Ok(_) => 1,
        Err(_) => {
            libsql_php_error("Error: Transaction rollback failed", "ERR_ROLLBACK_FAILED");
            0
        }
    }
}
