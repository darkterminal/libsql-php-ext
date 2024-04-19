use std::os::raw::{c_char, c_void};
use std::ffi::{CStr, CString};
use std::ptr;
use libc::c_int;
use libsql_client::local::Client;
use serde_json::to_string;

pub const FFI_LIB: &str = "libsql_php_client.so";

#[no_mangle]
pub extern "C" fn libsql_php_connect(path: *const c_char) -> *mut Client {
    if path.is_null() {
        return ptr::null_mut();
    }

    let c_str = unsafe {
        CStr::from_ptr(path)
    };

    let path_str = match c_str.to_str() {
        Ok(str) => str,
        Err(_) => return ptr::null_mut(),
    };

    let client = match Client::new(path_str) {
        Ok(client) => Box::new(client),
        Err(_) => return ptr::null_mut(),
    };

    Box::into_raw(client)
}

#[no_mangle]
pub extern "C" fn libsql_php_query(client_ptr: *mut c_void, query: *const c_char) -> *const c_char {
    if client_ptr.is_null() || query.is_null() {
        return ptr::null();
    }

    let client = unsafe {
        &mut *(client_ptr as *mut Client)
    };

    let c_str_query = unsafe {
        CStr::from_ptr(query)
    };

    let query_str = match c_str_query.to_str() {
        Ok(str) => str,
        Err(_) => return ptr::null(),
    };

    let result = match client.execute(query_str) {
        Ok(result_set) => {
            match to_string(&result_set) {
                Ok(json_str) => {
                    let c_str = CString::new(json_str).unwrap();

                    let ptr = unsafe { libc::malloc(c_str.as_bytes_with_nul().len()) } as *mut c_char;
                    
                    if ptr.is_null() {
                    
                        return ptr::null();
                    }

                    unsafe {
                        ptr.copy_from_nonoverlapping(c_str.as_ptr(), c_str.as_bytes_with_nul().len());
                    }
                    
                    ptr
                },
                Err(_) => return ptr::null(),
            }
        },
        Err(_) => return ptr::null(),
    };

    result as *const c_char
}

#[no_mangle]
pub extern "C" fn libsql_php_exec(client_ptr: *mut c_void, query: *const c_char) -> *const c_int {
    if client_ptr.is_null() || query.is_null() {
        return ptr::null();
    }

    let client = unsafe {
        &mut *(client_ptr as *mut Client)
    };

    let c_str_query = unsafe {
        CStr::from_ptr(query)
    };

    let query_str = match c_str_query.to_str() {
        Ok(str) => str,
        Err(_) => return ptr::null(),
    };

    let result = match client.execute(query_str) {
        Ok(_) => {
            let success_code: c_int = 1;
            let success_ptr = Box::into_raw(Box::new(success_code));
            success_ptr
        },
        Err(_) => {
            let error_code: c_int = 0;
            let error_ptr = Box::into_raw(Box::new(error_code));
            error_ptr
        }
    };

    result as *const c_int
}