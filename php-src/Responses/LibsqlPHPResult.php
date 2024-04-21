<?php

namespace Darkterminal\LibsqlPHP\Responses;

class LibsqlPHPResult
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $this->_raw_converter($data);
    }

    public function fetchArray(int $mode = SQLITE3_BOTH): array
    {
        $result = $this->_results();

        if ($mode === SQLITE3_NUM) {
            return $this->_sqlite3_fetch_num($this->fetchRaw());
        }

        if ($mode === SQLITE3_BOTH) {
            return $this->_sqlite3_fetch_both($this->fetchRaw());
        }

        return $result;
    }

    public function numColumns(): int {
        return count($this->data['columns']);
    }

    public function columName(int|null $index = null): array|string|false {
        $columns = array_keys($this->data['columns']);

        if (is_null($index)) {
            return $columns;
        }

        if (isset($columns[$index])) {
            return $columns[$index];
        }

        return false;
    }

    public function columnType(int|string|null $column = null): array|string|false {
        
        if (is_null($column)) {
            return array_values($this->data['columns']);
        }

        if (isset($this->data['columns'][$column])) {
            return $this->data['columns'][$column];
        }

        return false;
    }

    public function fetchRaw(): array
    {
        return $this->data;
    }

    public function finalize(): bool {
        unset($this->data);
        return true;
    }

    public function reset(): bool {
        if (!empty($this->data)) {
            $this->data = [];
            $this->data = $this->data;
            return true;
        }
        return false;
    }

    private function _raw_converter(array $data): array {
        usort($data, function($a, $b) {
            return $a['id']['Integer'] - $b['id']['Integer'];
        });

        $result = [
            "columns" => [],
            "rows" => [],
        ];

        foreach ($data as $item) {
            foreach ($item as $key => $value) {
                if (!isset($result['columns'][$key])) {
                    $result['columns'][$key] = key($value);
                }
            }
            $row = [];
            foreach ($result['columns'] as $column => $type) {
                $row[] = current($item[$column]);
            }
            $result['rows'][] = $row;
        }
        return $result;
    }

    private function _results(): array
    {
        $columns = array_keys($this->data['columns']);
        $result = array_map(function($row) use ($columns) {
            return array_combine($columns, $row);
        }, $this->data['rows']);

        return $result;
    }

    private function _sqlite3_fetch_num(array $data): array {
        $result = array_map(function($row) {
            return array_values($row); // Index the row by column number
        }, $data['rows']);

        return $result;
    }

    private function _sqlite3_fetch_both(array $data): array {
        $columns = array_keys($data['columns']);
        $result = [];

        foreach ($data['rows'] as $rowIndex => $row) {
            $rowArray = [];
            
            foreach ($columns as $colIndex => $columnName) {
                $rowArray[$columnName] = $row[$colIndex];
                $rowArray[$colIndex] = $row[$colIndex];
            }
            
            $result[$rowIndex] = $rowArray;
        }

        return $result;
    }
}
