use crate::{
    utils::{errors::libsql_php_error, runtime::runtime},
    ERR_NULL_CLIENT_PTR,
};

/// Resets a LibSQL connection in a PHP extension.
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
/// Returns a pointer to a memory location indicating the success of the reset operation. If an error occurs or the client pointer is null, returns a null pointer.
#[no_mangle]
pub extern "C" fn libsql_php_reset(client_ptr: *mut libc::c_void) -> *const libc::c_int {
    if client_ptr.is_null() {
        libsql_php_error(ERR_NULL_CLIENT_PTR, "ERR_NULL_CLIENT_PTR");
        return std::ptr::null_mut();
    }

    let client = unsafe { &mut *(client_ptr as *mut libsql::Connection) };

    runtime().block_on(client.reset());
    std::ptr::null()
}
