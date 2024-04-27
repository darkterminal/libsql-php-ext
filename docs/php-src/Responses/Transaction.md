# [Internal] - Transaction

Represents a database transaction.

## Constructor

Creates a new instance of the Transaction class and begins a transaction with the specified behavior.

```php
public function __construct(
    FFI $ffi,
    $db,
    string $behavior
)
```

**Parameters:**

- `$ffi` - The FFI instance.
- `$db` - The database handle.
- `$behavior` - The behavior of the transaction.

**Example**

```php
$transaction = new Transaction($ffiInstance, $dbConnection, 'DEFERRED');
```

## Exec

Executes a SQL query within the transaction.

```php
public function exec(string $query, array $params = []): Transaction
```

**Parameters:**

- `$query` - The SQL query to execute.
- `$params` - Optional parameters for the query.

**Return:**

- `Transaction` - The Transaction instance.

## Is Autocommit

Checks whether the transaction is in autocommit mode.

```php
public function is_autocommit(): bool
```

**Return:**

- `bool` - True if the transaction is in autocommit mode, false otherwise.

## Changes

Retrieves the number of rows affected by the transaction.

```php
public function changes(): int
```

**Return:**

- `int` - The number of rows affected by the transaction.

## Last Insert Rowid

Retrieves the rowid of the most recently inserted row in the transaction.

```php
public function last_insert_rowid(): int
```

**Return:**

- `int` - The rowid of the most recently inserted row.

## Reset

Resets the transaction.

```php
public function reset(): void
```

## Commit

Commits the transaction.

```php
public function commit(): bool
```

**Return:**

- `bool` - True if the transaction is committed successfully, false otherwise.

## Rollback

Rolls back the transaction.

```php
public function rollback(): bool
```

**Return:**

- `bool` - True if the transaction is rolled back successfully, false otherwise.
