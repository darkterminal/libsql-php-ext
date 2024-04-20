<?php

namespace Darkterminal\LibsqlPHP\Contracts;

use Darkterminal\LibsqlPHP\Responses\LibsqlPHPResult;

interface SQLite3Interface
{
    public function open(string $path): void;
    public function close(): void;
    public function query(string $stmt): LibsqlPHPResult;
    public function exec(string $stmt): bool;
    public function changes(): int;
}