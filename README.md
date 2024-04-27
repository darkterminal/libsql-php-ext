<p align="center">
    <img src="https://i.imgur.com/3tGQJNa.png" alt="LibSQL PHP Extension" width="1000">
</p>
<p align="center">
  <b>LibSQL Extension for PHP</b> <br />
  The Core Dependency of <a href="https://github.com/darkterminal/libsql-client-php" target="_blank"><b>LibSQL Client PHP</b></a>
</p>

## Requirements

- Linux or Darwin OS
- C/C++ Compiler
- jq
- Rust Installed
- PHP Installed
- FFI Extension is Enabled (_Why? I read the C heder definition from wrapper_)

## 🚨 DISCLAIMER 🚨

I only tested using Linux. This library is stand-alone and can be used to perform database operations using LibSQL locally or in-memory similar to using SQLite3.

## How To Try it?

**Install**

```bash
composer require darkterminal/libsql-php-ext
```

**Build The Extension**

```bash
./vendor/bin/build
```

## 💡 Usage Examples and Available Features

```php
<?php

use Darkterminal\LibSQLPHPExtension\LibSQLPHP;

require_once 'vendor/autoload.php';

$db = new LibSQLPHP("file:database.db");
if ($db->is_connected()) {
    echo $db->version() . PHP_EOL;
}
$db->close(); // Always close the database connection
```

### Exec
```php
$db->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");
$db->exec("INSERT INTO users (name) VALUES ('Handoko')");
$db->exec("INSERT INTO users (name) VALUES ('Karlina')");
```

### Execute Batch

Convenience method to run multiple SQL statements (that cannot take any parameters).
```php
$db->execute_batch("
    BEGIN;
    CREATE TABLE foo(x INTEGER);
    CREATE TABLE bar(y TEXT);
    COMMIT;
");
```

### Get Last Insert ID
```php
var_dump($db->last_insert_rowid());
```

### Query Database
```php
$result = $db->query("SELECT * FROM users LIMIT 5");
```

### Return as Raw Format
```php
echo "Return as raw:" . PHP_EOL;
var_dump($result->fetchRaw());

// Result
// array(2) {
//   ["columns"]=>
//   array(2) {
//     ["name"]=>
//     string(4) "Text"
//     ["id"]=>
//     string(7) "Integer"
//   }
//   ["rows"]=>
//   array(5) {
//     [0]=>
//     array(2) {
//       [0]=>
//       string(5) "Randi"
//       [1]=>
//       int(1)
//     }
//     [1]=>
//     array(2) {
//       [0]=>
//       string(4) "Ando"
//       [1]=>
//       int(2)
//     }
//     [2]=>
//     array(2) {
//       [0]=>
//       string(4) "Danu"
//       [1]=>
//       int(3)
//     }
//     [3]=>
//     array(2) {
//       [0]=>
//       string(10) "Rani Karni"
//       [1]=>
//       int(4)
//     }
//     [4]=>
//     array(2) {
//       [0]=>
//       string(6) "Rumana"
//       [1]=>
//       int(5)
//     }
//   }
// }
```

### Fetch a Result

Fetches a result row as an associative or numerically indexed array or both like [SQLite3](https://www.php.net/manual/en/sqlite3result.fetcharray.php). default is `LIBSQLPHP_BOTH`, other options is: `LIBSQLPHP_ASSOC` or `LIBSQLPHP_NUM`

#### Fetch Default
```php
echo "Return as default (LIBSQLPHP_BOTH):" . PHP_EOL;
$users = $result->fetchArray();

var_dump($users);
// array(5) {
//   [0]=>
//   array(4) {
//     ["id"]=>
//     int(1)
//     [0]=>
//     int(1)
//     ["name"]=>
//     string(5) "Randi"
//     [1]=>
//     string(5) "Randi"
//   }
//   [1]=>
//   array(4) {
//     ["id"]=>
//     int(2)
//     [0]=>
//     int(2)
//     ["name"]=>
//     string(4) "Ando"
//     [1]=>
//     string(4) "Ando"
//   }
//   [2]=>
//   array(4) {
//     ["id"]=>
//     int(3)
//     [0]=>
//     int(3)
//     ["name"]=>
//     string(4) "Danu"
//     [1]=>
//     string(4) "Danu"
//   }
//   [3]=>
//   array(4) {
//     ["id"]=>
//     int(4)
//     [0]=>
//     int(4)
//     ["name"]=>
//     string(10) "Rani Karni"
//     [1]=>
//     string(10) "Rani Karni"
//   }
//   [4]=>
//   array(4) {
//     ["id"]=>
//     int(5)
//     [0]=>
//     int(5)
//     ["name"]=>
//     string(6) "Rumana"
//     [1]=>
//     string(6) "Rumana"
//   }
// }
```

#### Fetch Assoc
```php
echo "Return as default (LIBSQLPHP_ASSOC):" . PHP_EOL;
$users = $result->fetchArray(LIBSQLPHP_ASSOC);

var_dump($users);
// array(5) {
//   [0]=>
//   array(2) {
//     ["id"]=>
//     int(1)
//     ["name"]=>
//     string(5) "Randi"
//   }
//   [1]=>
//   array(2) {
//     ["id"]=>
//     int(2)
//     ["name"]=>
//     string(4) "Ando"
//   }
//   [2]=>
//   array(2) {
//     ["id"]=>
//     int(3)
//     ["name"]=>
//     string(4) "Danu"
//   }
//   [3]=>
//   array(2) {
//     ["id"]=>
//     int(4)
//     ["name"]=>
//     string(10) "Rani Karni"
//   }
//   [4]=>
//   array(2) {
//     ["id"]=>
//     int(5)
//     ["name"]=>
//     string(6) "Rumana"
//   }
// }
```

#### Fetch Num
```php
echo "Return as default (LIBSQLPHP_NUM):" . PHP_EOL;
$users = $result->fetchArray(LIBSQLPHP_NUM);

var_dump($users);
// array(5) {
//   [0]=>
//   array(2) {
//     [0]=>
//     string(5) "Randi"
//     [1]=>
//     int(1)
//   }
//   [1]=>
//   array(2) {
//     [0]=>
//     string(4) "Ando"
//     [1]=>
//     int(2)
//   }
//   [2]=>
//   array(2) {
//     [0]=>
//     string(4) "Danu"
//     [1]=>
//     int(3)
//   }
//   [3]=>
//   array(2) {
//     [0]=>
//     string(10) "Rani Karni"
//     [1]=>
//     int(4)
//   }
//   [4]=>
//   array(2) {
//     [0]=>
//     string(6) "Rumana"
//     [1]=>
//     int(5)
//   }
// }
```

### Query Single

```php
$result = $db->querySingle("SELECT name FROM users WHERE id = 1");
$result2 = $db->querySingle("SELECT name FROM users WHERE id = 2", true);
var_dump($result);
// string(5) "Randi"
var_dump($result2);
// array(1) {
//   ["name"]=>
//   string(4) "Ando"
// }
```

### Get Total Columns

```php
echo "Return the column count:" . PHP_EOL;
var_dump($result->numColumns());
```

### Get The Column Names
```php
echo "Return the column names:" . PHP_EOL;
var_dump($result->columName());
```

### Get The Column Types
```php
echo "Return the column types:" . PHP_EOL;
var_dump($result->columnType());
```

### Parameters Bindings

#### `bindParam`
```php
$stmt = $db->prepare("INSERT INTO persons (name, age) VALUES (:name, @age)");

// Bind parameters
$stmt->bindParam(':name', $baz, LIBSQLPHP_TEXT);
$stmt->bindParam('@age', $foo, LIBSQLPHP_INTEGER);
$baz = "Sarah";
$foo = 22;
$stmt->execute();
```

#### `bindValue`
```php
$stmt = $db->prepare('INSERT INTO foo VALUES (?)');

$age = 18;
$stmt->bindValue(1, $age, LIBSQLPHP_INTEGER);
$stmt->execute();
```

**What Prepare Query Have?**

The `prepare` query give a result of `LibSQLPHPStmt` object that contains other method:
- `bindParam` - Bind a PHP variable to a parameter in the prepared statement.
- `bindValue` - Bind a value to a parameter in the prepared statement.
- `execute` - Execute the prepared statement with bound parameters.
- `getSQL` - Get the SQL query string with parameter values replaced.
- `paramCount` - Get the number of parameters in the prepared statement.
- `readOnly` - Check if the prepared statement is read-only.
- `reset` - Reset the prepared statement, clearing bound parameters.
- `clear` - Clear the values of bound parameters in the prepared statement.
- `close` - Close the prepared statement, freeing resources.

### Transaction

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

---

If this library is useful and wants to support what I do. Please say a prayer to the God you believe in to always give you and me health and blessings in life, or you can become my GitHub Sponsor.

```
Regard,

.darkterminal 
(Software Freestyle Engineer - 🇮🇩)
```
