use crate::{
    utils::{errors::libsql_php_error, runtime::runtime},
    ERR_INVALID_ARGUMENTS,
};

/// Executes a batch SQL query in a PHP extension, interfacing with a provided LibSQL connection.
///
/// # Safety
///
/// This function is marked as unsafe because it dereferences raw pointers and performs FFI operations.
///
/// # Arguments
///
/// * `client_ptr` - A raw pointer to the LibSQL connection client.
/// * `query` - A raw pointer to a C-style string representing the batch SQL query to execute.
///
/// # Returns
///
/// Returns a pointer to a memory location indicating the success of the batch execution. If an error occurs, returns a null pointer.
#[no_mangle]
pub extern "C" fn libsql_php_execute_batch(
    client_ptr: *mut libc::c_void,
    query: *const libc::c_char,
) -> *const libc::c_int {
    if client_ptr.is_null() || query.is_null() {
        libsql_php_error(ERR_INVALID_ARGUMENTS, "ERR_INVALID_ARGUMENTS");
        return std::ptr::null_mut();
    }

    let client = unsafe { &mut *(client_ptr as *mut libsql::Connection) };

    let c_str_query = unsafe { std::ffi::CStr::from_ptr(query) };

    let query_str = match c_str_query.to_str() {
        Ok(str) => str,
        Err(_) => {
            libsql_php_error(
                "Error: Failed to convert query to string",
                "ERR_INVALID_QUERY_CONVERT",
            );
            return std::ptr::null();
        }
    };

    let exec_result = runtime().block_on(async { client.execute_batch(query_str).await });

    match exec_result {
        Ok(_) => std::ptr::null(),
        Err(e) => {
            libsql_php_error(&format!("Error: {e}"), "ERR_QUERY_EXECUTION");
            std::ptr::null_mut()
        }
    }
}
