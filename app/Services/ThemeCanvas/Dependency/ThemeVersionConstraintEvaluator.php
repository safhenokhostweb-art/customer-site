<?php

namespace App\Services\ThemeCanvas\Dependency;

use InvalidArgumentException;

/**
 * Phase 3E-A — the one canonical evaluator for the constraint grammar
 * saas-platform's ThemeReleaseService already accepts and validates at
 * write time (assertValidVersionConstraintSyntax()): space-separated AND
 * clauses, each `<op><version>` with op in {>=, <=, >, <, =, ^} and
 * version a dotted 1-3 segment number (e.g. "2", "2.0", "2.0.1"). This is
 * a second, independent implementation of the SAME grammar (customer-site
 * has no dependency on saas-platform's code) — never a second, divergent
 * syntax.
 */
final class ThemeVersionConstraintEvaluator
{
    private const CLAUSE_PATTERN = '/^(>=|<=|>|<|=|\^)(\d+(?:\.\d+){0,2})$/';

    public function isValidSyntax(string $constraint): bool
    {
        $trimmed = trim($constraint);
        if ($trimmed === '') {
            return false;
        }

        foreach (preg_split('/\s+/', $trimmed) as $clause) {
            if (!preg_match(self::CLAUSE_PATTERN, $clause)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws InvalidArgumentException if $constraint is not valid syntax —
     *   an invalid constraint never silently evaluates to true or false.
     */
    public function satisfies(string $version, string $constraint): bool
    {
        $trimmed = trim($constraint);
        if (!$this->isValidSyntax($trimmed)) {
            throw new InvalidArgumentException("Invalid version constraint syntax: \"{$constraint}\".");
        }

        $version = $this->normalize($version);

        foreach (preg_split('/\s+/', $trimmed) as $clause) {
            preg_match(self::CLAUSE_PATTERN, $clause, $m);
            [, $operator, $bound] = $m;
            $bound = $this->normalize($bound);

            if ($operator === '^') {
                // Caret ("compatible with"): >= the given version, < the
                // next major version — the standard semver "no breaking
                // changes" range, matching what saas-platform's grammar
                // comment documents this operator as meaning.
                $major = (int) explode('.', $bound)[0];
                if (version_compare($version, $bound, '<') || version_compare($version, ($major + 1) . '.0.0', '>=')) {
                    return false;
                }
                continue;
            }

            if (!version_compare($version, $bound, $operator)) {
                return false;
            }
        }

        return true;
    }

    /**
     * PHP's version_compare() does NOT treat "3.0" and "3.0.0" as equal —
     * it compares segment-by-segment and a genuinely missing trailing
     * segment makes the shorter string compare as lower, not equal
     * (confirmed: version_compare('3.0.0','3.0','=') is false). Both the
     * grammar's own constraint bounds (1-3 segments, "2" / "2.0" / "2.0.1"
     * are all valid) and installed versions from different dependency
     * types may have differing segment counts for the SAME semantic
     * version, so every version string is padded to exactly 3 numeric
     * segments before any comparison — never left to this quirk.
     */
    private function normalize(string $version): string
    {
        $parts = array_pad(explode('.', $version), 3, '0');
        return implode('.', array_slice($parts, 0, 3));
    }
}
