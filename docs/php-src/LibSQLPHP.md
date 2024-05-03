# LibSQL PHP Extension

A lightweight PHP database library utilizing LibSQL and simple interface to interact with databases using LibSQL.

## Instanciated

The constructor of the LibSQLPHP class ensures that the provided parameters are valid, checks for the existence of necessary files, initializes the FFI interface for interacting with the LibSQL library, and opens a connection to the database. It encapsulates the initialization logic required for setting up a new instance of the LibSQLPHP class.

```php
public function __construct(
    string $path = "",
    int $flags = LIBSQLPHP_OPEN_READWRITE | LIBSQLPHP_OPEN_CREATE,
    string $encryptionKey = "",
    string $url = "",
    string $token = "",
    int $sync_interval = 5,
    bool $read_your_writes = true
)
```

**Parameters:**

- `$path` - **(Local/Remote Replica)** Path to the database file.
- `$flags` - **(Local)** Flags to control database opening mode. Default: `LIBSQLPHP_OPEN_READWRITE | LIBSQLPHP_OPEN_CREATE`
- `$encryptionKey` - **(Local/Remote Replica)** Encryption key for database (if applicable).
- `$url` - **(Remote Replica)** Base URL for HTTP connection (if applicable).
- `$token` - **(Remote Replica)** Authentication token for HTTP connection (if applicable).
- `$sync_interval` - **(Remote Replica)** Database sync duration in seconds (if applicable).
- `$read_your_writes` - **(Remote Replica)** Enable read-your-writes consistency (if applicable).

### Example Local File Connection

```php
// Minimal option
$db = new LibSQLPHP("file:database.db");

// Full option
$db = new LibSQLPHP("file:database.db", LIBSQLPHP_OPEN_READWRITE | LIBSQLPHP_OPEN_CREATE, "encryptionKey");
```

### Example In-Memory Connection

```php
$db = new LibSQLPHP(":memory:");
```

### Example Remote Replica Connection

```php
// Minimal option
$db = new LibSQLPHP(path: "file:database.db", url: $url, token: $token);

// Full option
$db = new LibSQLPHP(path: "file:database.db", url: $url, token: $token, sync_interval: 10, read_your_writes: true);
```

## Open

Instantiates an LibSQLPHP object and opens a connection to an LibSQL database. If the build includes encryption, then it will attempt to use the key.

```php
public function open(string $path, int $flags, string $encryptionKey = ""): void
```

**Parameters:**

- `$path` - Path to the database file. eg. `database.db` or `:memory:`
- `$flags` - Flags to control database opening mode. Default: `LIBSQLPHP_OPEN_READWRITE | LIBSQLPHP_OPEN_CREATE`
- `$encryptionKey` - Encryption key for database (if applicable).

**Example**

```php
$db = new LibSQLPHP();
$db->open("file:database.db");
```

## Is Autocommit

Checks whether the database connection is in autocommit mode.

```php
public function is_autocommit(): bool
```

**Example**

```php
$db = new LibSQLPHP("file:database.db");
$db->is_autocommit();
```

## Is Connected

Check if a connection to the database is established.

```php
public function is_connected(): bool
```

**Example**

```php
$db = new LibSQLPHP("file:database.db");
$db->is_connected();
```

## Close

Close the connection to the database.

```php
public function close(): void
```

**Example**

```php
$db = new LibSQLPHP("file:database.db");
if ($db->is_connected()) {
    echo $db->version() . PHP_EOL;
}
$db->close(); // Always close the database connection
```

## Version

Get the version of the LibSQL Binary.

```php
 public function version(): string
```

**Example**

```php
echo $db->version() . PHP_EOL;
```

## Query

Execute a query on the database.

```php
public function query(string $stmt, array $params = []): LibSQLPHPResult
```

**Parameters**
- `$stmt` - The SQL statement to execute.
- `$params` - The SQL statement parameters to execute.

**Return**
- `LibSQLPHPResult` - The result of the query. [Ref:LibSQLPHPResult](https://github.com/darkterminal/libsql-php-ext/blob/main/php-src/Responses/LibSQLPHPResult.php) / [Doc:LibSQLPHPResult](Responses/LibSQLPHPResult.md)

**Example**

```php
$result = $db->query("SELECT * FROM users LIMIT 5");
```

## Query Single

Execute a query and retrieve a single result from the database.

```php
public function querySingle(string $stmt, array $params = [], bool $entireRow = false): array|string
```

**Parameters**
- `$stmt` - The SQL statement to execute.
- `$params` - The SQL statement parameters to execute.
- `$entireRow` - Whether to return the entire row or a single value.

**Example**

```php
$result = $db->querySingle("SELECT name FROM users WHERE id = 1");
$result2 = $db->querySingle("SELECT name FROM users WHERE id = 2", true);
```

## Prepare

Prepare a SQL statement for execution.

```php
public function prepare(string $query): LibSQLPHPStmt
```

**Parameters**
- `$query` The SQL query to prepare.

**Return**
- `LibSQLPHPStmt` - A prepared statement object. [Ref:LibSQLPHPStmt](https://github.com/darkterminal/libsql-php-ext/blob/main/php-src/Responses/LibSQLPHPStmt.php) / [Doc:LibSQLPHPStmt](Responses/LibSQLPHPStmt.md)

**Example**

```php
// Bind Param
$stmt = $db->prepare("INSERT INTO persons (name, age) VALUES (:name, @age)");
$stmt->bindParam(':name', $baz, LIBSQLPHP_TEXT);
$stmt->bindParam('@age', $foo, LIBSQLPHP_INTEGER);
$baz = "Sarah";
$foo = 22;
$stmt->execute();

// Bind Value
$stmt = $db->prepare("INSERT INTO foo VALUES (?, ?)");
$stmt->bindValue(1, "baz", LIBSQLPHP_TEXT);
$stmt->bindValue(2, 5, LIBSQLPHP_INTEGER);
$stmt->execute();
```

## Changes

Get the number of rows affected by the last operation.

```php
public function changes(): int
```

**Example**

```php
$db->exec("DELETE FROM users WHERE id = 3");

$changes = $db->changes();
echo "The DELETE statement removed $changes rows";
```

## Las Insert Row ID

Retrieves the rowid of the most recently inserted row in the database.

**Example**

```php
$db->exec("INSERT INTO users (name) VALUES (?)", ["Marlo"]);
var_dump($db->last_insert_rowid());
```

## Transaction

Initiates a database transaction with the specified behavior.

**Parameters**
- `$behavior` - The behavior of the transaction (optional, defaults to `TransactionBehavior::Deferred`).

Other Behavior:
- `TransactionBehavior::Deferred`
- `TransactionBehavior::Immediate`
- `TransactionBehavior::ReadOnly`

**Return**
- `Transaction` - A Transaction instance representing the initiated transaction. [Ref:Transaction](https://github.com/darkterminal/libsql-php-ext/blob/main/php-src/Responses/Transaction.php) / [Doc:Transaction](Responses/Transaction.md)

**Example**

```php
$operations_successful = false;
$tx = $db->transaction(TransactionBehavior::Deferred);
$tx->exec("INSERT INTO users (name) VALUES (?)", ["Emanuel"]);
$tx->exec("INSERT INTO users (name) VALUES (?)", ["Darren"]);

if ($operations_successful) {
    $tx->commit();
    echo "Commit the changes" . PHP_EOL;
} else {
    $tx->rollback();
    echo "Rollback the changes" . PHP_EOL;
}
```
> NOTE: After `commit` or `rollback` the `$tx` will be free from memory

## Exec

Execute a SQL statement.

**Parameters**
- `$query` The SQL statement to execute.
- `$params` The SQL parameters to execute. (Optional)

```php
public function exec(string $query, array $params = []): bool
```

**Example**

```php
$db->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");
$db->exec("INSERT INTO users (name) VALUES ('Handoko')");
$db->exec("INSERT INTO users (name) VALUES ('Karlina')");
```

## Execute Batch

Convenience method to run multiple SQL statements (that cannot take any parameters).

```php
public function execute_batch(string $query): void
```

**Parameters**
- `$query` - The batch of SQL queries to execute.

**Example**

```php
$db->execute_batch("
    BEGIN;
    CREATE TABLE foo(x INTEGER);
    CREATE TABLE bar(y TEXT);
    COMMIT;
");
```

## Sync

Synchronize changes with the database server.

```php
public function sync(): int
```

> NOTE: Sync not work for local file connection (Not yet implement in Local Replica / Remote Replica).
