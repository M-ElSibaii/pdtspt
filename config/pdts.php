<?php

/*
|--------------------------------------------------------------------------
| PDTs.pt dictionary identity
|--------------------------------------------------------------------------
|
| The single source of truth for the dictionary release and the URI scheme
| root. Every identifier the platform emits is
|
|     {base}/uri/{dictionary_version}/{entity}/{code}
|     {base}/uri/{dictionary_version}/{entity}/{code}/v{versionNumber}
|
| built exclusively by App\Services\UriService — nothing else concatenates
| identifier strings.
|
*/

return [

    // Dictionary release. Bumping this mints a new identifier namespace, so it
    // changes only when the dictionary itself is released anew.
    'dictionary_version' => env('PDTS_DICTIONARY_VERSION', '0.1'),

    // Alias accepted on input (never emitted) that resolves to whatever
    // dictionary_version currently is.
    'dictionary_version_alias' => 'latest',

    // Canonical, externally resolvable root. Identifiers are absolute and
    // stable regardless of the host the app happens to run on (localhost,
    // staging), so exports always carry the public form.
    'uri_base' => env('PDTS_URI_BASE', 'https://pdts.pt'),

    // Path prefix for the resolver routes.
    'uri_prefix' => 'uri',

    // Languages the site renders in. PT is the dictionary's creator language
    // and the default; the choice never affects a record's URI.
    'languages' => ['pt', 'en'],
    'default_language' => 'pt',
];
