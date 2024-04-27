# [Internal] - LibSQLPHPResult

Represents the result set obtained from executing a query using LibSQLPHP.

## Constructor

Creates a new instance of the `LibSQLPHPResult` class.

```php
public function __construct(
    FFI $ffi,
    mixed $db,
    array $data
)
```

**Parameters:**

- `$ffi` - The FFI instance.
- `$db` - The database connection handle.
- `$data` - The raw result data obtained from LibSQLPHP.

**Example**

```php
$result = new LibSQLPHPResult($ffiInstance, $dbConnection, $rawData);
```

## Fetch Array

Fetches the result set as an array.

```php
public function fetchArray(int $mode = LIBSQLPHP_BOTH): array
```

**Parameters:**

- `$mode` - The fetching mode (optional).

**Return:**

- `array` - The fetched result set.

## Num Columns

Gets the number of columns in the result set.

```php
public function numColumns(): int
```

**Return:**

- `int` - The number of columns.

## Column Name

Gets the name of a column by index.

```php
public function columName(int|null $index = null): array|string|false
```

**Parameters:**

- `$index` - The index of the column (optional).

**Return:**

- `array|string|false` - The name of the column, or an array of all column names, or false if the index is out of range.

## Column Type

Gets the type of a column by name or index.

```php
public function columnType(int|string|null $column = null): array|string|false
```

**Parameters:**

- `$column` - The name or index of the column (optional).

**Return:**

- `array|string|false` - The type of the column, or an array of all column types, or false if the column does not exist.

## Fetch Raw

Gets the raw result data.

```php
public function fetchRaw(): array
```

**Return:**

- `array` - The raw result data.

## Finalize

Finalizes the result set and frees resources.

```php
public function finalize(): bool
```

**Return:**

- `bool` - Always true.

## Reset

Resets the result set for re-execution.

```php
public function reset(): bool
```

**Return:**

- `bool` - True if reset was successful, false otherwise.
