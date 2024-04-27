# [Internal] - LibSQLPHPStmt

Represents a prepared statement for executing SQL queries with parameter binding.

## Constructor

Creates a new instance of the `LibSQLPHPStmt` class.

```php
public function __construct(
    FFI $ffi,
    $db,
    string $query
)
```

**Parameters:**

- `$ffi` - The FFI instance.
- `$db` - The database connection handle.
- `$query` - The SQL query string.

**Example**

```php
$stmt = new LibSQLPHPStmt($ffiInstance, $dbConnection, $query);
```

## Bind Param

Binds a PHP variable to a parameter in the prepared statement.

```php
public function bindParam(string $param, mixed &$variable, int $type = LIBSQLPHP_TEXT): void
```

**Parameters:**

- `$param` - The parameter name or position.
- `&$variable` - The PHP variable to bind.
- `$type` - The type of the parameter (optional).

## Bind Value

Binds a value to a parameter in the prepared statement.

```php
public function bindValue(string $param, mixed $value, int $type = LIBSQLPHP_TEXT): void
```

**Parameters:**

- `$param` - The parameter name or position.
- `$value` - The value to bind.
- `$type` - The type of the parameter (optional).

## Execute

Executes the prepared statement with bound parameters.

```php
public function execute(): bool
```

**Return:**

- `bool` - True if the execution was successful, false otherwise.

## Get SQL

Gets the SQL query string with parameter values replaced.

```php
public function getSQL(bool $expand = false): string|false
```

**Parameters:**

- `$expand` - Whether to expand named parameters (optional).

**Return:**

- `string|false` - The SQL query string with replaced parameter values, or false on failure.

## Param Count

Gets the number of parameters in the prepared statement.

```php
public function paramCount(): int
```

**Return:**

- `int` - The number of parameters.

## Read Only

Checks if the prepared statement is read-only.

```php
public function readOnly(): bool
```

**Return:**

- `bool` - True if the statement is read-only, false otherwise.

## Reset

Resets the prepared statement, clearing bound parameters.

```php
public function reset(): void
```

## Clear

Clears the values of bound parameters in the prepared statement.

```php
public function clear(): void
```

## Close

Closes the prepared statement, freeing resources.

```php
public function close(): void
```
