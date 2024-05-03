use once_cell::sync::OnceCell;
use tokio::runtime::Runtime;

/// Retrieves a reference to the global Tokio runtime.
/// 
/// # Returns
/// 
/// A reference to the Tokio runtime.
/// 
/// # Examples
/// 
/// ```
/// let rt = runtime();
/// ```
pub fn runtime() -> &'static Runtime {
    static RUNTIME: OnceCell<Runtime> = OnceCell::new();

    RUNTIME.get_or_try_init(Runtime::new).unwrap()
}
