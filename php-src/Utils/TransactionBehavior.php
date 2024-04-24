<?php

namespace Darkterminal\LibSQLPHPExtension\Utils;

/**
 * Class TransactionBehavior
 *
 * Represents the behavior options for database transactions.
 */
class TransactionBehavior
{
    /**
     * The DEFERRED transaction behavior.
     */
    const Deferred = "DEFERRED";

    /**
     * The WRITE transaction behavior.
     */
    const Immediate = "WRITE";

    /**
     * The READ transaction behavior.
     */
    const ReadOnly = "READ";
}
