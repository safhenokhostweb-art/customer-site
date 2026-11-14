<?php

namespace App\Modules\SolarCalculator\Formula;

/**
 * A compiled, side-effect-free arithmetic expression.
 *
 * The admin edits formulas as text, so this is a small recursive-descent
 * parser: nothing is ever passed to eval(), no PHP function is reachable
 * except the whitelist in FUNCTIONS, and parsing is bounded in length and
 * depth.
 *
 * Grammar (lowest to highest precedence):
 *   or         := and (('or' | '||') and)*
 *   and        := not (('and' | '&&') not)*
 *   not        := ('not' | '!') not | comparison
 *   comparison := additive (('<' | '<=' | '>' | '>=' | '==' | '!=') additive)?
 *   additive   := term (('+' | '-') term)*
 *   term       := unary (('*' | '/' | '%') unary)*
 *   unary      := ('-' | '+') unary | power
 *   power      := primary ('^' unary)?            (right-associative)
 *   primary    := number | 'true' | 'false' | name | name '(' args ')' | '(' or ')'
 *
 * Booleans are numbers (true = 1, false = 0). `if(c, a, b)` is lazy, so
 * `if(x > 0, y / x, 0)` never divides by zero.
 */
final class Expression
{
    public const MAX_LENGTH = 2000;
    public const MAX_DEPTH = 64;

    /** name => [min args, max args (null = variadic)] */
    public const FUNCTIONS = [
        'if' => [3, 3],
        'min' => [1, null],
        'max' => [1, null],
        'abs' => [1, 1],
        'sqrt' => [1, 1],
        'round' => [1, 2],
        'ceil' => [1, 2],
        'floor' => [1, 2],
        'roundup' => [2, 2],
        'clamp' => [3, 3],
    ];

    private const RESERVED = ['and', 'or', 'not', 'true', 'false'];

    /** @var list<array{0:string,1:string|float}> */
    private array $tokens = [];
    private int $pos = 0;
    private int $depth = 0;

    /** @param array<mixed> $ast */
    private function __construct(private readonly string $source, private array $ast = [])
    {
    }

    public static function parse(string $source): self
    {
        $source = trim(self::normalizeDigits($source));
        if ($source === '') {
            throw new FormulaException('عبارت خالی است.');
        }
        if (mb_strlen($source) > self::MAX_LENGTH) {
            throw new FormulaException('عبارت بیش از حد طولانی است.');
        }

        $expr = new self($source);
        $expr->tokens = self::tokenize($source);
        $expr->ast = $expr->parseOr();
        if ($expr->peek() !== null) {
            throw new FormulaException('نماد غیرمنتظره «' . $expr->peek()[1] . '» در عبارت.');
        }
        $expr->tokens = [];

        return $expr;
    }

    public function source(): string
    {
        return $this->source;
    }

    /**
     * Every variable name the expression reads (functions excluded).
     *
     * @return list<string>
     */
    public function variables(): array
    {
        $names = [];
        $walk = function (array $node) use (&$walk, &$names): void {
            switch ($node[0]) {
                case 'v':
                    $names[$node[1]] = true;
                    break;
                case 'u':
                    $walk($node[2]);
                    break;
                case 'b':
                    $walk($node[2]);
                    $walk($node[3]);
                    break;
                case 'f':
                    foreach ($node[2] as $arg) {
                        $walk($arg);
                    }
                    break;
            }
        };
        $walk($this->ast);

        return array_keys($names);
    }

    /**
     * @param callable(string):float $resolve returns the value of a variable
     */
    public function evaluate(callable $resolve): float
    {
        $value = $this->evalNode($this->ast, $resolve);
        if (!is_finite($value)) {
            throw new FormulaException('نتیجهٔ محاسبه عدد معتبری نیست.');
        }

        return $value;
    }

    // ── Evaluation ───────────────────────────────────────────────────────

    /** @param array<mixed> $node */
    private function evalNode(array $node, callable $resolve): float
    {
        switch ($node[0]) {
            case 'n':
                return $node[1];
            case 'v':
                return (float) $resolve($node[1]);
            case 'u':
                $v = $this->evalNode($node[2], $resolve);
                return match ($node[1]) {
                    '-' => -$v,
                    '+' => $v,
                    'not' => $v == 0.0 ? 1.0 : 0.0,
                };
            case 'b':
                return $this->evalBinary($node[1], $node[2], $node[3], $resolve);
            case 'f':
                return $this->evalFunction($node[1], $node[2], $resolve);
        }

        throw new FormulaException('ساختار عبارت نامعتبر است.');
    }

    /** @param array<mixed> $l @param array<mixed> $r */
    private function evalBinary(string $op, array $l, array $r, callable $resolve): float
    {
        // Short-circuit logic.
        if ($op === 'and') {
            return $this->evalNode($l, $resolve) != 0.0 && $this->evalNode($r, $resolve) != 0.0 ? 1.0 : 0.0;
        }
        if ($op === 'or') {
            return $this->evalNode($l, $resolve) != 0.0 || $this->evalNode($r, $resolve) != 0.0 ? 1.0 : 0.0;
        }

        $a = $this->evalNode($l, $resolve);
        $b = $this->evalNode($r, $resolve);

        return match ($op) {
            '+' => $a + $b,
            '-' => $a - $b,
            '*' => $a * $b,
            '/' => $b == 0.0 ? throw new FormulaException('تقسیم بر صفر.') : $a / $b,
            '%' => $b == 0.0 ? throw new FormulaException('باقی‌مانده بر صفر.') : fmod($a, $b),
            '^' => $a ** $b,
            '<' => $a < $b ? 1.0 : 0.0,
            '<=' => $a <= $b ? 1.0 : 0.0,
            '>' => $a > $b ? 1.0 : 0.0,
            '>=' => $a >= $b ? 1.0 : 0.0,
            '==' => abs($a - $b) < 1e-9 ? 1.0 : 0.0,
            '!=' => abs($a - $b) >= 1e-9 ? 1.0 : 0.0,
        };
    }

    /** @param list<array<mixed>> $args */
    private function evalFunction(string $name, array $args, callable $resolve): float
    {
        if ($name === 'if') {
            return $this->evalNode($args[0], $resolve) != 0.0
                ? $this->evalNode($args[1], $resolve)
                : $this->evalNode($args[2], $resolve);
        }

        $v = array_map(fn (array $a) => $this->evalNode($a, $resolve), $args);

        return match ($name) {
            'min' => min($v),
            'max' => max($v),
            'abs' => abs($v[0]),
            'sqrt' => $v[0] < 0 ? throw new FormulaException('جذر عدد منفی.') : sqrt($v[0]),
            'round' => round($v[0], (int) ($v[1] ?? 0)),
            'ceil' => isset($v[1]) ? self::stepped($v[0], $v[1], 'ceil') : ceil($v[0]),
            'floor' => isset($v[1]) ? self::stepped($v[0], $v[1], 'floor') : floor($v[0]),
            'roundup' => self::stepped($v[0], $v[1], 'ceil'),
            'clamp' => max($v[1], min($v[2], $v[0])),
        };
    }

    /** ceil/floor to a multiple of $step (e.g. ceil(4.12, 0.5) = 4.5). */
    private static function stepped(float $value, float $step, string $fn): float
    {
        if ($step <= 0) {
            throw new FormulaException('گام گرد کردن باید بزرگ‌تر از صفر باشد.');
        }
        // The epsilon keeps 4.5 / 0.5 = 9.000000001 from rounding up to 9.5.
        $q = $value / $step;
        $q = $fn === 'ceil' ? ceil(round($q, 9)) : floor(round($q, 9));

        return round($q * $step, 10);
    }

    // ── Parsing ──────────────────────────────────────────────────────────

    /** @return list<array{0:string,1:string|float}> */
    private static function tokenize(string $s): array
    {
        $tokens = [];
        $len = strlen($s);
        $i = 0;
        while ($i < $len) {
            $c = $s[$i];
            if (ctype_space($c)) {
                $i++;
                continue;
            }
            if (ctype_digit($c) || ($c === '.' && $i + 1 < $len && ctype_digit($s[$i + 1]))) {
                if (!preg_match('/\G\d*\.?\d+(?:[eE][-+]?\d+)?/', $s, $m, 0, $i)) {
                    throw new FormulaException('عدد نامعتبر در عبارت.');
                }
                $tokens[] = ['num', (float) $m[0]];
                $i += strlen($m[0]);
                continue;
            }
            if (ctype_alpha($c) || $c === '_') {
                preg_match('/\G[A-Za-z_][A-Za-z0-9_]*/', $s, $m, 0, $i);
                $tokens[] = ['id', strtolower($m[0])];
                $i += strlen($m[0]);
                continue;
            }
            $two = substr($s, $i, 2);
            if (in_array($two, ['<=', '>=', '==', '!=', '&&', '||'], true)) {
                $tokens[] = ['op', match ($two) { '&&' => 'and', '||' => 'or', default => $two }];
                $i += 2;
                continue;
            }
            if (str_contains('+-*/%^()<>,!', $c)) {
                $tokens[] = ['op', $c === '!' ? 'not' : $c];
                $i++;
                continue;
            }
            $char = preg_match('/\G./su', $s, $m, 0, $i) ? $m[0] : '?';
            throw new FormulaException('نویسهٔ غیرمجاز «' . $char . '» در عبارت.');
        }

        return $tokens;
    }

    /** @return array{0:string,1:string|float}|null */
    private function peek(): ?array
    {
        return $this->tokens[$this->pos] ?? null;
    }

    private function acceptOp(string ...$ops): ?string
    {
        $t = $this->peek();
        if ($t === null) {
            return null;
        }
        $value = $t[0] === 'id' && in_array($t[1], ['and', 'or', 'not'], true) ? $t[1] : ($t[0] === 'op' ? $t[1] : null);
        if ($value !== null && in_array($value, $ops, true)) {
            $this->pos++;
            return $value;
        }

        return null;
    }

    private function expectOp(string $op): void
    {
        if ($this->acceptOp($op) === null) {
            throw new FormulaException("«{$op}» مورد انتظار بود.");
        }
    }

    private function enter(): void
    {
        if (++$this->depth > self::MAX_DEPTH) {
            throw new FormulaException('عبارت بیش از حد تودرتو است.');
        }
    }

    /** @return array<mixed> */
    private function parseOr(): array
    {
        $this->enter();
        $node = $this->parseAnd();
        while ($this->acceptOp('or') !== null) {
            $node = ['b', 'or', $node, $this->parseAnd()];
        }
        $this->depth--;

        return $node;
    }

    /** @return array<mixed> */
    private function parseAnd(): array
    {
        $node = $this->parseNot();
        while ($this->acceptOp('and') !== null) {
            $node = ['b', 'and', $node, $this->parseNot()];
        }

        return $node;
    }

    /** @return array<mixed> */
    private function parseNot(): array
    {
        if ($this->acceptOp('not') !== null) {
            $this->enter();
            $node = ['u', 'not', $this->parseNot()];
            $this->depth--;
            return $node;
        }

        return $this->parseComparison();
    }

    /** @return array<mixed> */
    private function parseComparison(): array
    {
        $node = $this->parseAdditive();
        $op = $this->acceptOp('<', '<=', '>', '>=', '==', '!=');
        if ($op !== null) {
            $node = ['b', $op, $node, $this->parseAdditive()];
        }

        return $node;
    }

    /** @return array<mixed> */
    private function parseAdditive(): array
    {
        $node = $this->parseTerm();
        while (($op = $this->acceptOp('+', '-')) !== null) {
            $node = ['b', $op, $node, $this->parseTerm()];
        }

        return $node;
    }

    /** @return array<mixed> */
    private function parseTerm(): array
    {
        $node = $this->parseUnary();
        while (($op = $this->acceptOp('*', '/', '%')) !== null) {
            $node = ['b', $op, $node, $this->parseUnary()];
        }

        return $node;
    }

    /** @return array<mixed> */
    private function parseUnary(): array
    {
        if (($op = $this->acceptOp('-', '+')) !== null) {
            $this->enter();
            $node = ['u', $op, $this->parseUnary()];
            $this->depth--;
            return $node;
        }

        return $this->parsePower();
    }

    /** @return array<mixed> */
    private function parsePower(): array
    {
        $base = $this->parsePrimary();
        if ($this->acceptOp('^') !== null) {
            $this->enter();
            $base = ['b', '^', $base, $this->parseUnary()];
            $this->depth--;
        }

        return $base;
    }

    /** @return array<mixed> */
    private function parsePrimary(): array
    {
        $t = $this->peek();
        if ($t === null) {
            throw new FormulaException('عبارت ناقص است.');
        }

        if ($t[0] === 'num') {
            $this->pos++;
            return ['n', $t[1]];
        }

        if ($t[0] === 'op' && $t[1] === '(') {
            $this->pos++;
            $node = $this->parseOr();
            $this->expectOp(')');
            return $node;
        }

        if ($t[0] === 'id') {
            $name = (string) $t[1];
            $this->pos++;
            if ($name === 'true' || $name === 'false') {
                return ['n', $name === 'true' ? 1.0 : 0.0];
            }
            if (in_array($name, self::RESERVED, true)) {
                throw new FormulaException("کلمهٔ «{$name}» در این جایگاه مجاز نیست.");
            }

            $next = $this->peek();
            if ($next !== null && $next[0] === 'op' && $next[1] === '(') {
                return $this->parseCall($name);
            }

            return ['v', $name];
        }

        throw new FormulaException('نماد غیرمنتظره «' . $t[1] . '» در عبارت.');
    }

    /** @return array<mixed> */
    private function parseCall(string $name): array
    {
        if (!array_key_exists($name, self::FUNCTIONS)) {
            throw new FormulaException("تابع «{$name}» شناخته‌شده نیست.");
        }
        $this->expectOp('(');
        $this->enter();
        $args = [];
        if ($this->acceptOp(')') === null) {
            do {
                $args[] = $this->parseOr();
            } while ($this->acceptOp(',') !== null);
            $this->expectOp(')');
        }
        $this->depth--;

        [$min, $max] = self::FUNCTIONS[$name];
        if (count($args) < $min || ($max !== null && count($args) > $max)) {
            throw new FormulaException("تعداد ورودی‌های تابع «{$name}» نادرست است.");
        }

        return ['f', $name, $args];
    }

    /** Persian (۰-۹) and Arabic-Indic (٠-٩) digits, and the Persian decimal separator, to ASCII. */
    public static function normalizeDigits(string $s): string
    {
        return strtr($s, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '٫' => '.', '٬' => '', '،' => ',',
        ]);
    }
}
