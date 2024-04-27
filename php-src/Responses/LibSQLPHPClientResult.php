<?php

namespace Darkterminal\LibSQLPHPExtension\Responses;

/**
 * Represents the result of a client query in LibSQLPHP.
 *
 * This class encapsulates the result of a client query, including column names, column types,
 * rows of data, the number of rows affected, and the last insert row ID.
 *
 */
class LibSQLPHPClientResult
{
    /**
     * The columns returned in the query result.
     * 
     * @var array
     */
    public array $columns;

    /**
     * The types of columns returned in the query result.
     * 
     * @var array
     */
    public array $columnTypes;

    /**
     * The rows of data returned in the query result.
     * 
     * @var array
     */
    public array $rows;

    /**
     * The number of rows affected by the query.
     * 
     * @var int
     */
    public int $rowsAffected;

    /**
     * The ID of the last inserted row.
     * 
     * @var int
     */
    public int $lastInsertRowid;

    /**
     * The data of the query result represented as an array.
     * 
     * @var array
     */
    protected array $data;

    /**
     * Constructs a new LibSQLPHPClientResult instance.
     *
     * @param array $columns The columns returned in the query result.
     * @param array $columnTypes The types of columns returned in the query result.
     * @param array $rows The rows of data returned in the query result.
     * @param int $rowsAffected The number of rows affected by the query.
     * @param int $lastInsertRowid The ID of the last inserted row.
     */
    public function __construct(
        array $columns,
        array $columnTypes,
        array $rows,
        int $rowsAffected,
        int $lastInsertRowid
    ) {
        $this->columns = $columns;
        $this->columnTypes = $columnTypes;
        $this->rows = $rows;
        $this->rowsAffected = $rowsAffected;
        $this->lastInsertRowid = $lastInsertRowid;
        $this->data = [
            'columns' => $this->columns,
            'columnTypes' => $this->columnTypes,
            'rows' => $this->rows,
            'rowsAffected' => $this->rowsAffected,
            'lastInsertRowid' => $this->lastInsertRowid
        ];
    }

    /**
     * Creates a new instance of LibSQLPHPClientResult.
     *
     * @param array $columns The columns returned in the query result.
     * @param array $columnTypes The types of columns returned in the query result.
     * @param array $rows The rows of data returned in the query result.
     * @param int $rowsAffected The number of rows affected by the query.
     * @param int $lastInsertRowid The ID of the last inserted row.
     * @return self The newly created instance.
     */
    public static function create(
        array $columns,
        array $columnTypes,
        array $rows,
        int $rowsAffected,
        int $lastInsertRowid
    ): self {
        return new self(
            $columns,
            $columnTypes,
            $rows,
            $rowsAffected,
            $lastInsertRowid
        );
    }

    /**
     * Converts the LibSQLPHPClientResult instance to a JSON string.
     *
     * @return string The JSON representation of the data.
     */
    public function toObject(): string
    {
        return json_encode($this->data);
    }
    
    /**
     * Converts the LibSQLPHPClientResult instance to an associative array.
     *
     * @return array The associative array representation of the data.
     */
    public function toArray(): array {
        return $this->data;
    }
}

