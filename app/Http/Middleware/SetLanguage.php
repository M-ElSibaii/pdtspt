<?php

namespace App\Http\Middleware;

use App\Support\Lang;
use Closure;
use Illuminate\Http\Request;

/**
 * Decide which language this request renders in.
 *
 * Order of precedence:
 *   1. ?lang=pt / ?lang=en on the URL — opens a page in a given language, and sticks
 *      for the rest of the session so following links keep it.
 *   2. the session's remembered choice.
 *   3. Portuguese, the dictionary's creator language.
 *
 * The language only ever affects rendering. Identifiers are language-independent, so
 * this never touches routing or URI generation.
 */
class SetLanguage
{
    public function handle(Request $request, Closure $next)
    {
        $requested = $request->query('lang');

        if (Lang::isSupported($requested)) {
            Lang::set((string) $requested);
        } else {
            $remembered = $request->session()->get(Lang::SESSION_KEY);
            app()->setLocale(Lang::isSupported($remembered) ? $remembered : Lang::default());
        }

        return $next($request);
    }
}
