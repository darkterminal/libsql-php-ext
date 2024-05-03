pub mod libsqlphp;
pub mod types;
pub mod utils;

const ERR_NULL_CLIENT_PTR: &str = "Client pointer is null";
const ERR_PATH_IS_EMPTY: &str = "Path is not defined";
const ERR_INVALID_PATH_CONVERT: &str = "Failed to convert path to string";
const ERR_INVALID_ARGUMENTS: &str = "Client pointer or query is null";
const ERR_INVALID_QUERY_CONVERT: &str = "Failed to convert query to string";
const ERR_REMOTE_REPLICA_CONFIGURATION: &str = "Error remote replica configuration";
const ERR_DATABASE_PAIR_NOT_FOUND: &str = "Database pair is not found";
const ERR_TRANSACTION_COMMIT: &str = "Transaction commit failed";
const ERR_TRANSACTION_ROLLBACK: &str = "Transaction rollback failed";
const ERR_STRING_CONVERTION: &str = "Failed to convert query to string";
