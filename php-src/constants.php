<?php

/**
 * Indicates fetching an associative array.
 */
define('LIBSQLPHP_ASSOC', 1);

/**
 * Indicates fetching a numerically indexed array.
 */
define('LIBSQLPHP_NUM', 2);

/**
 * Indicates fetching both associative and numerically indexed arrays.
 */
define('LIBSQLPHP_BOTH', 3);

/**
 * Represents an integer type.
 */
define('LIBSQLPHP_INTEGER', 1);

/**
 * Represents a float type.
 */
define('LIBSQLPHP_FLOAT', 2);

/**
 * Represents a text type.
 */
define('LIBSQLPHP_TEXT', 3);

/**
 * Represents a BLOB (Binary Large Object) type.
 */
define('LIBSQLPHP_BLOB', 4);

/**
 * Represents a NULL value type.
 */
define('LIBSQLPHP_NULL', 5);

/**
 * Specifies opening the database in read-only mode.
 */
define('LIBSQLPHP_OPEN_READONLY', 1);

/**
 * Specifies opening the database in read-write mode.
 */
define('LIBSQLPHP_OPEN_READWRITE', 2);

/**
 * Specifies creating the database if it does not exist.
 */
define('LIBSQLPHP_OPEN_CREATE', 4);

/**
 * Specifies opening the database without any mutex.
 */
define('LIBSQLPHP_OPEN_NOMUTEX', 8);

/**
 * Specifies opening the database with full mutex.
 */
define('LIBSQLPHP_OPEN_FULLMUTEX', 16);

/**
 * Specifies using a shared cache for the database.
 */
define('LIBSQLPHP_OPEN_SHAREDCACHE', 0x00020000);

/**
 * Specifies using a private cache for the database.
 */
define('LIBSQLPHP_OPEN_PRIVATECACHE', 0x00040000);

/**
 * Specifies that the function is deterministic.
 */
define('LIBSQLPHP_DETERMINISTIC', 2048);
