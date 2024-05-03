use crate::{
    utils::{errors::libsql_php_error, runtime::runtime},
    ERR_INVALID_PATH_CONVERT, ERR_PATH_IS_EMPTY,
};

/// Establishes a connection to a local LibSQL database with optional encryption,
/// using the provided path, flags, and encryption key.
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
/// * `path` - A pointer to a null-terminated C string representing the path to the database.
/// * `flags` - A pointer to a null-terminated C string representing the flags for database opening.
/// * `encryption_key` - A pointer to a null-terminated C string representing the encryption key.
///
/// # Returns
///
/// A raw pointer to a `Connection` object if the connection is successfully established,
/// otherwise a null pointer.
///
/// # Examples
///
/// ```
/// use std::ptr;
///
/// let path = "example_path\0".as_ptr();
/// let flags = "LIBSQLPHP_OPEN_READWRITE\0".as_ptr();
/// let encryption_key = "example_key\0".as_ptr();
/// let conn_ptr = libsql_php_connect_local(path, flags, encryption_key);
/// assert!(!conn_ptr.is_null());
/// ```
#[no_mangle]
pub extern "C" fn libsql_php_connect_local(
    path: *const libc::c_char,
    flags: *const libc::c_char,
    encryption_key: *const libc::c_char,
) -> *mut libsql::Connection {
    if path.is_null() {
        libsql_php_error(ERR_PATH_IS_EMPTY, "ERR_PATH_IS_EMPTY");
        return std::ptr::null_mut();
    }

    let flags_str = if flags.is_null() {
        None
    } else {
        Some(
            unsafe { std::ffi::CStr::from_ptr(flags) }
                .to_str()
                .unwrap_or(""),
        )
    };

    let open_flags = match flags_str {
        Some("LIBSQLPHP_OPEN_READONLY") => libsql::OpenFlags::SQLITE_OPEN_READ_ONLY,
        Some("LIBSQLPHP_OPEN_READWRITE") => libsql::OpenFlags::SQLITE_OPEN_READ_WRITE,
        Some("LIBSQLPHP_OPEN_CREATE") => libsql::OpenFlags::SQLITE_OPEN_CREATE,
        Some("LIBSQLPHP_OPEN_READWRITE_LIBSQLPHP_OPEN_CREATE") => libsql::OpenFlags::default(),
        Some("LIBSQLPHP_OPEN_READONLY_LIBSQLPHP_OPEN_CREATE") => {
            libsql::OpenFlags::SQLITE_OPEN_READ_ONLY | libsql::OpenFlags::SQLITE_OPEN_CREATE
        }
        _ => libsql::OpenFlags::default(),
    };

    let c_str = unsafe { std::ffi::CStr::from_ptr(path) };

    let path_str = match c_str.to_str() {
        Ok(str) => str,
        Err(_) => {
            libsql_php_error(ERR_INVALID_PATH_CONVERT, "ERR_INVALID_PATH_CONVERT");
            return std::ptr::null_mut();
        }
    };

    let encryption_config = if !encryption_key.is_null() {
        let key_c_str = unsafe { std::ffi::CStr::from_ptr(encryption_key) };
        let key_str = key_c_str.to_str().unwrap_or("");
        Some(libsql::EncryptionConfig::new(
            libsql::Cipher::Aes256Cbc,
            key_str.as_bytes().to_vec().into(),
        ))
    } else {
        None
    };

    let rt = runtime();

    let conn = rt.block_on(async {
        let mut builder = libsql::Builder::new_local(path_str).flags(open_flags);

        if let Some(enc_config) = encryption_config {
            builder = builder.encryption_config(enc_config);
        }

        let db = builder.build().await.unwrap();
        let conn = db.connect().unwrap();

        Box::new(conn)
    });

    Box::into_raw(conn)
}
