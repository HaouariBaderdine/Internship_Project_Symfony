<?php

namespace App\Exception;

use RuntimeException;
use Throwable;


class ApiRequestException extends RuntimeException
{
    private $apiErrorCode;

    public function __construct(string $message, int $apiErrorCode = 0, Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->apiErrorCode = $apiErrorCode;
    }

    public function getApiErrorCode(): int
    {
        return $this->apiErrorCode;
    }
}