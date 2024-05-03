use crate::{
    utils::{errors::libsql_php_error, runtime::runtime},
    ERR_INVALID_ARGUMENTS, ERR_INVALID_QUERY_CONVERT,
};

/// Executes a SQL query in a PHP extension, interfacing with a provided LibSQL connection.
///
/// # Safety
///
/// This function is marked as unsafe because it dereferences raw pointers and performs FFI operations.
///
/// # Arguments
///
/// * `client_ptr` - A raw pointer to the LibSQL connection client.
/// * `query` - A raw pointer to a C-style string representing the SQL query to execute.
/// * `query_params` - A pointer to an array of raw pointers to C-style strings representing query parameters.
/// * `query_params_len` - The length of the `query_params` array.
///
/// # Returns
///
/// Returns a pointer to a memory location containing the number of rows affected by the query execution. If an error occurs, returns a null pointer.
#[no_mangle]
pub extern "C" fn libsql_php_exec(
    client_ptr: *mut libc::c_void,
    query: *const libc::c_char,
    query_params: *const *const libc::c_char,
    query_params_len: usize,
) -> *const i64 {
    if client_ptr.is_null() || query.is_null() {
        libsql_php_error(ERR_INVALID_ARGUMENTS, "ERR_INVALID_ARGUMENTS");
        return std::ptr::null_mut();
    }

    let client = unsafe { &mut *(client_ptr as *mut libsql::Connection) };

    let c_str_query = unsafe { std::ffi::CStr::from_ptr(query) };

    let query_str = match c_str_query.to_str() {
        Ok(str) => str,
        Err(_) => {
            libsql_php_error(ERR_INVALID_QUERY_CONVERT, "ERR_INVALID_QUERY_CONVERT");
            return std::ptr::null();
        }
    };

    let params = if !query_params.is_null() && query_params_len > 0 {
        let params_slice = unsafe { std::slice::from_raw_parts(query_params, query_params_len) };
        params_slice
            .iter()
            .filter_map(|&param_ptr| {
                if param_ptr.is_null() {
                    None
                } else {
                    let param_cstr = unsafe { std::ffi::CStr::from_ptr(param_ptr) };
                    param_cstr
                        .to_str()
                        .ok()
                        .map(|s| libsql::Value::from(s.to_string()))
                }
            })
            .collect::<Vec<libsql::Value>>()
    } else {
        Vec::new()
    };

    let is_empty_or_all_empty_strings = params.iter().all(|value| match value {
        libsql::Value::Text(s) => s.is_empty(),
        _ => false,
    });

    let exec_result = runtime().block_on(async {
        if is_empty_or_all_empty_strings {
            client.execute(query_str, ()).await
        } else {
            client.execute(query_str, params).await
        }
    });

    match exec_result {
        Ok(_) => {
            let rows_affected = Box::new(client.last_insert_rowid());
            Box::into_raw(rows_affected)
        }
        Err(e) => {
            libsql_php_error(&format!("Error: {e}"), "ERR_QUERY_EXECUTION");
            std::ptr::null_mut()
        }
    }
}
