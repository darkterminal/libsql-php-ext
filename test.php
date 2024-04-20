<?php

use Darkterminal\LibsqlPHP\LibsqlPHP;

require_once 'vendor/autoload.php';

$db = new LibsqlPHP("database.db");
if ($db->is_connected()) {
    $result = $db->query("SELECT * FROM users");
    
    // echo "Return as raw:" . PHP_EOL;
    // var_dump($result->fetchRaw());
    
    echo "Return as associative array:" . PHP_EOL;
    var_dump($result->fetchArray(SQLITE3_ASSOC));
    
    // echo "Return the column names:" . PHP_EOL;
    // var_dump($result->columName());
    
    // echo "Return the column types:" . PHP_EOL;
    // var_dump($result->columnType());
}