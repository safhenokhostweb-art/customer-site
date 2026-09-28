<?php

namespace App\Services\ThemeCanvas\DataBinding;

interface ThemeDataSource
{
    public function source(): string;

    /** @param array<string,mixed> $query @return array<string,mixed> */
    public function resolve(array $query): array;
}
