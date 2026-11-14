<?php

namespace App\Modules\SolarCalculator\Formula;

use RuntimeException;

/**
 * Every failure of the formula engine: a syntax error, an unknown
 * variable or function, a dependency cycle, a division by zero or a
 * non-finite result. The message is Persian and safe to show to the
 * site admin (it never contains PHP internals).
 */
class FormulaException extends RuntimeException
{
    public function __construct(string $message, public readonly ?string $formulaKey = null)
    {
        parent::__construct($formulaKey === null ? $message : "«{$formulaKey}»: {$message}");
    }

    public function withKey(string $key): self
    {
        return $this->formulaKey === null ? new self($this->getMessage(), $key) : $this;
    }
}
