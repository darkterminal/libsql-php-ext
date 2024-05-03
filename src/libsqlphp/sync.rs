use crate::{
    types::db_pair::DbConnPair,
    utils::{errors::libsql_php_error, runtime::runtime},
    ERR_DATABASE_PAIR_NOT_FOUND,
};

/// Synchronizes the remote replica database associated with the provided `DbConnPair`.
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
/// * `pair` - A raw pointer to a `DbConnPair` struct containing database and connection pointers.
///
/// # Returns
///
/// * `0` - If synchronization is successful.
/// * `-1` - If the provided database pair pointer is null.
/// * `-2` - If there is an error during synchronization.
///
/// # Examples
///
/// ```
/// use std::ptr;
///
/// let pair: *mut DbConnPair = ptr::null_mut();
/// let result = libsql_php_sync(pair);
/// assert_eq!(result, -1);
/// ```
#[no_mangle]
pub extern "C" fn libsql_php_sync(pair: *mut DbConnPair) -> i32 {
    if pair.is_null() {
        libsql_php_error(ERR_DATABASE_PAIR_NOT_FOUND, "ERR_DATABASE_PAIR_NOT_FOUND");
        return -1;
    }

    let pair = unsafe { &mut *pair };
    let db = unsafe { &mut *pair.db };

    let rt = runtime();
    let result = rt.block_on(async { db.sync().await });

    match result {
        Ok(_) => 0,   // Success
        Err(_) => -2, // Indicate sync error
    }
}
