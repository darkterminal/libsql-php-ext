use crate::{utils::errors::libsql_php_error, ERR_NULL_CLIENT_PTR};

/// Retrieves the last inserted row ID from a SQL connection in a PHP extension.
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
/// Returns the last inserted row ID as an integer. If an error occurs or the client pointer is null, returns `0`.
#[no_mangle]
pub extern "C" fn libsql_php_last_insert_rowid(client_ptr: *mut libc::c_void) -> i64 {
    if client_ptr.is_null() {
        libsql_php_error(ERR_NULL_CLIENT_PTR, "ERR_NULL_CLIENT_PTR");
        return 0;
    }

    let client = unsafe { &mut *(client_ptr as *mut libsql::Connection) };

    let last_insert_rowid = client.last_insert_rowid();
    last_insert_rowid
}
