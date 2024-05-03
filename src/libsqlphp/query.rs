use crate::{
    utils::{errors::libsql_php_error, runtime::runtime},
    ERR_INVALID_QUERY_CONVERT, ERR_NULL_CLIENT_PTR,
};

/// Executes an LibSQL query using the provided client pointer, query string, and query parameters,
/// returning the query result in JSON format.
///
/// This function is marked with #[no_mangle] to ensure its symbol is preserved for
/// use in external C code.
///
/// # Safety
///
/// This function manipulates raw pointers and interfaces with asynchronous code,
/// requiring careful handling to ensure memory safety and avoid undefined behavior.
///
/// # Arguments
///
/// * `client_ptr` - A raw pointer to the SQL connection client.
/// * `query` - A pointer to a null-terminated C string representing the SQL query.
/// * `query_params` - A pointer to an array of null-terminated C strings representing query parameters.
/// * `query_params_len` - The number of query parameters in the array.
///
/// # Returns
///
/// A pointer to a null-terminated C string representing the JSON-formatted query result,
/// or a null pointer if an error occurs.
///
/// # Examples
///
/// ```
/// use std::ptr;
///
/// let client_ptr: *mut std::ffi::c_void = ptr::null_mut();
/// let query = "SELECT * FROM table\0".as_ptr();
/// let query_params: *const *const std::os::raw::c_char = ptr::null();
/// let query_params_len = 0;
/// let result = libsql_php_query(client_ptr, query, query_params, query_params_len);
/// assert!(!result.is_null());
/// ```
#[no_mangle]
pub extern "C" fn libsql_php_query(
    client_ptr: *mut libc::c_void,
    query: *const libc::c_char,
    query_params: *const *const libc::c_char,
    query_params_len: usize,
) -> *const libc::c_char {
    if client_ptr.is_null() || query.is_null() {
        libsql_php_error(
            ERR_NULL_CLIENT_PTR,
            "ERR_NULL_CLIENT_PTR",
        );
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

    let query_result = runtime().block_on(async {
        match client.query(query_str, params).await {
            Ok(mut rows) => {
                let mut results: Vec<std::collections::HashMap<String, libsql::Value>> = Vec::new();
                while let Ok(Some(row)) = rows.next().await {
                    let mut result = std::collections::HashMap::new();
                    for idx in 0..rows.column_count() {
                        let column_name = row.column_name(idx as i32).unwrap();
                        let value = row.get_value(idx).unwrap();
                        result.insert(column_name.to_string(), value);
                    }
                    results.push(result);
                }
                Ok(results)
            }
            Err(e) => Err(e),
        }
    });

    match query_result {
        Ok(results) => {
            let json = serde_json::to_string(&results).unwrap();
            let c_json = std::ffi::CString::new(json).unwrap();
            c_json.into_raw()
        }
        Err(e) => {
            libsql_php_error(
                &format!("{e}"),
                "ERR_QUERY_EXECUTION",
            );
            std::ptr::null()
        }
    }
}
