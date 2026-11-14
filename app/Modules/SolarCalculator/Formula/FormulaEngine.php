<?php

namespace App\Modules\SolarCalculator\Formula;

/**
 * Evaluates a set of named formulas against a set of input variables.
 *
 * A formula may reference inputs and any other formula; each formula is
 * evaluated once, in dependency order, and a dependency cycle or a
 * reference to an unknown name fails with a FormulaException naming the
 * formula at fault. Formula keys may not shadow an input name.
 */
final class FormulaEngine
{
    /** @var array<string, Expression> */
    private array $compiled = [];

    /**
     * @param array<string, string> $formulas key => expression source
     */
    public function __construct(array $formulas)
    {
        foreach ($formulas as $key => $source) {
            try {
                $this->compiled[$key] = Expression::parse((string) $source);
            } catch (FormulaException $e) {
                throw $e->withKey($key);
            }
        }
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->compiled);
    }

    /**
     * Evaluates every formula.
     *
     * @param array<string, float|int|bool> $inputs
     * @return array<string, float> formula key => value
     */
    public function evaluate(array $inputs): array
    {
        $inputs = array_map(static fn ($v) => (float) $v, $inputs);
        foreach (array_keys($this->compiled) as $key) {
            if (array_key_exists($key, $inputs)) {
                throw new FormulaException('نام فرمول با یکی از ورودی‌های ماشین‌حساب یکسان است.', $key);
            }
        }

        $values = [];
        $state = []; // key => 1 visiting, 2 done

        $resolve = function (string $key) use (&$resolve, &$values, &$state, $inputs): float {
            if (array_key_exists($key, $inputs)) {
                return $inputs[$key];
            }
            if (!isset($this->compiled[$key])) {
                throw new FormulaException("متغیر «{$key}» تعریف نشده است.");
            }
            if (($state[$key] ?? 0) === 2) {
                return $values[$key];
            }
            if (($state[$key] ?? 0) === 1) {
                throw new FormulaException('وابستگی چرخه‌ای بین فرمول‌ها.', $key);
            }

            $state[$key] = 1;
            try {
                $values[$key] = $this->compiled[$key]->evaluate($resolve);
            } catch (FormulaException $e) {
                throw $e->withKey($key);
            }
            $state[$key] = 2;

            return $values[$key];
        };

        foreach (array_keys($this->compiled) as $key) {
            $resolve($key);
        }

        return $values;
    }

    /**
     * Every non-formula name `$key` reads, directly or through other
     * formulas (e.g. which `rate_*` inputs the total price depends on).
     *
     * @return list<string>
     */
    public function reachableInputs(string $key): array
    {
        $inputs = [];
        $seen = [];
        $walk = function (string $k) use (&$walk, &$inputs, &$seen): void {
            if (isset($seen[$k])) {
                return;
            }
            $seen[$k] = true;
            if (!isset($this->compiled[$k])) {
                $inputs[$k] = true;
                return;
            }
            foreach ($this->compiled[$k]->variables() as $dep) {
                $walk($dep);
            }
        };
        $walk($key);
        unset($inputs[$key]);

        return array_keys($inputs);
    }

    /**
     * Static checks that need no input values: every referenced name exists
     * and there is no cycle. Returns a list of Persian error messages.
     *
     * @param list<string> $inputNames
     * @return list<string>
     */
    public function lint(array $inputNames): array
    {
        $errors = [];
        $known = array_flip($inputNames);
        foreach ($this->compiled as $key => $expr) {
            if (isset($known[$key])) {
                $errors[] = "«{$key}»: نام فرمول با یکی از ورودی‌های ماشین‌حساب یکسان است.";
            }
            foreach ($expr->variables() as $name) {
                if (!isset($known[$name]) && !isset($this->compiled[$name])) {
                    $errors[] = "«{$key}»: متغیر «{$name}» تعریف نشده است.";
                }
            }
        }

        $state = [];
        $visit = function (string $key, array $path) use (&$visit, &$state, &$errors, $known): void {
            if (($state[$key] ?? 0) === 2 || isset($known[$key]) || !isset($this->compiled[$key])) {
                return;
            }
            if (($state[$key] ?? 0) === 1) {
                $cycle = array_slice($path, (int) array_search($key, $path, true));
                $errors[] = 'وابستگی چرخه‌ای: ' . implode(' ← ', array_merge($cycle, [$key]));
                return;
            }
            $state[$key] = 1;
            foreach ($this->compiled[$key]->variables() as $dep) {
                $visit($dep, array_merge($path, [$key]));
            }
            $state[$key] = 2;
        };
        foreach (array_keys($this->compiled) as $key) {
            $visit($key, []);
        }

        return array_values(array_unique($errors));
    }
}
