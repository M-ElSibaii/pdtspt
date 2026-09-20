<?php

namespace App\Support;

use Illuminate\Support\Facades\Session;

/**
 * The site's PT/EN switch, aliased as `Lg` for use in Blade.
 *
 * Two jobs, deliberately separate:
 *
 *   Lg::t('Estado')          UI chrome — labels, headings, buttons. Translated from the
 *                            Portuguese source string via resources/lang/ui.php.
 *   Lg::f($row, 'name')      Record content — reads the language column the data already
 *                            holds (nameEn / namePt, definitionEn / definitionPt,
 *                            nameEnSc / namePtSc). Nothing is machine-translated: when a
 *                            record has no text in the chosen language the other language
 *                            is shown rather than a blank cell.
 *
 * The choice is per session, defaults to PT, and is overridable per request with
 * ?lang=pt / ?lang=en. It never changes a record's URI.
 */
class Lang
{
    public const SESSION_KEY = 'pdts.language';

    /** @var array<string,string>|null */
    private static ?array $ui = null;

    public static function supported(): array
    {
        return (array) config('pdts.languages', ['pt', 'en']);
    }

    public static function default(): string
    {
        return (string) config('pdts.default_language', 'pt');
    }

    public static function isSupported(?string $code): bool
    {
        return $code !== null && in_array(strtolower($code), self::supported(), true);
    }

    /** The language this request renders in. */
    public static function current(): string
    {
        $locale = app()->getLocale();

        return self::isSupported($locale) ? strtolower($locale) : self::default();
    }

    /** Remember a choice for the rest of the session and apply it to this request. */
    public static function set(string $code): string
    {
        $code = self::isSupported($code) ? strtolower($code) : self::default();
        Session::put(self::SESSION_KEY, $code);
        app()->setLocale($code);

        return $code;
    }

    public static function isEn(): bool
    {
        return self::current() === 'en';
    }

    public static function isPt(): bool
    {
        return self::current() === 'pt';
    }

    /** The other language — what the toggle switches to. */
    public static function other(): string
    {
        return self::isEn() ? 'pt' : 'en';
    }

    /**
     * Translate one UI string. The Portuguese wording is the key, so a string with no
     * English entry yet still renders (in Portuguese) instead of showing a key.
     */
    public static function t(string $portuguese, array $replace = []): string
    {
        $text = self::isEn() ? (self::ui()[$portuguese] ?? $portuguese) : $portuguese;

        foreach ($replace as $needle => $value) {
            $text = str_replace(':' . $needle, (string) $value, $text);
        }

        return $text;
    }

    /** Pick between two literals without going through the UI map. */
    public static function pick($portuguese, $english)
    {
        return self::isEn() ? $english : $portuguese;
    }

    /**
     * Read a record's text in the current language.
     *
     * $base is the column name without its language suffix: f($pdt, 'pdtName') reads
     * pdtNameEn / pdtNamePt, f($prop, 'definition') reads definitionEn / definitionPt.
     * Falls back to the other language, then to $default.
     */
    public static function f($record, string $base, $default = null)
    {
        $wanted = self::isEn() ? 'En' : 'Pt';
        $other  = self::isEn() ? 'Pt' : 'En';

        $value = self::read($record, $base . $wanted);
        if (self::filled($value)) return $value;

        $value = self::read($record, $base . $other);
        if (self::filled($value)) return $value;

        return $default;
    }

    /**
     * As f(), but for the "short/spoken" name columns (nameEnSc / namePtSc), which
     * carry the human-readable form of a code-shaped name.
     */
    public static function fSc($record, string $base, $default = null)
    {
        $wanted = self::isEn() ? 'EnSc' : 'PtSc';
        $other  = self::isEn() ? 'PtSc' : 'EnSc';

        $value = self::read($record, $base . $wanted);
        if (self::filled($value)) return $value;

        $value = self::read($record, $base . $other);
        if (self::filled($value)) return $value;

        return $default;
    }

    /**
     * The display name of a dictionary property: its spoken name when there is one,
     * otherwise its code-shaped name. Used wherever properties are listed.
     */
    public static function propertyName($record, $default = null)
    {
        return self::fSc($record, 'name') ?? self::f($record, 'name', $default);
    }

    /** Language-appropriate URL for the current page. Used by the header toggle. */
    public static function toggleUrl(string $code): string
    {
        return route('language.switch', ['locale' => $code, 'redirect' => request()->fullUrl()]);
    }

    private static function read($record, string $key)
    {
        if (is_array($record)) return $record[$key] ?? null;
        if (is_object($record)) return $record->{$key} ?? null;

        return null;
    }

    private static function filled($value): bool
    {
        if ($value === null) return false;
        $trimmed = trim((string) $value);

        return $trimmed !== '' && strtolower($trimmed) !== 'n/a';
    }

    /** @return array<string,string> */
    private static function ui(): array
    {
        if (self::$ui === null) {
            $path = resource_path('lang/ui.php');
            self::$ui = is_file($path) ? (array) require $path : [];
        }

        return self::$ui;
    }
}
