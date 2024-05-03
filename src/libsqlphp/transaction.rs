use crate::{
    utils::{errors::libsql_php_error, runtime::runtime},
    ERR_NULL_CLIENT_PTR,
};

/// Initiates a LibSQL transaction in a PHP extension with the specified behavior.
///
/// # Safety
///
/// This function is marked as unsafe because it dereferences raw pointers.
///
/// # Arguments
///
/// * `client_ptr` - A raw pointer to the LibSQL connection client.
/// * `behavior` - A raw pointer to a C-style string representing the transaction behavior. Can be "DEFERRED", "WRITE", "READ", or null.
///
/// # Returns
///
/// Returns a pointer to a memory location containing the transaction object if successful. If an error occurs or the client pointer is null, returns a null pointer.
#[no_mangle]
pub extern "C" fn libsql_php_transaction(
    client_ptr: *mut libc::c_void,
    behavior: *const libc::c_char,
) -> *mut libsql::Transaction {
    if client_ptr.is_null() {
        libsql_php_error(ERR_NULL_CLIENT_PTR, "ERR_INVALID_ARGUMENTS");
        return std::ptr::null_mut();
    }

    let client = unsafe { &mut *(client_ptr as *mut libsql::Connection) };

    let behavior_str = if behavior.is_null() {
        None
    } else {
        Some(
            unsafe { std::ffi::CStr::from_ptr(behavior) }
                .to_str()
                .unwrap_or(""),
        )
    };

    let trx_behavior = match behavior_str {
        Some("DEFERRED") => libsql::TransactionBehavior::Deferred,
        Some("WRITE") => libsql::TransactionBehavior::Immediate,
        Some("READ") => libsql::TransactionBehavior::ReadOnly,
        _ => libsql::TransactionBehavior::Deferred,
    };

    let trx = runtime().block_on(async {
        let transaction = client.transaction_with_behavior(trx_behavior).await;
        transaction
    });

    match trx {
        Ok(trx) => Box::into_raw(Box::new(trx)),
        Err(e) => {
            libsql_php_error(&format!("{e}"), "ERR_INICIATE_TRANSACTION");
            std::ptr::null_mut()
        }
    }
}
