/// Retrieves the version of the LibSQL library in use.
///
/// # Returns
///
/// Returns a pointer to a C-style string containing the version information of the LibSQL library.
#[no_mangle]
pub extern "C" fn libsql_version() -> *const libc::c_char {
    let version =
        "LibSQL version ".to_string() + ": " + &libsql::version() + "-" + &libsql::version_number().to_string();
    let c_version = std::ffi::CString::new(version).expect("CString::new failed");
    c_version.into_raw() as *const libc::c_char
}
