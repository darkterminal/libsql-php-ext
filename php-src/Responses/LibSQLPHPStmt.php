<?php

namespace Darkterminal\LibSQLPHPExtension\Responses;

use Darkterminal\LibSQLPHPExtension\LibSQLPHP;
use Darkterminal\LibSQLPHPExtension\Utils\QueryParams;
use FFI;

/**
 * Represents a prepared statement for executing SQL queries with parameter binding.
 */
class LibSQLPHPStmt
{
    protected array $named_parameters = [];
    protected array $positonal_parameters = [];

    /**
     * Constructor.
     *
     * @param FFI $ffi The FFI instance.
     * @param $db The database connection handle.
     * @param string $query The SQL query string.
     */
    public function __construct(
        protected FFI $ffi,
        protected $db,
        protected string $query
    ) {
        $this->ffi = $ffi;
        $this->db = $db;
        $this->query = $query;
    }

    /**
     * Bind a PHP variable to a parameter in the prepared statement.
     *
     * @param string $param The parameter name or position.
     * @param mixed $variable The PHP variable to bind.
     * @param int $type The type of the parameter (optional).
     *
     * @return void
     *
     * @throws \Exception If named parameters are used with positional queries.
     */
    public function bindParam($param, &$variable, $type = LIBSQLPHP_TEXT)
    {
        if (!preg_match('/^[:@]/', $param)) {
            if (!preg_match('/^[:@]/', $this->query)) {
                $this->positonal_parameters[$param] = ['value' => &$variable, 'type' => $type];
            } else {
                throw new \Exception("Named parameters are not supported for positional queries");
            }
        } else {
            $this->named_parameters[$param] = ['value' => &$variable, 'type' => $type];
        }
    }

    /**
     * Bind a value to a parameter in the prepared statement.
     *
     * @param string $param The parameter name or position.
     * @param mixed $value The value to bind.
     * @param int $type The type of the parameter (optional).
     *
     * @return void
     *
     * @throws \Exception If named parameters are used with positional queries.
     */
    public function bindValue($param, $value, $type = LIBSQLPHP_TEXT)
    {
        if (!preg_match('/^[:@]/', $param)) {
            if (!preg_match('/^[:@]/', $this->query)) {
                $this->positonal_parameters[$param] = ['value' => $value, 'type' => $type];
            } else {
                throw new \Exception("Named parameters are not supported for positional queries");
            }
        } else {
            $this->named_parameters[$param] = ['value' => $value, 'type' => $type];
        }
    }

    /**
     * Execute the prepared statement with bound parameters.
     *
     * @return bool True if the execution was successful, false otherwise.
     */
    public function execute()
    {
        $query = $this->query;

        if (!empty($this->named_parameters)) {
            foreach ($this->named_parameters as $param => $paramData) {
                $value = $this->typed_value($paramData['value'], $paramData['type']);
                if ($paramData['type'] === LIBSQLPHP_TEXT || $paramData['type'] === LIBSQLPHP_BLOB) {
                    $value = "'" . LibSQLPHP::escapeString($value) . "'";
                }
                $query = str_replace($param, $value, $query);
            }
        }

        if (!empty($this->positonal_parameters)) {
            foreach ($this->positonal_parameters as $param => $paramData) {
                $value = $this->typed_value($paramData['value'], $paramData['type']);
                if ($paramData['type'] === LIBSQLPHP_TEXT || $paramData['type'] === LIBSQLPHP_BLOB) {
                    $value = "'" . LibSQLPHP::escapeString($value) . "'";
                }
                $query = preg_replace('/\?/', $value, $query, 1);
            }
        }

        $this->reset();

        $params = [];
        $queryParams = new QueryParams($params);
        $exec = $this->ffi->libsql_php_exec($this->db, $query, $queryParams->getData(), $queryParams->getLength());
        $queryParams->freeParams();
        return $exec[0] === 0;
    }

    /**
     * Get the SQL query string with parameter values replaced.
     *
     * @param bool $expand Whether to expand named parameters (optional).
     *
     * @return string|false The SQL query string with replaced parameter values, or false on failure.
     */
    public function getSQL($expand = false): string|false
    {
        $query = $this->query;

        if ($expand) {
            foreach ($this->named_parameters as $param => $paramData) {
                $value = $paramData['value'] ?? 'NULL';
                if ($paramData['type'] === LIBSQLPHP_TEXT || $paramData['type'] === LIBSQLPHP_BLOB) {
                    $value = "'" . LibSQLPHP::escapeString($value) . "'";
                }
                $query = str_replace($param, $value, $query);
            }

            foreach ($this->positonal_parameters as $param => $paramData) {
                $value = $paramData['value'] ?? 'NULL';
                if ($paramData['type'] === LIBSQLPHP_TEXT || $paramData['type'] === LIBSQLPHP_BLOB) {
                    $value = "'" . LibSQLPHP::escapeString($value) . "'";
                }
                $query = preg_replace('/\?/', $value, $query, 1);
            }
        }

        return $query;
    }

    /**
     * Get the number of parameters in the prepared statement.
     *
     * @return int The number of parameters.
     */
    public function paramCount(): int
    {
        $namedParamCount = count($this->named_parameters);
        $positionalParamCount = count($this->positonal_parameters);

        return $namedParamCount + $positionalParamCount;
    }

    /**
     * Check if the prepared statement is read-only.
     *
     * @return bool True if the statement is read-only, false otherwise.
     */
    public function readOnly(): bool
    {
        $writeOperations = ['INSERT', 'UPDATE', 'DELETE', 'REPLACE', 'CREATE', 'DROP', 'ALTER'];
        $query = $this->query;

        foreach ($writeOperations as $operation) {
            if (stripos($query, $operation) !== false) {
                return false;
            }
        }

        foreach ($this->named_parameters as $paramData) {
            if ($paramData['type'] === LIBSQLPHP_NULL) {
                return false;
            }
        }

        foreach ($this->positonal_parameters as $paramData) {
            if ($paramData['type'] === LIBSQLPHP_NULL) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reset the prepared statement, clearing bound parameters.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->named_parameters = [];
        $this->positonal_parameters = [];
    }

    /**
     * Clear the values of bound parameters in the prepared statement.
     *
     * @return void
     */
    public function clear(): void
    {
        foreach ($this->named_parameters as &$paramData) {
            $paramData['value'] = null;
        }
        foreach ($this->positonal_parameters as &$paramData) {
            $paramData['value'] = null;
        }
    }

    /**
     * Close the prepared statement, freeing resources.
     *
     * @return void
     */
    public function close(): void
    {
        unset($this->named_parameters);
        unset($this->positonal_parameters);
    }

    /**
     * Convert a value to the specified type.
     *
     * @param mixed $value The value to convert.
     * @param int $type The target type.
     *
     * @return mixed The converted value.
     */
    private function typed_value($value, $type): mixed
    {
        switch ($type) {
            case LIBSQLPHP_INTEGER:
                $value = intval($value);
                break;
            case LIBSQLPHP_FLOAT:
                $value = floatval($value);
                break;
            case LIBSQLPHP_TEXT:
                $value = (string) $value;
                break;
            case LIBSQLPHP_NULL:
                $value = null;
                break;
            case LIBSQLPHP_BLOB:
                $value;
                break;
        }

        return $value;
    }
}
