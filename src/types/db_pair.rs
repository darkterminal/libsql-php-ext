use libsql::{Connection, Database};

/// Represents a pair of pointers to a database and a connection.
/// 
/// This struct is marked with #[repr(C)] to ensure its memory layout is compatible
/// with C code.
#[repr(C)]
pub struct DbConnPair {
    /// A raw pointer to the database object.
    pub db: *mut Database,
    /// A raw pointer to the connection object.
    pub conn: *mut Connection,
}
