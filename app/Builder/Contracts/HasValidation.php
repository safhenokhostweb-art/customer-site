<?php

namespace App\Builder\Contracts;

/**
 * Optional: element provides custom validation beyond schema.
 * Return array of error messages; empty array means valid.
 */
interface HasValidation
{
    /**
     * @return string[] List of validation error messages
     */
    public static function validate(array $props): array;
}
