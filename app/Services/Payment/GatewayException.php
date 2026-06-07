<?php

namespace App\Services\Payment;

use RuntimeException;

class GatewayException extends RuntimeException
{
    public function __construct(string $message, public readonly int $gatewayCode = 0)
    {
        parent::__construct($message);
    }
}
