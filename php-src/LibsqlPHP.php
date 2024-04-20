<?php

namespace Darkterminal\LibsqlPHP;

use Darkterminal\LibsqlPHP\Contracts\SQLite3Interface;
use Darkterminal\LibsqlPHP\Responses\LibsqlPHPResult;

class LibsqlPHP implements SQLite3Interface
{
    public \FFI $ffi;
    protected int $rows_affected = 0;
    public $db;
    public bool $is_connected;

    public function __construct(string $path) {
        $this->ffi = \FFI::cdef(
            file_get_contents(__DIR__ . '/libsql_php.def'),
            __DIR__ . '/../libs/libsql_php_client.so'
        );

        $this->open($path);
    }

    public function open(string $path): void {
        $this->db = $this->ffi->libsql_php_open_file($path);
        $this->is_connected = ($this->db) ? true : false;
    }

    public function is_connected(): bool {
        return $this->is_connected;
    }

    public function close(): void {
        if ($this->db) {
            $this->ffi->libsql_php_close($this->db);
        }
    }

    public function query(string $stmt): LibsqlPHPResult {
        $data = $this->ffi->libsql_php_query($this->db, $stmt);
        $result = \FFI::new("char[4]");
        if ($data !== null) {
            $result = json_decode($data, true);
        }
        
        if ($result instanceof \FFI\CData) {
            $result = [];
        }

        $this->rows_affected = $result['rows_affected'];

        return new LibsqlPHPResult($result);
    }

    public function changes() : int {
        return $this->rows_affected;
    }

    public function exec(string $stmt): bool {
        $exec = $this->ffi->libsql_php_exec($this->db, $stmt);
        return $exec[0] === 0 ? true : false;
    }

    public function sync(): int {
        $exec = $this->ffi->libsql_php_sync($this->db);
        return $exec[0] === 0 ? true : false;
    }
}
