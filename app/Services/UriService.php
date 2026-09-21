<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * The one place that knows what a PDTs.pt identifier looks like.
 *
 *     https://pdts.pt/uri/{dictVersion}/{entity}/{code}
 *     https://pdts.pt/uri/{dictVersion}/{entity}/{code}/v{versionNumber}   (optional, pinned)
 *
 * The unversioned form is canonical: it is what goes into exports, declarations and
 * every OwnedUri. The /v{n} form exists only for systems that must pin to a known
 * state of a record.
 *
 * Responsibilities, and nothing outside this class may take them on:
 *   - build()      a URI for any record of any entity
 *   - parse()      a URI back to entity + code + optional version
 *   - resolve()    that triple to the underlying database row
 *   - collisions() report where two different records would claim one code
 *
 * Codes are derived from names, never truncated and never invented. Where names
 * legitimately repeat (groups of properties, class properties, enumerated values)
 * the record id is prefixed, exactly as the scheme prescribes. Where the scheme
 * says the name alone must be unique and it is not, that is a data defect: it is
 * REPORTED by collisions() and by the export guards, never silently patched.
 *
 * Language never enters a URI. A record has one identifier in every language.
 */
class UriService
{
    // ---------------------------------------------------------------- entities

    public const PROPERTY       = 'prop';
    public const DATA_TEMPLATE  = 'dt';
    public const CONSTRUCTION   = 'class';
    public const GROUP          = 'gop';
    public const CLASS_PROPERTY = 'classprop';
    public const DOCUMENT       = 'doc';
    public const UNIT           = 'unit';
    public const QUANTITY_KIND  = 'pq';
    public const DIMENSION      = 'dim';
    public const ENUM_VALUE     = 'enum';

    /**
     * Entity segment => how its records are identified.
     *
     *  table      the backing table
     *  key        primary key column
     *  name       column the code's name part comes from (Portuguese: this is a
     *             Portuguese dictionary, so the Portuguese name IS the identifier)
     *  nameAlias  English name column, when the record has one. It mints no second
     *             identity — it is only a second way to arrive at the one canonical
     *             URI, which the resolver reaches by a 301 redirect.
     *  slug       'pascal'   fold prose into PascalCase
     *             'sanitize' strip URI-hostile characters, keep the name's own casing
     *             'raw'      already a code (a unit symbol, a quantity name)
     *  prefixId   true when {id}- must precede the name because names repeat
     *  versioned  true when the record carries versionNumber/revisionNumber and so
     *             supports the /v{n} pinned form
     *  lineage    column holding the lineage GUID shared by every version of a record
     */
    private const MAP = [
        self::PROPERTY => [
            'table' => 'propertiesdatadictionaries', 'key' => 'Id', 'name' => 'namePt',
            'nameAlias' => 'nameEn',
            'slug' => 'sanitize', 'prefixId' => false, 'versioned' => true, 'lineage' => 'GUID',
            'label' => 'Property',
        ],
        self::DATA_TEMPLATE => [
            'table' => 'productdatatemplates', 'key' => 'Id', 'name' => 'pdtNamePt',
            'nameAlias' => 'pdtNameEn',
            'slug' => 'pascal', 'prefixId' => false, 'versioned' => true, 'lineage' => 'GUID',
            'label' => 'Product data template',
        ],
        self::CONSTRUCTION => [
            'table' => 'constructionobjects', 'key' => 'GUID', 'name' => 'constructionObjectNamePt',
            'nameAlias' => 'constructionObjectNameEn',
            'slug' => 'pascal', 'prefixId' => false, 'versioned' => true, 'lineage' => 'GUID',
            'label' => 'Construction object',
        ],
        self::GROUP => [
            'table' => 'groupofproperties', 'key' => 'Id', 'name' => 'gopNamePt',
            'slug' => 'pascal', 'prefixId' => true, 'versioned' => true, 'lineage' => 'GUID',
            'label' => 'Group of properties',
        ],
        self::CLASS_PROPERTY => [
            'table' => 'properties', 'key' => 'Id', 'name' => null,
            'slug' => 'sanitize', 'prefixId' => true, 'versioned' => false, 'lineage' => null,
            'label' => 'Class property',
        ],
        self::DOCUMENT => [
            'table' => 'referencedocuments', 'key' => 'GUID', 'name' => 'rdName',
            'slug' => 'sanitize', 'prefixId' => false, 'versioned' => false, 'lineage' => null,
            'label' => 'Reference document',
        ],
        self::UNIT => [
            'table' => 'units', 'key' => 'guid', 'name' => 'code',
            'slug' => 'raw', 'prefixId' => false, 'versioned' => false, 'lineage' => null,
            'label' => 'Unit',
        ],
        self::QUANTITY_KIND => [
            'table' => 'physical_quantities', 'key' => 'guid', 'name' => 'name',
            'slug' => 'raw', 'prefixId' => false, 'versioned' => false, 'lineage' => null,
            'label' => 'Quantity kind',
        ],
        self::DIMENSION => [
            'table' => 'dimensions', 'key' => 'guid', 'name' => 'canonical',
            'slug' => 'raw', 'prefixId' => false, 'versioned' => false, 'lineage' => null,
            'label' => 'Dimension',
        ],
        self::ENUM_VALUE => [
            // Enumerated values are carried inline on the dictionary property
            // (listOfPossibleValuesInLanguageN); they have no table and therefore no id
            // of their own. The {id} of the scheme is the owning property's dictionary
            // id — the only identifier that exists. Nothing is invented.
            'table' => 'propertiesdatadictionaries', 'key' => 'Id', 'name' => null,
            'slug' => 'sanitize', 'prefixId' => true, 'versioned' => false, 'lineage' => null,
            'label' => 'Enumerated value',
        ],
    ];

    public static function entities(): array
    {
        return array_keys(self::MAP);
    }

    public static function isEntity(?string $entity): bool
    {
        return $entity !== null && isset(self::MAP[$entity]);
    }

    public static function label(string $entity): string
    {
        return self::MAP[$entity]['label'] ?? $entity;
    }

    public static function supportsVersionPin(string $entity): bool
    {
        return (bool) (self::MAP[$entity]['versioned'] ?? false);
    }

    // ------------------------------------------------------------- composition

    public static function dictionaryVersion(): string
    {
        return (string) config('pdts.dictionary_version', '0.1');
    }

    public static function base(): string
    {
        return rtrim((string) config('pdts.uri_base', 'https://pdts.pt'), '/');
    }

    private static function prefix(): string
    {
        return trim((string) config('pdts.uri_prefix', 'uri'), '/');
    }

    /** Normalise an inbound dictionary version ("latest" -> the configured release). */
    public static function normaliseDictVersion(?string $dictVersion): ?string
    {
        $current = self::dictionaryVersion();
        if ($dictVersion === null || $dictVersion === '') return $current;
        if ($dictVersion === config('pdts.dictionary_version_alias', 'latest')) return $current;

        return $dictVersion === $current ? $current : null;
    }

    /**
     * Percent-encode one code for use as a path value, leaving "/" literal so unit
     * symbols such as "kg/m³" stay readable. The resolver route captures the whole
     * remainder, so a literal slash is safe.
     */
    private static function encode(string $code): string
    {
        return str_replace('%2F', '/', rawurlencode($code));
    }

    /** Path only: "/uri/0.1/prop/Potassio" (+ "/v4"). */
    public static function path(string $entity, string $code, ?int $version = null): string
    {
        self::assertEntity($entity);

        $path = '/' . self::prefix() . '/' . self::dictionaryVersion()
            . '/' . $entity . '/' . self::encode($code);

        if ($version !== null) {
            if (!self::supportsVersionPin($entity)) {
                throw new \InvalidArgumentException(
                    "Entity '{$entity}' has no versionNumber, so it cannot be pinned with /v{n}."
                );
            }
            $path .= '/v' . $version;
        }

        return $path;
    }

    /** Canonical, absolute, externally resolvable identifier. This is what exports carry. */
    public static function uri(string $entity, string $code, ?int $version = null): string
    {
        return self::base() . self::path($entity, $code, $version);
    }

    /** Same identifier, but rooted at this deployment — use for hrefs inside the site. */
    public static function link(string $entity, string $code, ?int $version = null): string
    {
        return url(self::path($entity, $code, $version));
    }

    // ---------------------------------------------------------- record -> URI

    /**
     * Canonical URI for a record. $record may be an Eloquent model, a stdClass row
     * or an array. Pass $version to emit the pinned form.
     */
    public static function build(string $entity, $record, ?int $version = null): string
    {
        return self::uri($entity, self::codeFor($entity, $record), $version);
    }

    /** As build(), but rooted at this deployment (for in-site links). */
    public static function buildLink(string $entity, $record, ?int $version = null): string
    {
        return self::link($entity, self::codeFor($entity, $record), $version);
    }

    /** Canonical URI pinned to the record's own versionNumber. */
    public static function buildPinned(string $entity, $record): string
    {
        $v = self::get($record, 'versionNumber');

        return self::build($entity, $record, $v === null ? null : (int) $v);
    }

    /** Infer the entity from an Eloquent model and build its URI. */
    public static function forModel($model, ?int $version = null): string
    {
        return self::build(self::entityForModel($model), $model, $version);
    }

    public static function entityForModel($model): string
    {
        $map = [
            \App\Models\propertiesdatadictionaries::class => self::PROPERTY,
            \App\Models\productdatatemplates::class       => self::DATA_TEMPLATE,
            \App\Models\constructionobjects::class        => self::CONSTRUCTION,
            \App\Models\groupofproperties::class          => self::GROUP,
            \App\Models\properties::class                 => self::CLASS_PROPERTY,
            \App\Models\referencedocuments::class         => self::DOCUMENT,
            \App\Models\Unit::class                       => self::UNIT,
            \App\Models\PhysicalQuantity::class           => self::QUANTITY_KIND,
            \App\Models\Dimension::class                  => self::DIMENSION,
        ];
        $class = is_object($model) ? get_class($model) : null;
        if ($class !== null && isset($map[$class])) return $map[$class];

        throw new \InvalidArgumentException(
            'No URI entity is defined for ' . ($class ?: gettype($model)) . '.'
        );
    }

    /**
     * The code for a record, per the scheme's table.
     *
     * A class property's name comes from the dictionary property it points at, so
     * either pass a joined row (which already carries namePt) or let this look it up
     * from properties.propertyId.
     */
    public static function codeFor(string $entity, $record): string
    {
        self::assertEntity($entity);
        $def = self::MAP[$entity];

        if ($entity === self::CLASS_PROPERTY) {
            $name = self::get($record, 'namePt') ?? self::classPropertyName($record);

            return self::joinCode(self::get($record, 'Id'), self::slugify($name, 'sanitize'));
        }

        if ($entity === self::ENUM_VALUE) {
            throw new \InvalidArgumentException(
                'Enumerated values are identified by owning property + value: use UriService::enumCode().'
            );
        }

        $name = $def['name'] ? self::get($record, $def['name']) : null;
        $slug = self::slugify($name, $def['slug']);

        if (!$def['prefixId']) {
            if ($slug === '') {
                throw new \RuntimeException(
                    self::label($entity) . ' has no usable name, so no code can be derived for it.'
                );
            }

            return $slug;
        }

        return self::joinCode(self::get($record, $def['key']), $slug);
    }

    /** {id}-Name for an enumerated value of a dictionary property. */
    public static function enumCode($dictionaryProperty, string $value): string
    {
        return self::joinCode(self::get($dictionaryProperty, 'Id'), self::slugify($value, 'sanitize'));
    }

    public static function enumUri($dictionaryProperty, string $value): string
    {
        return self::uri(self::ENUM_VALUE, self::enumCode($dictionaryProperty, $value));
    }

    /**
     * Canonical URI for the latest row of a lineage GUID. Exporters resolve relation
     * targets this way: a relation points at a lineage, not at one stored row.
     */
    public static function forLineage(string $entity, string $guid, ?int $version = null): ?string
    {
        $row = self::latestOfLineage($entity, $guid, $version);

        return $row ? self::build($entity, $row, $version) : null;
    }

    /** The dictionary's own namespace: https://pdts.pt/uri/0.1 (bSDD DictionaryUri). */
    public static function dictionaryUri(): string
    {
        return self::base() . '/' . self::prefix() . '/' . self::dictionaryVersion();
    }

    /**
     * A class property as listed under one particular owner class, for bSDD.
     *
     * bSDD requires every OwnedUri to be globally unique, but one class property is
     * listed twice — under its PDT class and under its group class. The owner's name
     * and id are appended to the code:
     *
     *     .../classprop/2279-LibertacaoDeCadmio-Mestre37
     *     .../classprop/2279-LibertacaoDeCadmio-DadosGerais73
     *
     * This does not create a new identifier: the resolver reads only the leading
     * class-property id, so both land on the same class-property page as the
     * canonical .../classprop/2279-LibertacaoDeCadmio.
     *
     * @param string $ownerEntity self::DATA_TEMPLATE or self::GROUP
     */
    public static function classPropertyInContext($classProperty, string $ownerEntity, $owner): string
    {
        if (!in_array($ownerEntity, [self::DATA_TEMPLATE, self::GROUP], true)) {
            throw new \InvalidArgumentException("A class property is owned by a dt or a gop, not '{$ownerEntity}'.");
        }

        $nameColumn = self::MAP[$ownerEntity]['name'];
        $ownerTag = self::pascalCase(self::get($owner, $nameColumn)) . self::get($owner, 'Id');

        return self::uri(self::CLASS_PROPERTY, self::codeFor(self::CLASS_PROPERTY, $classProperty) . '-' . $ownerTag);
    }

    /**
     * Identity of a relation between two classes (bSDD ClassRelation.OwnedUri).
     * Relations are not dictionary entities, so they are not given a segment of their
     * own: they are named as a fragment of their source class's URI, which keeps them
     * inside the scheme, unique, and dereferenceable to the class that states them.
     */
    public static function relationUri(string $sourceUri, string $relationType, string $targetUri): string
    {
        $target = self::parse($targetUri);
        $targetTag = $target ? $target['entity'] . '-' . $target['code'] : md5($targetUri);

        return strtok($sourceUri, '#') . '#' . $relationType . '-' . str_replace('/', '-', $targetTag);
    }

    private static function joinCode($id, string $slug): string
    {
        if ($id === null || $id === '') {
            throw new \RuntimeException('Cannot build a code without a record id.');
        }

        return $slug === '' ? (string) $id : $id . '-' . $slug;
    }

    // -------------------------------------------------------------- parse

    /**
     * Parse a full URI, or a bare path, into its parts.
     *
     * @return array{dictVersion:string,entity:string,code:string,version:?int}|null
     *         null when the string is not a PDTs.pt identifier at all.
     */
    public static function parse(string $uri): ?array
    {
        $path = parse_url(trim($uri), PHP_URL_PATH);
        if ($path === false || $path === null) $path = trim($uri);
        $path = '/' . ltrim(rawurldecode($path), '/');

        $prefix = '/' . self::prefix() . '/';
        $at = strpos($path, $prefix);
        if ($at === false) return null;

        $rest = substr($path, $at + strlen($prefix));
        $parts = explode('/', $rest, 3);
        if (count($parts) < 3) return null;

        [$dictVersion, $entity, $code] = $parts;
        if (!self::isEntity($entity)) return null;

        [$code, $version] = self::splitVersionSuffix($code);
        if ($code === '') return null;

        return [
            'dictVersion' => $dictVersion,
            'entity'      => $entity,
            'code'        => $code,
            'version'     => $version,
        ];
    }

    /**
     * Split a resolver-route {code} value (which may carry a trailing /v{n}).
     *
     * @return array{0:string,1:?int}
     */
    public static function splitVersionSuffix(string $code): array
    {
        if (preg_match('~^(.*)/v(\d+)$~', $code, $m)) {
            return [trim($m[1], '/'), (int) $m[2]];
        }

        return [trim($code, '/'), null];
    }

    // ------------------------------------------------------------- resolve

    /**
     * Resolve entity + code (+ optional pinned version) to the underlying row.
     *
     * Unversioned resolves to the CURRENT state of the record: the Active row of the
     * lineage with the highest version/revision, falling back to the highest of any
     * status when nothing is Active.
     *
     * Returns null when nothing matches. Throws CodeCollisionException when the code
     * is ambiguous — two different records claiming it is a data defect that must be
     * seen, not papered over by picking one.
     */
    public static function resolve(string $entity, string $code, ?int $version = null)
    {
        self::assertEntity($entity);
        $def = self::MAP[$entity];

        if ($version !== null && !$def['versioned']) {
            return null;
        }

        if ($entity === self::ENUM_VALUE) {
            return self::resolveEnum($code);
        }

        // Id-prefixed codes address one stored row directly.
        if ($def['prefixId']) {
            $id = self::leadingId($code);
            if ($id === null) return null;

            $row = DB::table($def['table'])->where($def['key'], $id)->first();
            if (!$row) return null;
            if ($version === null) return $row;

            // Pinned: walk to the requested version of the same lineage.
            return self::latestOfLineage($entity, (string) ($row->{$def['lineage']} ?? ''), $version);
        }

        $candidates = self::rowsForCode($entity, $code);
        if ($candidates->isEmpty()) return null;

        // One code may legitimately cover many rows of ONE lineage (its versions).
        $lineages = $def['lineage']
            ? $candidates->pluck($def['lineage'])->filter()->unique()->values()
            : collect();

        if ($lineages->count() > 1) {
            throw new CodeCollisionException($entity, $code, self::describeAll($entity, $candidates));
        }

        if (!$def['versioned']) {
            if ($candidates->count() > 1) {
                throw new CodeCollisionException($entity, $code, self::describeAll($entity, $candidates));
            }

            return $candidates->first();
        }

        return self::pickVersion($candidates, $version);
    }

    /** Resolve a whole URI string in one step. */
    public static function resolveUri(string $uri)
    {
        $parts = self::parse($uri);
        if (!$parts) return null;
        if (self::normaliseDictVersion($parts['dictVersion']) === null) return null;

        return self::resolve($parts['entity'], $parts['code'], $parts['version']);
    }

    /**
     * Every stored row whose canonical code equals $code.
     *
     * Codes are derived by stripping and folding characters, so they cannot be matched
     * with a plain WHERE on the name column. The candidate set is narrowed in SQL by
     * the code's alphanumeric skeleton and then confirmed in PHP against the real code.
     */
    private static function rowsForCode(string $entity, string $code)
    {
        return self::rowsMatching($entity, $code, self::MAP[$entity]['name'], false);
    }

    /**
     * Rows whose $column, run through this entity's slug rule, equals $code.
     *
     * @param bool $asAlias compare against the English name instead of the canonical one
     */
    private static function rowsMatching(string $entity, string $code, ?string $column, bool $asAlias)
    {
        $def = self::MAP[$entity];
        if ($column === null) return collect();

        $query = DB::table($def['table']);

        if ($def['slug'] === 'raw') {
            // Unit symbols and quantity names are stored verbatim and are CASE-SENSITIVE:
            // "MW" (megawatt) and "mW" (milliwatt) are different units. MySQL's default
            // collation is case-insensitive, so the comparison is forced to binary —
            // otherwise a URI would resolve to the wrong unit.
            return $query->whereRaw('BINARY `' . $column . '` = ?', [$code])->get();
        }

        // Every derivation preserves the letters and digits of the name, in order —
        // so an ordered LIKE on that skeleton is a sound (if loose) prefilter.
        $skeleton = preg_replace('/[^A-Za-z0-9]/', '', $code);
        $chars = str_split($skeleton === '' ? $code : $skeleton);
        $like = '%' . implode('%', array_map(fn($c) => addcslashes($c, '%_\\'), $chars)) . '%';

        return $query->where($column, 'like', $like)->get()
            ->filter(function ($row) use ($entity, $code, $asAlias) {
                try {
                    $derived = $asAlias ? self::aliasCodeFor($entity, $row) : self::codeFor($entity, $row);
                    return $derived === $code;
                } catch (\Throwable $e) {
                    return false;
                }
            })->values();
    }

    /** Latest (or pinned) row of one lineage: Active first, then highest version/revision. */
    public static function latestOfLineage(string $entity, string $guid, ?int $version = null)
    {
        self::assertEntity($entity);
        $def = self::MAP[$entity];
        if (!$def['lineage'] || $guid === '') return null;

        return self::pickVersion(DB::table($def['table'])->where($def['lineage'], $guid)->get(), $version);
    }

    /**
     * Choose one row out of a lineage's rows.
     *  - $version given: the highest revision within that versionNumber.
     *  - otherwise: Active preferred, then highest version, then highest revision.
     */
    private static function pickVersion($rows, ?int $version)
    {
        $rows = collect($rows);
        if ($rows->isEmpty()) return null;

        if ($version !== null) {
            $rows = $rows->filter(fn($r) => (int) ($r->versionNumber ?? -1) === $version)->values();
            if ($rows->isEmpty()) return null;

            return $rows->sortByDesc(fn($r) => (int) ($r->revisionNumber ?? 0))->first();
        }

        return $rows->sort(function ($a, $b) {
            return [self::activeRank($b), (int) ($b->versionNumber ?? 0), (int) ($b->revisionNumber ?? 0)]
                <=> [self::activeRank($a), (int) ($a->versionNumber ?? 0), (int) ($a->revisionNumber ?? 0)];
        })->first();
    }

    private static function activeRank($row): int
    {
        return (($row->status ?? null) === 'Active') ? 1 : 0;
    }

    /** "73-DadosGerais" -> "73"; also accepts a bare id. */
    private static function leadingId(string $code): ?string
    {
        return preg_match('/^(\d+)(?:-|$)/', $code, $m) ? $m[1] : null;
    }

    /**
     * An enum code is {dictionaryPropertyId}-{Value}. It resolves to the owning
     * dictionary property plus the matching value from its inline list, so the
     * resolver can land on the property page at that value.
     *
     * @return object|null  {property, value}
     */
    private static function resolveEnum(string $code)
    {
        $id = self::leadingId($code);
        if ($id === null) return null;

        $property = DB::table('propertiesdatadictionaries')->where('Id', $id)->first();
        if (!$property) return null;

        foreach (self::enumValuesOf($property) as $value) {
            if (self::enumCode($property, $value) === $code) {
                return (object) ['property' => $property, 'value' => $value];
            }
        }

        return null;
    }

    /**
     * The enumerated values a dictionary property allows, grouped by the language tag
     * the data carries.
     *
     * listOfPossibleValuesInLanguageN holds them as parenthesised, language-tagged
     * groups whose LAST item is the language code:
     *
     *     (LEFT, RIGHT, NOTDEFINED, EN), (ESQUERDA, DIREITA, NÃODEFINIDO, PT)
     *
     * and sometimes as a bare list ("True/False"). Both are read here; nothing is
     * translated or invented — the stored values are returned as they stand.
     *
     * @return array<string,array<int,string>> language tag (or '' when untagged) => values
     */
    public static function enumValueGroups($property): array
    {
        $raw = trim((string) (self::get($property, 'listOfPossibleValuesInLanguageN') ?? ''));
        if ($raw === '' || strtolower($raw) === 'n/a') return [];

        $groups = [];

        if (preg_match_all('/\(([^()]*)\)/u', $raw, $m) && !empty($m[1])) {
            foreach ($m[1] as $group) {
                $values = self::splitValues($group, '/[,;\r\n]+/u');
                if (!$values) continue;

                // A trailing 2-letter token is the group's language, not a value.
                $tag = '';
                $last = end($values);
                if (preg_match('/^[a-z]{2}$/i', (string) $last)) {
                    $tag = strtolower(array_pop($values));
                }
                if ($values) $groups[$tag] = $values;
            }
        }

        if (!$groups) {
            $values = self::splitValues($raw, '#[/,;\r\n]+#u');
            if ($values) $groups[''] = $values;
        }

        return $groups;
    }

    /**
     * The canonical value list an enum identifier is built from.
     *
     * A URI may not depend on the reader's language, so one group has to be canonical:
     * the English-tagged group when the data has one (it is the interoperable form the
     * dictionary shares with bSDD/IFC), otherwise the first group stored.
     */
    public static function enumValuesOf($property): array
    {
        $groups = self::enumValueGroups($property);
        if (!$groups) return [];

        return $groups['en'] ?? reset($groups);
    }

    /** @return array<int,string> */
    private static function splitValues(string $text, string $pattern): array
    {
        $parts = preg_split($pattern, $text) ?: [];

        return array_values(array_filter(array_map('trim', $parts), fn($v) => $v !== ''));
    }

    /**
     * One real code per entity, for seeding documentation and the admin API tester with
     * examples that actually resolve. Returns '' for an entity with no usable record.
     *
     * @return array<string,string>
     */
    public static function sampleCodes(): array
    {
        $out = [];

        foreach (self::MAP as $entity => $def) {
            if ($entity === self::ENUM_VALUE) continue;

            $query = DB::table($def['table']);
            if ($def['versioned']) $query->where('status', 'Active');
            if ($entity === self::DOCUMENT) $query->where('rdName', '!=', 'n/a');

            $out[$entity] = '';
            foreach ($query->limit(25)->get() as $row) {
                try {
                    $out[$entity] = self::codeFor($entity, $row);
                    break;
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }

        return $out;
    }


    // --------------------------------------------------------------- aliases

    /**
     * The English-name code for a record, or null when it has none.
     *
     * This is NOT a second identifier. Codes are the Portuguese name — this is a
     * Portuguese dictionary, and namePt is what appears in exports, in DoPCs and in the
     * canonical URI. The English code exists only so that a reader who knows the record
     * by its English name can type it and be redirected (301) to the one canonical URI.
     *
     * Returns null when the English name is missing, or when it derives to the same code
     * as the Portuguese one (in which case there is nothing to alias).
     */
    public static function aliasCodeFor(string $entity, $record): ?string
    {
        self::assertEntity($entity);
        $def = self::MAP[$entity];

        $column = $def['nameAlias'] ?? null;
        if ($column === null) return null;

        $slug = self::slugify(self::get($record, $column), $def['slug']);
        if ($slug === '') return null;

        $code = $def['prefixId'] ? self::joinCode(self::get($record, $def['key']), $slug) : $slug;

        try {
            if ($code === self::codeFor($entity, $record)) return null;
        } catch (\Throwable $e) {
            // No canonical code (no Portuguese name) — the alias is still a way in.
        }

        return $code;
    }

    /** True when this entity has an English name that can be aliased. */
    public static function hasAlias(string $entity): bool
    {
        return (self::MAP[$entity]['nameAlias'] ?? null) !== null;
    }

    /**
     * Resolve a code as an ENGLISH name, for records whose canonical (Portuguese) code
     * it is not. Used only after the canonical lookup has come up empty, so a Portuguese
     * code always wins over an English one that happens to spell the same.
     *
     * Returns null when nothing matches. Throws CodeCollisionException when two different
     * records answer to the same English name — an ambiguous alias is no more usable than
     * an ambiguous identifier.
     */
    public static function resolveAlias(string $entity, string $code, ?int $version = null)
    {
        self::assertEntity($entity);
        $def = self::MAP[$entity];

        if (!self::hasAlias($entity)) return null;
        if ($version !== null && !$def['versioned']) return null;

        $candidates = self::rowsMatching($entity, $code, $def['nameAlias'], true);
        if ($candidates->isEmpty()) return null;

        $lineages = $def['lineage']
            ? $candidates->pluck($def['lineage'])->filter()->unique()->values()
            : collect();

        if ($lineages->count() > 1) {
            throw new CodeCollisionException($entity, $code, self::describeAll($entity, $candidates));
        }

        if (!$def['versioned']) {
            if ($candidates->count() > 1) {
                throw new CodeCollisionException($entity, $code, self::describeAll($entity, $candidates));
            }

            return $candidates->first();
        }

        return self::pickVersion($candidates, $version);
    }

    /**
     * Every alias the dictionary answers to, as code => canonical code, for one entity.
     * Aliases that would shadow a canonical code are EXCLUDED: the canonical name always
     * wins, and the clash is reported by aliasConflicts().
     *
     * @return array<string,string>
     */
    public static function aliases(string $entity, string $scope = 'export'): array
    {
        if (!self::hasAlias($entity)) return [];

        $canonical = [];
        $aliases = [];

        foreach (self::rowsForCollisionScan($entity, $scope) as $row) {
            try {
                $canonical[self::codeFor($entity, $row)] = true;
            } catch (\Throwable $e) {
                // unnameable row; reported elsewhere
            }
            $alias = self::aliasCodeFor($entity, $row);
            if ($alias === null) continue;
            try {
                $aliases[$alias][] = self::codeFor($entity, $row);
            } catch (\Throwable $e) {
                continue;
            }
        }

        $out = [];
        foreach ($aliases as $alias => $targets) {
            if (isset($canonical[$alias])) continue;              // canonical wins
            $targets = array_values(array_unique($targets));
            if (count($targets) > 1) continue;                    // ambiguous, see aliasConflicts()
            $out[$alias] = $targets[0];
        }

        return $out;
    }

    /**
     * Aliases that cannot be honoured, with the reason: either the English name is also
     * some record's Portuguese name, or two records share one English name.
     *
     * @return array<int,array{entity:string,code:string,reason:string,records:array<int,string>}>
     */
    public static function aliasConflicts(string $scope = 'export'): array
    {
        $out = [];

        foreach (self::entities() as $entity) {
            if (!self::hasAlias($entity)) continue;

            $canonicalOwner = [];
            $aliasRows = [];

            foreach (self::rowsForCollisionScan($entity, $scope) as $row) {
                try {
                    $canonicalOwner[self::codeFor($entity, $row)][] = $row;
                } catch (\Throwable $e) {
                    // unnameable row; reported by uri:check
                }
                $alias = self::aliasCodeFor($entity, $row);
                if ($alias !== null) $aliasRows[$alias][] = $row;
            }

            foreach ($aliasRows as $alias => $rows) {
                if (isset($canonicalOwner[$alias])) {
                    $out[] = [
                        'entity' => $entity,
                        'code' => $alias,
                        'reason' => 'shadowed by a Portuguese name',
                        'records' => array_merge(
                            self::describeAll($entity, $canonicalOwner[$alias]),
                            self::describeAll($entity, $rows)
                        ),
                    ];
                    continue;
                }

                $lineages = collect($rows)->pluck(self::MAP[$entity]['lineage'] ?? 'x')->filter()->unique();
                if ($lineages->count() > 1) {
                    $out[] = [
                        'entity' => $entity,
                        'code' => $alias,
                        'reason' => 'two records share this English name',
                        'records' => self::describeAll($entity, $rows),
                    ];
                }
            }
        }

        return $out;
    }

    // ----------------------------------------------------------- collisions

    /**
     * Every code claimed by more than one record, per entity.
     *
     * Scope matters: 'export' checks only what the dictionary actually publishes
     * (Active rows), which is the set whose codes must be unique. 'all' checks every
     * stored row, which also surfaces superseded lineages and is only useful for a
     * full audit.
     *
     * @return array<int,array{entity:string,code:string,records:array<int,string>}>
     */
    public static function collisions(string $scope = 'export'): array
    {
        $out = [];

        foreach (self::MAP as $entity => $def) {
            if ($entity === self::ENUM_VALUE) continue; // derived, no stored rows

            $byCode = [];
            foreach (self::rowsForCollisionScan($entity, $scope) as $row) {
                try {
                    $code = self::codeFor($entity, $row);
                } catch (\Throwable $e) {
                    continue; // unnameable rows are reported by uri:check, not here
                }

                // Versions of ONE record share a code by design; only distinct records
                // (distinct lineages) claiming one code is a defect.
                $identity = $def['lineage']
                    ? (string) ($row->{$def['lineage']} ?? '')
                    : '';
                $identity = $identity !== '' ? $identity : ('#' . ($row->{$def['key']} ?? '?'));

                $byCode[$code][$identity] = self::describe($entity, $row);
            }

            foreach ($byCode as $code => $records) {
                if (count($records) > 1) {
                    $out[] = [
                        'entity'  => $entity,
                        'code'    => $code,
                        'records' => array_values($records),
                    ];
                }
            }
        }

        return $out;
    }

    private static function rowsForCollisionScan(string $entity, string $scope)
    {
        $def = self::MAP[$entity];
        $query = DB::table($def['table']);

        if ($scope === 'export' && $def['versioned']) {
            $query->where('status', 'Active');
        }

        return $query->get();
    }

    private static function describeAll(string $entity, $rows): array
    {
        return collect($rows)->map(fn($r) => self::describe($entity, $r))->values()->all();
    }

    private static function describe(string $entity, $row): string
    {
        $def  = self::MAP[$entity];
        $key  = self::get($row, $def['key']) ?? '?';
        $name = $def['name'] ? (self::get($row, $def['name']) ?? '') : '';
        $ver  = self::get($row, 'versionNumber');
        $v    = $ver === null ? '' : ' v' . $ver . '.' . (self::get($row, 'revisionNumber') ?? 0);

        return $def['table'] . '.' . $def['key'] . '=' . $key . $v . ' "' . $name . '"';
    }

    /**
     * Guard for the exporters: refuse to emit a dictionary whose codes are ambiguous.
     * Restricted to the entities a given export actually carries.
     *
     * @param array<int,string> $entities
     */
    public static function assertNoCollisions(array $entities, string $scope = 'export'): void
    {
        $found = array_values(array_filter(
            self::collisions($scope),
            fn($c) => in_array($c['entity'], $entities, true)
        ));

        if (!$found) return;

        $lines = [];
        foreach ($found as $c) {
            $lines[] = $c['entity'] . '/' . $c['code'] . "\n     " . implode("\n     ", $c['records']);
        }

        throw new \RuntimeException(
            'Export aborted — ' . count($found) . ' identifier collision(s). Two different records '
            . 'would claim the same URI, so the dictionary cannot be published until they are '
            . "reconciled (Admin > Dedupe dictionary). Nothing was renamed automatically.\n\n - "
            . implode("\n\n - ", $lines)
        );
    }

    // ------------------------------------------------------------- utilities

    private static function assertEntity(string $entity): void
    {
        if (!self::isEntity($entity)) {
            throw new \InvalidArgumentException(
                "Unknown URI entity '{$entity}'. Known: " . implode(', ', self::entities()) . '.'
            );
        }
    }

    /** Read a column off a model, stdClass row or array, uniformly. */
    private static function get($record, string $key)
    {
        if (is_array($record)) return $record[$key] ?? null;
        if (is_object($record)) return $record->{$key} ?? null;

        return null;
    }

    /** A class property's name lives on the dictionary property it points at. */
    private static function classPropertyName($record): ?string
    {
        $propertyId = self::get($record, 'propertyId');
        if (!$propertyId) return null;

        return DB::table('propertiesdatadictionaries')->where('Id', $propertyId)->value('namePt');
    }

    private static function slugify(?string $name, string $style): string
    {
        $name = (string) $name;

        switch ($style) {
            case 'raw':
                return trim($name);
            case 'pascal':
                return self::pascalCase($name);
            case 'sanitize':
            default:
                return self::sanitize($name);
        }
    }

    private const ACCENTS = [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'ý' => 'y', 'ÿ' => 'y', 'ç' => 'c', 'ñ' => 'n',
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'Ý' => 'Y', 'Ç' => 'C', 'Ñ' => 'N',
    ];

    /**
     * Strip only the characters a URI cannot carry, keeping the name's own casing.
     * Used where the stored name is already a code-shaped token ("LibertacaoDeCadmio").
     * Nothing is shortened.
     */
    public static function sanitize(?string $string): string
    {
        if (!$string) return '';
        $string = strtr($string, self::ACCENTS);

        return preg_replace('/["#%\/\\\\:`{}\[\]|;<>?~\s]/', '', $string);
    }

    /**
     * Fold a human-written name into PascalCase ("Placa de gesso" -> "PlacaDeGesso").
     * Used where the stored name is prose. Nothing is shortened.
     */
    public static function pascalCase(?string $string): string
    {
        if (!$string) return '';
        $string = strtr($string, self::ACCENTS);
        $string = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $string);
        $words = preg_split('/[\s_\-]+/', trim($string)) ?: [];

        return implode('', array_map('ucfirst', array_map('strtolower', $words)));
    }
}
