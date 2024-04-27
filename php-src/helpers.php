<?php

/**
 * Check if an array is associative.
 *
 * @param array $data The array to check.
 * @return bool True if the array is associative, false otherwise.
 */
function is_array_assoc(array $data)
{
    if (empty($data) || !is_array($data)) {
        return false;
    }

    if (array_keys($data) !== range(0, count($data) - 1)) {
        return true;
    }

    return false;
}

/**
 * Replace named parameters with their corresponding values in a given SQL statement.
 *
 * @param string $stmt The SQL statement containing named parameters.
 * @param array $named_params An associative array of named parameters and their values.
 * @return string The SQL statement with named parameters replaced by their values.
 */
function intoParams($stmt, $named_params) {
    foreach ($named_params as $key => $value) {
        if (is_string($value) || is_resource($value)) {
            $value = "'" . $value . "'";
        }
        $placeholders = [':' . $key, '@' . $key, '$' . $key];
        $stmt = str_replace($placeholders, $value, $stmt);
    }
    return $stmt;
}
