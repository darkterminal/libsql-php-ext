use crate::{utils::errors::libsql_php_error, ERR_NULL_CLIENT_PTR};

/// Retrieves the number of affected rows after executing a LibSQL query in a PHP extension.
///
/// # Safety
///
/// This function is marked as unsafe because it dereferences raw pointers.
///
/// # Arguments
///
/// * `client_ptr` - A raw pointer to the SQL connection client.
///
/// # Returns
///
/// Returns a pointer to a memory location containing the number of affected rows. If an error occurs or the client pointer is null, returns a null pointer.
#[no_mangle]
pub extern "C" fn libsql_php_affected_rows(client_ptr: *mut libc::c_void) -> *const u64 {
    if client_ptr.is_null() {
        libsql_php_error(ERR_NULL_CLIENT_PTR, "ERR_NULL_CLIENT_PTR");
        return std::ptr::null_mut();
    }

    let client = unsafe { &mut *(client_ptr as *mut libsql::Connection) };

    let affected_rows: u64 = client.changes();
    Box::into_raw(Box::new(affected_rows))
}
