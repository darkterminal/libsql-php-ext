use crate::{utils::errors::libsql_php_error, ERR_NULL_CLIENT_PTR};

/// Calls the `libsql_php_error` function if the provided client pointer is null,
/// otherwise closes the LibSQL connection and deallocates memory.
///
/// # Safety
///
/// This function dereferences raw pointers and requires proper handling to ensure
/// memory safety and avoid undefined behavior.
///
/// # Arguments
///
/// * `client_ptr` - A raw pointer to the LibSQL connection client.
///
/// # Examples
///
/// ```
/// use std::ptr;
///
/// let client_ptr: *mut std::ffi::c_void = ptr::null_mut();
/// libsql_php_close(client_ptr);
/// ```
#[no_mangle]
pub extern "C" fn libsql_php_close(client_ptr: *mut libc::c_void) {
    if client_ptr.is_null() {
        libsql_php_error(ERR_NULL_CLIENT_PTR, "ERR_NULL_CLIENT_PTR");
        return;
    }

    let client = unsafe { Box::from_raw(client_ptr as *mut libsql::Connection) };
    drop(client);
}
