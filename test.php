<?php

use Darkterminal\LibSQLPHPExtension\LibSQLPHP;
use Darkterminal\LibSQLPHPExtension\Utils\TransactionBehavior;

require_once 'vendor/autoload.php';

$db = new LibSQLPHP("file:database.db");
if ($db->is_connected()) {

    echo $db->version() . PHP_EOL;

    // $db->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");
    // $db->exec("INSERT INTO users (name) VALUES ('Handoko')");
    // $db->exec("INSERT INTO users (name) VALUES ('Karlina')");

    // $db->exec("INSERT INTO users (name) VALUES (?)", ["Marlo"]);
    // var_dump($db->last_insert_rowid());

    // $result = $db->query('SELECT * FROM users');

    // echo "Return as raw:" . PHP_EOL;
    // var_dump($result->fetchRaw(LIBSQLPHP_ASSOC));

    // echo json_encode(["name" => "Merlin"]) . PHP_EOL;

    // echo "Return as default (LIBSQLPHP_NUM):" . PHP_EOL;
    // $users = $result->fetchArray(LIBSQLPHP_NUM);

    // var_dump($users);
    // foreach ($users as $user) {
    //     echo $user['name'] . PHP_EOL;
    // }

    // echo "Return the column count:" . PHP_EOL;
    // var_dump($result->numColumns());

    // echo "Return the column names:" . PHP_EOL;
    // var_dump($result->columName());

    // echo "Return the column types:" . PHP_EOL;
    // var_dump($result->columnType());

    // $stmt = $db->prepare("INSERT INTO persons (name, age) VALUES (:name, @age)");

    // // Bind parameters
    // $stmt->bindParam(':name', $baz, LIBSQLPHP_TEXT);
    // $stmt->bindParam('@age', $foo, LIBSQLPHP_INTEGER);
    // $baz = "Sarah";
    // $foo = 22;
    // $stmt->execute();

    // $stmt = $db->prepare("INSERT INTO foo VALUES (?, ?)");

    // // Bind parameters
    // $stmt->bindValue(1, "baz", LIBSQLPHP_TEXT);
    // $stmt->bindValue(2, 5, LIBSQLPHP_INTEGER);
    // echo $stmt->paramCount() . PHP_EOL;

    // $db->exec("DELETE FROM users WHERE id = 3");

    // $changes = $db->changes();
    // echo "The DELETE statement removed $changes rows";

    // $stmt = $db->prepare('INSERT INTO foo VALUES (?)');

    // $age = 18;
    // $stmt->bindValue(1, $age, LIBSQLPHP_INTEGER);

    // // Check if the statement is read-only
    // if ($stmt->readOnly()) {
    //     echo "The statement is read-only.\n";
    // } else {
    //     echo "The statement is not read-only.\n";
    // }

    // $result = $db->querySingle("SELECT name FROM users WHERE id = 1");
    // $result2 = $db->querySingle("SELECT name FROM users WHERE id = 2", true);
    // var_dump($result);
    // var_dump($result2);

    // $operations_successful = false;
    // $tx = $db->transaction(TransactionBehavior::Deferred);
    // $tx->exec("INSERT INTO users (name) VALUES (?)", ["Emanuel"]);
    // $tx->exec("INSERT INTO users (name) VALUES (?)", ["Darren"]);

    // if ($operations_successful) {
    //     $tx->commit();
    //     echo "Commit the changes" . PHP_EOL;
    // } else {
    //     $tx->rollback();
    //     echo "Rollback the changes" . PHP_EOL;
    // }

    // $db->execute_batch("
    //     BEGIN;
    //     CREATE TABLE foo(x INTEGER);
    //     CREATE TABLE bar(y TEXT);
    //     COMMIT;
    // ");
}
$db->close();
