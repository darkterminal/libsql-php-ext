<?php

namespace Darkterminal\LibsqlPHP\Responses;

class LibsqlPHPResult
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function fetchArray(int $mode = SQLITE3_BOTH): array
    {
        $result = $this->_results($this->data);

        if ($mode === SQLITE3_NUM) {
            return $this->_sqlite3_fetch_num($result);
        }

        if ($mode === SQLITE3_BOTH) {
            return $this->_sqlite3_fetch_both($result);
        }

        return $result;
    }

    public function numColumns(): int {
        return count($this->data['columns']);
    }

    public function columName(int|null $index = null): array|string|false {
        if (is_null($index)) {
            return $this->data['columns'];
        }

        if (isset($this->data['columns'][$index])) {
            return $this->data['columns'][$index];
        }

        return false;
    }

    public function columnType(int|string|null $column = null): array|string|false {

        $collections = [];

        foreach ($this->data['rows'] as $row) {
            $rowValues = [];
            foreach ($row['values'] as $value) {
                $rowValues[] = $value['type'];
            }
            
            $collections[] = array_combine($this->data['columns'], $rowValues);
        }

        $columnTypes = current($collections);

        if (is_null($column)) {
            return $columnTypes;
        }

        if (is_string($column) && array_key_exists($column, $columnTypes)) {
            return $columnTypes[$column];
        }

        if (is_integer($column)) {
            return array_values($columnTypes)[$column];
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

    private function _results(): array
    {
        $collections = [];

        foreach ($this->data['rows'] as $row) {
            $rowValues = [];
            foreach ($row['values'] as $value) {
                switch ($value['type']) {
                    case 'text':
                        $value = (string) $value['value'];
                        break;
                    case 'integer':
                        $value = (int) $value['value'];
                        break;
                    case 'float':
                        $value = (float) $value['value'];
                        break;
                    case 'null':
                        $value = null;
                        break;
                    case 'blob':
                        $value = $value;
                        break;
                    default:
                        $value = (string) $value['value'];
                        break;
                }
                $rowValues[] = $value;
            }
            
            $collections[] = array_combine($this->data['columns'], $rowValues);
        }

        return $collections;
    }

    private function _sqlite3_fetch_num(array $data): array {
        $nums = array();
    
        foreach ($data as $row) {
            $numsRow = array();
            foreach ($row as $columnName => $columnValue) {
                $columnNumber = array_search($columnName, array_keys($row));
                $numsRow[$columnNumber] = $columnValue;
            }
            $nums[] = $numsRow;
        }
    
        return $nums;
    }

    private function _sqlite3_fetch_both(array $data): array {
        $both = array();

        foreach ($data as $index => $row) {
            $newRow = array();
            foreach ($row as $columnName => $columnValue) {
                $newRow[$columnName] = $columnValue;
                $newRow[$index] = $columnValue;
            }
            $both[] = $newRow;
        }

        return $both;
    }
}
