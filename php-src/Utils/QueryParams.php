<?php

namespace Darkterminal\LibSQLPHPExtension\Utils;

/**
 * Class QueryParams
 *
 * Represents a collection of query parameters.
 */
class QueryParams
{
    /**
     * The FFI instance.
     */
    protected \FFI $ffi;

    /**
     * The number of query parameters.
     */
    protected $query_params_len;

    /**
     * The FFI query parameters array.
     */
    protected $ffi_query_params;

    /**
     * QueryParams constructor.
     *
     * @param array $data The data array containing query parameters.
     */
    public function __construct($data)
    {
        if (empty($data)) {
            $this->query_params_len = 1;
            $this->ffi_query_params = \FFI::new("char*[{$this->query_params_len}]", false);
            $this->ffi_query_params[0] = \FFI::new("char[1]", false);
            $this->ffi_query_params[0][0] = "\0";
        } else {
            $this->query_params_len = count($data);
            $this->ffi_query_params = \FFI::new("char*[{$this->query_params_len}]", false);
            foreach ($data as $i => $param) {
                $param_len = strlen($param) + 1;
                $this->ffi_query_params[$i] = \FFI::new("char[{$param_len}]", false);
                $param_with_null = $param . "\0";
                \FFI::memcpy($this->ffi_query_params[$i], $param_with_null, $param_len);
            }
        }
    }

    /**
     * Gets the address of the FFI query parameters array.
     *
     * @return mixed The address of the FFI query parameters array.
     */
    public function getData()
    {
        return \FFI::addr($this->ffi_query_params[0]);
    }

    /**
     * Gets the length of the query parameters array.
     *
     * @return int The length of the query parameters array.
     */
    public function getLength()
    {
        return $this->query_params_len;
    }

    /**
     * Frees the memory allocated for the query parameters.
     *
     * @return void
     */
    public function freeParams()
    {
        for ($i = 0; $i < $this->query_params_len; $i++) {
            \FFI::free($this->ffi_query_params[$i]);
        }
        \FFI::free($this->ffi_query_params);
    }
}
