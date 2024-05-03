use crate::{utils::{errors::libsql_php_error, runtime::runtime}, ERR_NULL_CLIENT_PTR, ERR_STRING_CONVERTION};

/// Executes a LibSQL query within a transaction in a PHP extension.
///
/// # Safety
///
/// This function is marked as unsafe because it dereferences raw pointers.
///
/// # Arguments
///
/// * `trx_ptr` - A raw pointer to the transaction object.
/// * `query` - A raw pointer to a C-style string representing the LibSQL query to execute.
/// * `query_params` - A pointer to an array of raw pointers to C-style strings representing query parameters.
/// * `query_params_len` - The length of the `query_params` array.
///
/// # Returns
///
/// Returns a pointer to the transaction object if successful. If an error occurs or a null pointer is provided, returns a null pointer.
#[no_mangle]
pub extern "C" fn libsql_php_transaction_exec(
    trx_ptr: *mut libc::c_void,
    query: *const libc::c_char,
    query_params: *const *const libc::c_char,
    query_params_len: usize,
) -> *mut libsql::Transaction {
    if trx_ptr.is_null() || query.is_null() {
        libsql_php_error(ERR_NULL_CLIENT_PTR, "ERR_NULL_CLIENT_PTR");
        return std::ptr::null_mut();
    }

    let transaction = unsafe { &mut *(trx_ptr as *mut libsql::Transaction) };

    let c_str_query = unsafe { std::ffi::CStr::from_ptr(query) };

    let query_str = match c_str_query.to_str() {
        Ok(str) => str,
        Err(_) => {
            libsql_php_error(
                ERR_STRING_CONVERTION,
                "ERR_STRING_CONVERTION",
            );
            return std::ptr::null_mut();
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

    let result = runtime().block_on(async { transaction.execute(query_str, params).await });

    match result {
        Ok(_) => transaction,
        Err(e) => {
            libsql_php_error(
                &format!("executing transaction: {:?}", e),
                "ERR_EXECUTION_FAILED",
            );
            std::ptr::null_mut()
        }
    }
}
