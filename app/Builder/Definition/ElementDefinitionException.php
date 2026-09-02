<?php

namespace App\Builder\Definition;

use RuntimeException;

/**
 * Phase 3E-D — thrown while building a ThemeBuilderElementDefinition or
 * ThemeBuilderControlDefinition from malformed input. This is a boot-time/
 * definition-construction failure only — it is never thrown while
 * validating a persisted block's props (ElementValidator's existing
 * never-throws contract is untouched), and never thrown during Theme
 * reconciliation (definitions are not consulted there at all).
 */
final class ElementDefinitionException extends RuntimeException
{
    public static function unknownControlType(string $key, string $controlType): self
    {
        return new self(
            "Control \"{$key}\" declares unknown control type \"{$controlType}\". "
            . 'Only the V1 control vocabulary is supported.'
        );
    }

    public static function duplicateControlKey(string $key): self
    {
        return new self("Duplicate control key \"{$key}\" — every control key must be unique within one element definition.");
    }

    public static function invalidSelectOptions(string $key): self
    {
        return new self("Control \"{$key}\" is a select control but declares no non-empty \"options\" list.");
    }
}
