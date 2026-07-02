<?php

namespace App\Services;

/**
 * Derives the 7 SI base-dimension exponents (ISO 80000 order: length, mass, time, electric
 * current, thermodynamic temperature, amount of substance, luminous intensity) from a unit's
 * physical symbol by parsing it into recognised SI/derived tokens.
 *
 * This is honest derivation, not fabrication: a result is returned ONLY when the ENTIRE symbol
 * parses into known physical tokens and operators. Any unknown token (currencies, data units,
 * indicators, mojibake, ambiguous symbols) makes the whole parse fail and derive() returns null
 * — those quantities are left unlinked and reported.
 */
class SiDimension
{
    /** Output order (ISO 80000). */
    public const ORDER = ['L', 'M', 'T', 'I', 'Θ', 'N', 'J'];

    /** Longest-first so multi-char prefixes ("da") match before single-char ones. */
    private const PREFIX = ['da', 'Y', 'Z', 'E', 'P', 'T', 'G', 'M', 'k', 'h', 'd', 'c', 'm', 'µ', 'μ', 'u', 'n', 'p', 'f', 'a', 'z', 'y'];

    /** Atomic unit symbol (WITHOUT SI prefix) => [L,M,T,I,Θ,N,J]. */
    private static function atomic(): array
    {
        static $a = null;
        if ($a !== null) return $a;
        $v = fn ($l = 0, $m = 0, $t = 0, $i = 0, $th = 0, $n = 0, $j = 0) => [$l, $m, $t, $i, $th, $n, $j];
        return $a = [
            // dimensionless (angle / ratio)
            '1' => $v(), 'rad' => $v(), 'sr' => $v(), '°' => $v(), 'deg' => $v(), "'" => $v(), '%' => $v(),
            // length
            'm' => $v(1), 'ft' => $v(1), 'in' => $v(1), 'yd' => $v(1), 'mile' => $v(1), 'nmi' => $v(1), 'Ao' => $v(1),
            // area / volume (non-SI named)
            'acre' => $v(2), 'ar' => $v(2), 'l' => $v(3), 'L' => $v(3), 'gal' => $v(3), 'galUK' => $v(3), 'bbl' => $v(3), 'qt' => $v(3),
            // mass
            'g' => $v(0, 1), 't' => $v(0, 1), 'tonne' => $v(0, 1), 'lb' => $v(0, 1), 'oz' => $v(0, 1), 'u' => $v(0, 1),
            // time
            's' => $v(0, 0, 1), 'min' => $v(0, 0, 1), 'h' => $v(0, 0, 1), 'hr' => $v(0, 0, 1),
            'day' => $v(0, 0, 1), 'wk' => $v(0, 0, 1), 'yr' => $v(0, 0, 1), 'mo' => $v(0, 0, 1),
            'Month' => $v(0, 0, 1), 'Jaar' => $v(0, 0, 1), 'year' => $v(0, 0, 1), 'week' => $v(0, 0, 1),
            // electric current
            'A' => $v(0, 0, 0, 1), 'At' => $v(0, 0, 0, 1),
            // temperature
            'K' => $v(0, 0, 0, 0, 1), '°C' => $v(0, 0, 0, 0, 1), '°F' => $v(0, 0, 0, 0, 1),
            // amount of substance
            'mol' => $v(0, 0, 0, 0, 0, 1),
            // luminous intensity / luminous flux
            'cd' => $v(0, 0, 0, 0, 0, 0, 1), 'lm' => $v(0, 0, 0, 0, 0, 0, 1),
            // named derived
            'N' => $v(1, 1, -2),
            'Pa' => $v(-1, 1, -2), 'bar' => $v(-1, 1, -2), 'atm' => $v(-1, 1, -2),
            'mmHg' => $v(-1, 1, -2), 'cmHg' => $v(-1, 1, -2), 'inHg' => $v(-1, 1, -2), 'psi' => $v(-1, 1, -2),
            'J' => $v(2, 1, -2), 'cal' => $v(2, 1, -2), 'BTU' => $v(2, 1, -2), 'Wh' => $v(2, 1, -2),
            'eV' => $v(2, 1, -2), 'therm' => $v(2, 1, -2), 'Nm' => $v(2, 1, -2), 'VAh' => $v(2, 1, -2),
            'W' => $v(2, 1, -3), 'hp' => $v(2, 1, -3), 'VA' => $v(2, 1, -3), 'var' => $v(2, 1, -3), 'VAR' => $v(2, 1, -3),
            'C' => $v(0, 0, 1, 1), 'Ah' => $v(0, 0, 1, 1), 'As' => $v(0, 0, 1, 1),
            'V' => $v(2, 1, -3, -1), 'Volt' => $v(2, 1, -3, -1),
            'F' => $v(-2, -1, 4, 2),
            'Ohm' => $v(2, 1, -3, -2), 'Ω' => $v(2, 1, -3, -2),
            'S' => $v(-2, -1, 3, 2),
            'Wb' => $v(2, 1, -2, -1),
            'T' => $v(0, 1, -2, -1),
            'H' => $v(2, 1, -2, -2),
            'Hz' => $v(0, 0, -1), 'Bq' => $v(0, 0, -1),
            'lx' => $v(-2, 0, 0, 0, 0, 0, 1), 'phot' => $v(-2, 0, 0, 0, 0, 0, 1), 'fc' => $v(-2, 0, 0, 0, 0, 0, 1),
            'knot' => $v(1, 0, -1), 'mph' => $v(1, 0, -1),
            'Js' => $v(2, 1, -1), 'Ns' => $v(1, 1, -1),
            // volume flow (imperial abbreviations)
            'cfm' => $v(3, 0, -1), 'cfs' => $v(3, 0, -1), 'cfh' => $v(3, 0, -1),
            // force (gravitational / imperial)
            'kgf' => $v(1, 1, -2), 'lbf' => $v(1, 1, -2), 'tf' => $v(1, 1, -2),
            // energy / power extras
            'hph' => $v(2, 1, -2), 'VARh' => $v(2, 1, -2),
            // kinematic viscosity (stokes) + day + per-time rates (revolutions/cycles are dimensionless)
            'St' => $v(2, 0, -1), 'd' => $v(0, 0, 1),
            'rpm' => $v(0, 0, -1), 'Rpm' => $v(0, 0, -1), 'cpm' => $v(0, 0, -1),
            'cph' => $v(0, 0, -1), 'opm' => $v(0, 0, -1), 'ACH' => $v(0, 0, -1),
        ];
    }

    /** @return array<int,float>|null the 7-exponent vector, or null if not derivable. */
    public static function derive(string $code): ?array
    {
        $norm = self::normalize($code);
        if ($norm === '' ) return null;
        try {
            $pos = 0;
            $vec = self::parseExpr($norm, $pos);
            if ($pos !== strlen($norm)) return null; // trailing garbage
            return $vec;
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    /** Canonical string e.g. force -> "L1M1T-2"; dimensionless -> "1". Deterministic. */
    public static function canonical(array $vec): string
    {
        $out = '';
        foreach (self::ORDER as $i => $sym) {
            $e = $vec[$i] ?? 0;
            if ($e != 0) {
                $out .= $sym . self::fmt($e);
            }
        }
        return $out === '' ? '1' : $out;
    }

    private static function fmt(float $e): string
    {
        return $e == (int) $e ? (string) (int) $e : rtrim(rtrim(number_format($e, 3, '.', ''), '0'), '.');
    }

    /** Convert superscripts to ^n form and unify multiplication operators. */
    private static function normalize(string $code): string
    {
        $code = trim($code);
        $sup = ['⁰' => '0', '¹' => '1', '²' => '2', '³' => '3', '⁴' => '4', '⁵' => '5', '⁶' => '6', '⁷' => '7', '⁸' => '8', '⁹' => '9', '⁻' => '-'];
        $code = preg_replace_callback('/[⁰¹²³⁴⁵⁶⁷⁸⁹⁻]+/u', function ($m) use ($sup) {
            return '^' . strtr($m[0], $sup);
        }, $code);
        $code = str_replace(['·', '⋅', '*'], '*', $code);
        return $code;
    }

    // ---- recursive-descent evaluator over * / ( ) and atoms with optional ^power ----

    private static function parseExpr(string $s, int &$pos): array
    {
        $vec = self::parseFactor($s, $pos);
        while ($pos < strlen($s) && ($s[$pos] === '*' || $s[$pos] === '/')) {
            $op = $s[$pos++];
            $f = self::parseFactor($s, $pos);
            for ($i = 0; $i < 7; $i++) {
                $vec[$i] += $op === '/' ? -$f[$i] : $f[$i];
            }
        }
        return $vec;
    }

    private static function parseFactor(string $s, int &$pos): array
    {
        if ($pos >= strlen($s)) throw new \RuntimeException('unexpected end');
        if ($s[$pos] === '(') {
            $pos++;
            $inner = self::parseExpr($s, $pos);
            if ($pos >= strlen($s) || $s[$pos] !== ')') throw new \RuntimeException('unbalanced');
            $pos++;
            $pow = self::parsePower($s, $pos);
            return array_map(fn ($x) => $x * $pow, $inner);
        }
        // read a symbol: any char except operators/parens/^
        $start = $pos;
        while ($pos < strlen($s) && !in_array($s[$pos], ['*', '/', '(', ')', '^'], true)) {
            $pos++;
        }
        $sym = substr($s, $start, $pos - $start);
        if ($sym === '') throw new \RuntimeException('empty symbol');
        $pow = self::parsePower($s, $pos);
        $base = self::symbolDim($sym);
        if ($base === null) throw new \RuntimeException("unknown token: {$sym}");
        return array_map(fn ($x) => $x * $pow, $base);
    }

    private static function parsePower(string $s, int &$pos): int
    {
        if ($pos < strlen($s) && $s[$pos] === '^') {
            $pos++;
            $start = $pos;
            if ($pos < strlen($s) && $s[$pos] === '-') $pos++;
            while ($pos < strlen($s) && ctype_digit($s[$pos])) $pos++;
            $num = substr($s, $start, $pos - $start);
            if ($num === '' || $num === '-') throw new \RuntimeException('bad power');
            return (int) $num;
        }
        return 1;
    }

    /** Resolve a symbol (atomic, or SI prefix + atomic) to its dimension vector, or null. */
    private static function symbolDim(string $sym): ?array
    {
        $atomic = self::atomic();
        if (array_key_exists($sym, $atomic)) {
            return $atomic[$sym];
        }
        foreach (self::PREFIX as $p) {
            if ($sym !== $p && str_starts_with($sym, $p)) {
                $rest = substr($sym, strlen($p));
                if ($rest !== '' && array_key_exists($rest, $atomic)) {
                    return $atomic[$rest];
                }
            }
        }
        return null;
    }
}
