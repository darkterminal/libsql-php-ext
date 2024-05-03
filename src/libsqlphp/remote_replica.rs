use crate::{
    types::db_pair::DbConnPair,
    utils::{errors::libsql_php_error, runtime::runtime},
    ERR_INVALID_PATH_CONVERT, ERR_REMOTE_REPLICA_CONFIGURATION,
};

/// Connects to a new remote replica in a PHP extension.
///
/// # Arguments
///
/// * `path` - A pointer to a C-style string representing the path.
/// * `url` - A pointer to a C-style string representing the URL.
/// * `token` - A pointer to a C-style string representing the token.
/// * `sync_duration` - The synchronization duration in seconds.
/// * `read_your_writes` - An integer representing whether "read your writes" mode is enabled (1) or not (0).
///
/// # Returns
///
/// Returns a pointer to a memory location containing a pair of database connection objects (`DbConnPair`). If an error occurs or null pointers are provided, returns a null pointer.
#[no_mangle]
pub extern "C" fn libsql_php_connect_new_remote_replica(
    path: *const libc::c_char,
    url: *const libc::c_char,
    token: *const libc::c_char,
    sync_duration: usize,
    read_your_writes: libc::c_int,
) -> *mut DbConnPair {
    if path.is_null() && url.is_null() && token.is_null() {
        libsql_php_error(
            ERR_REMOTE_REPLICA_CONFIGURATION,
            "ERR_REMOTE_REPLICA_CONFIGURATION",
        );
        return std::ptr::null_mut();
    }

    let c_str = unsafe { std::ffi::CStr::from_ptr(path) };

    let path_str = match c_str.to_str() {
        Ok(str) => str,
        Err(_) => {
            libsql_php_error(ERR_INVALID_PATH_CONVERT, "ERR_INVALID_PATH_CONVERT");
            return std::ptr::null_mut();
        }
    };

    let u_str = unsafe { std::ffi::CStr::from_ptr(url) };

    let url_str = match u_str.to_str() {
        Ok(str) => str,
        Err(_) => {
            libsql_php_error(ERR_INVALID_PATH_CONVERT, "ERR_INVALID_PATH_CONVERT");
            return std::ptr::null_mut();
        }
    };

    let t_str = unsafe { std::ffi::CStr::from_ptr(token) };

    let token_str = match t_str.to_str() {
        Ok(str) => str,
        Err(_) => {
            libsql_php_error(ERR_INVALID_PATH_CONVERT, "ERR_INVALID_PATH_CONVERT");
            return std::ptr::null_mut();
        }
    };

    let rt = runtime();

    let pair = rt.block_on(async {
        let mut builder = libsql::Builder::new_remote_replica(
            path_str,
            url_str.to_string(),
            token_str.to_string(),
        );

        let periodic_sync: u64 = if sync_duration < 1 {
            5
        } else {
            sync_duration.try_into().unwrap()
        };

        builder = builder.sync_interval(std::time::Duration::from_secs(periodic_sync));

        let read_your_writes_bool = if read_your_writes == 0 { false } else { true };
        builder = builder.read_your_writes(read_your_writes_bool);

        let db = builder.build().await.unwrap();
        let conn = db.connect().unwrap();

        let pair = Box::new(DbConnPair {
            db: Box::into_raw(Box::new(db)),
            conn: Box::into_raw(Box::new(conn)),
        });

        Box::into_raw(pair)
    });

    pair
}
