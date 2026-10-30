<?php

namespace HolartWeb\AxoraCMS\Services\Integrations\Cdek;

use RuntimeException;

/**
 * An error of the CDEK API, with a message safe to show in the admin panel.
 */
class CdekException extends RuntimeException
{
    /**
     * @param  bool  $retryable  a network or server failure, worth trying again later
     */
    public function __construct(string $message, public readonly bool $retryable = false)
    {
        parent::__construct($message);
    }
}
