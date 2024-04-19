<?php
declare(strict_types = 1);

class LibsqlPHP 
{
    public FFI $ffi;
    public $db;
    public bool $is_connected;

    public function __construct(string $path) {
        $this->ffi = \FFI::cdef(
            file_get_contents(__DIR__ . '/libsql_php.def'),
            __DIR__ . '/libs/libsql_php_client.so'
        );

        $this->open($path);
    }

    public function open(string $path): void {
        $this->db = $this->ffi->libsql_php_connect($path);
        $this->is_connected = ($this->db) ? true : false;
    }

    public function is_connected(): bool {
        return $this->is_connected;
    }

    public function query(string $stmt): array {
        $data = $this->ffi->libsql_php_query($this->db, $stmt);
        $result = FFI::new("char[4]");
        if ($data !== null) {
            $result = json_decode($data, true);
        }
        $this->ffi->free($this->db);
        
        if ($result instanceof FFI\CData) {
            $result = [];
        }

        return $result;
    }

    public function exec(string $stmt): bool {
        $exec = $this->ffi->libsql_php_exec($this->db, $stmt);
        $this->ffi->free($this->db);
        return (bool) $exec[0];
    }
}

$db = new LibsqlPHP("./database.db");
var_dump($db->is_connected());
