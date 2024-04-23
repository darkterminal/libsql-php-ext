# LibSQL Client PHP

This is experimental - work in progress

## How To Tryit?

**DISCLAIMER: I only tested using Linux**

I will pack this code as Composer Package when is ready.

Requirements:
- Rust Installed
- PHP Installed
- FFI Extension is Enabled

1. Clone this repo and `cd path/to/repo`
2. run `cargo instlal`
3. after that run `./build.sh`
4. test the PHP file, run `php test.php`

```php
<?php

use Darkterminal\LibsqlPHP\LibsqlPHP;

require_once 'vendor/autoload.php';

$db = new LibsqlPHP("file:database.db");
if ($db->is_connected()) {

    $db->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");
    $db->exec("INSERT INTO users (name) VALUES ('Tokio')");
    $db->exec("INSERT INTO users (name) VALUES ('Serde')");
    $db->exec("INSERT INTO users (name) VALUES ('Elpha')");

    $result = $db->query("SELECT * FROM users");
    
    echo "Return as raw:" . PHP_EOL;
    var_dump($result->fetchRaw());
    
    echo "Return as associative array:" . PHP_EOL;
    var_dump($result->fetchArray(SQLITE3_NUM));

    echo "Return the column count:" . PHP_EOL;
    var_dump($result->numColumns());
    
    echo "Return the column names:" . PHP_EOL;
    var_dump($result->columName());
    
    echo "Return the column types:" . PHP_EOL;
    var_dump($result->columnType());
}

```
