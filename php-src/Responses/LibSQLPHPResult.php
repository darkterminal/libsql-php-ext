<?php

namespace Darkterminal\LibSQLPHPExtension\Responses;

use FFI;

/**
 * Represents the result set obtained from executing a query using LibSQLPHP.
 */
class LibSQLPHPResult
{
    /**
     * Constructor.
     *
     * @param FFI $ffi The FFI instance.
     * @param mixed $db The database connection handle.
     * @param array $data The raw result data obtained from LibSQLPHP.
     */
    public function __construct(
        protected FFI $ffi,
        protected $db,
        protected array $data
    ) {
        $this->data = $this->_raw_converter($data);
    }

    /**
     * Fetch the result set as an array.
     *
     * @param int $mode The fetching mode (optional).
     *
     * @return array The fetched result set.
     */
    public function fetchArray(int $mode = LIBSQLPHP_BOTH): array
    {
        $result = $this->_results();

        if ($mode === LIBSQLPHP_NUM) {
            return $this->_libsqlphp_fetch_num($this->fetchRaw());
        }

        if ($mode === LIBSQLPHP_BOTH) {
            return $this->_libsqlphp_fetch_both($this->fetchRaw());
        }

        return $result;
    }

    /**
     * Get the number of columns in the result set.
     *
     * @return int The number of columns.
     */
    public function numColumns(): int
    {
        return count($this->data['columns']);
    }

    /**
     * Get the name of a column by index.
     *
     * @param int|null $index The index of the column (optional).
     *
     * @return array|string|false The name of the column, or an array of all column names, or false if the index is out of range.
     */
    public function columName(int|null $index = null): array|string|false
    {
        $columns = array_keys($this->data['columns']);

        if (is_null($index)) {
            return $columns;
        }

        if (isset($columns[$index])) {
            return $columns[$index];
        }

        return false;
    }

    /**
     * Get the type of a column by name or index.
     *
     * @param int|string|null $column The name or index of the column (optional).
     *
     * @return array|string|false The type of the column, or an array of all column types, or false if the column does not exist.
     */
    public function columnType(int|string|null $column = null): array|string|false
    {

        if (is_null($column)) {
            return array_values($this->data['columns']);
        }

        if (isset($this->data['columns'][$column])) {
            return $this->data['columns'][$column];
        }

        return false;
    }

    /**
     * Get the raw result data.
     *
     * @return array The raw result data.
     */
    public function fetchRaw(): array
    {
        return $this->data;
    }

    /**
     * Finalize the result set and free resources.
     *
     * @return bool Always true.
     */
    public function finalize(): bool
    {
        unset($this->data);
        return true;
    }

    /**
     * Reset the result set for re-execution.
     *
     * @return bool True if reset was successful, false otherwise.
     */
    public function reset(): bool
    {
        if (!empty($this->db)) {
            $this->ffi->libsql_php_reset($this->db);
            return true;
        }
        return false;
    }

    /**
     * Convert raw result data into a structured format.
     *
     * @param array $data The raw result data to convert.
     *
     * @return array The converted result data containing columns and rows.
     */
    private function _raw_converter(array $data): array
    {
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

    /**
     * Convert the raw result data into a structured array with column names as keys.
     *
     * @return array The structured result data.
     */
    private function _results(): array
    {
        $columns = array_keys($this->data['columns']);
        $result = array_map(function ($row) use ($columns) {
            return array_combine($columns, $row);
        }, $this->data['rows']);

        return $result;
    }

    /**
     * Fetch the result data as a numerically indexed array.
     *
     * @param array $data The raw result data.
     *
     * @return array The result data with numerically indexed rows.
     */
    private function _libsqlphp_fetch_num(array $data): array
    {
        $result = array_map(function ($row) {
            return array_values($row);
        }, $data['rows']);

        return $result;
    }

    /**
     * Fetch the result data as an array with both numerically indexed and associative keys.
     *
     * @param array $data The raw result data.
     *
     * @return array The result data with both numerically indexed and associative keys.
     */
    private function _libsqlphp_fetch_both(array $data): array
    {
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
