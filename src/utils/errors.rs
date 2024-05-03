/// Prints an error message related to LibSQL operations in PHP format and exits the program.
/// 
/// # Arguments
/// 
/// * `msg` - A string slice representing the error message.
/// * `code` - A string slice representing the error code.
/// 
/// # Examples
/// 
/// ```
/// libsql_php_error("Connection failed", "CONN_ERR");
/// ```
pub fn libsql_php_error(msg: &str, code: &str) {
    let code_upper = code.to_uppercase();
    let message = format!("Error LibSQLPHP - {msg} - {code_upper}");
    eprintln!("{}", message);
    std::process::exit(1);
}
