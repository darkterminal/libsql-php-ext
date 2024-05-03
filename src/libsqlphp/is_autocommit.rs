use crate::{utils::errors::libsql_php_error, ERR_NULL_CLIENT_PTR};

/// Checks if autocommit is enabled for a LibSQL connection in a PHP extension.
///
/// # Safety
///
/// This function is marked as unsafe because it dereferences raw pointers.
///
/// # Arguments
///
/// * `client_ptr` - A raw pointer to the LibSQL connection client.
///
/// # Returns
///
/// Returns an integer representing whether autocommit is enabled (`1`) or not (`0`). If an error occurs or the client pointer is null, returns `0`.
#[no_mangle]
pub extern "C" fn libsql_php_is_autocommit(client_ptr: *mut libc::c_void) -> i64 {
    if client_ptr.is_null() {
        libsql_php_error(ERR_NULL_CLIENT_PTR, "ERR_NULL_CLIENT_PTR");
        return 0;
    }

    let client = unsafe { &mut *(client_ptr as *mut libsql::Connection) };

    let is_autocommit = client.is_autocommit();
    if is_autocommit {
        1
    } else {
        0
    }
}
