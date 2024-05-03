use crate::{
    utils::{errors::libsql_php_error, runtime::runtime},
    ERR_NULL_CLIENT_PTR, ERR_TRANSACTION_COMMIT,
};

/// Commits a transaction in a PHP extension.
///
/// # Safety
///
/// This function is marked as unsafe because it dereferences raw pointers.
///
/// # Arguments
///
/// * `trx_ptr` - A raw pointer to the transaction object.
///
/// # Returns
///
/// Returns `1` if the transaction is successfully committed. If an error occurs or a null pointer is provided, returns `0`.
#[no_mangle]
pub extern "C" fn libsql_php_transaction_commit(trx_ptr: *mut libc::c_void) -> i64 {
    if trx_ptr.is_null() {
        libsql_php_error(ERR_NULL_CLIENT_PTR, "ERR_NULL_CLIENT_PTR");
        return 0;
    }

    let transaction = unsafe { std::ptr::read(trx_ptr as *mut libsql::Transaction) };

    let commited = runtime().block_on(async { transaction.commit().await });

    match commited {
        Ok(_) => 1,
        Err(_) => {
            libsql_php_error(ERR_TRANSACTION_COMMIT, "ERR_TRANSACTION_COMMIT");
            0
        }
    }
}
