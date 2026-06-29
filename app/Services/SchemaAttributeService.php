<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Single source of truth for the full attribute model of every PDT-hierarchy table,
 * read LIVE from the database schema (column introspection) so it stays correct as the
 * schema changes — no hardcoded field lists.
 *
 * For each column it reports: input kind, whether it is a SYSTEM/lineage field
 * (auto-managed by the services and shown read-only, never user-typed), and whether it
 * is MANDATORY for the user, by which mechanism:
 *   - 'db'   : column is NOT NULL without a default (structural), and
 *   - 'rule' : EN ISO 23386 management-rule mandatory that is nullable in the DB but
 *              required by the existing create forms' validate() rules.
 *
 * System/lineage fields are excluded from user-mandatory blocking even when they are
 * DB NOT NULL (the services fill GUID / version / dates / status / parent FKs).
 */
class SchemaAttributeService
{
    /** Levels the unified editor manages, in hierarchy order. */
    public const TABLES = [
        'constructionobjects',
        'productdatatemplates',
        'groupofproperties',
        'properties',
        'propertiesdatadictionaries',
    ];

    /**
     * Auto-managed lineage / system / hierarchy columns. Shown read-only; filled by the
     * services (GuidGenerator, versioning + date logic) or by the editor's parent context.
     * Never part of user-mandatory blocking.
     */
    private const SYSTEM = [
        'Id',
        'GUID',
        'versionNumber', 'revisionNumber',
        'dateOfRevision', 'dateOfVersion', 'dateOfCreation', 'dateofActivation', 'dateOfLastChange',
        'created_at', 'updated_at',
        'status',
        'listOfReplacedProperties', 'listOfReplacingProperties',
        'depreciationDate', 'depreciationExplanation',
        // Parent links set by the editor hierarchy, not free-typed:
        'pdtId', 'pdtID', 'gopID', 'propertyId',
        // NOTE: constructionObjectGUID is NOT system — it's the user's choice of construction
        // object for the PDT (editable via a searchable dropdown), so it stays editable.
    ];

    /**
     * Management-rule mandatories (EN ISO 23386) that the DB leaves nullable but the
     * existing create flow requires. Detected from the controllers' validate() rules
     * (e.g. PropertiesController::addPropertyManual). Kept tiny and explicit — the
     * attribute LIST itself is always live from the schema, only these extra REQUIRED
     * flags are declared. Adjust here if the management rules change.
     */
    private const RULE_MANDATORY = [
        // dataType added as a management-rule mandatory: the DB column is `text NULL` (not
        // NOT-NULL) and the legacy addPropertyManual treated it as nullable, so it was NOT
        // mandatory before — bSDD requires every property to declare a dataType, so it is
        // enforced here as a rule mandatory (confirmed: db = optional, rule = required now).
        'propertiesdatadictionaries' => ['nameEnSc', 'namePtSc', 'definitionEn', 'definitionPt', 'dataType'],
        // constructionobjects / productdatatemplates / groupofproperties / properties:
        // no rule-mandatory beyond their DB NOT NULL columns.
    ];

    /** Human label overrides; otherwise the column name is title-cased. */
    private const LABELS = [
        'pdtNameEn' => 'Name (EN)', 'pdtNamePt' => 'Name (PT)',
        'gopNameEn' => 'Name (EN)', 'gopNamePt' => 'Name (PT)',
        // Dictionary names: nameEn/namePt are the CODE (PascalCase, no accents); the *Sc
        // pair is the human-readable sentence case (spaces & accents allowed).
        'nameEn' => 'Code (EN) — PascalCase, no accents', 'namePt' => 'Code (PT) — PascalCase, no accents',
        'nameEnSc' => 'Name (EN) — sentence case', 'namePtSc' => 'Name (PT) — sentence case',
        'constructionObjectNameEn' => 'Name (EN)', 'constructionObjectNamePt' => 'Name (PT)',
        'definitionEn' => 'Definition (EN)', 'definitionPt' => 'Definition (PT)',
        'descriptionEn' => 'Description (EN)', 'descriptionPt' => 'Description (PT)',
        'referenceDocumentGUID' => 'Reference document',
        'constructionObjectGUID' => 'Object Type',
        'relationToOtherDataDictionaries' => 'Mapping (relation to other data dictionaries)',
    ];

    /** Columns that are the element's name/description — surfaced outside the expander. */
    private const PRIMARY = [
        'constructionObjectNameEn', 'constructionObjectNamePt',
        'pdtNameEn', 'pdtNamePt', 'gopNameEn', 'gopNamePt',
        'nameEnSc', 'namePtSc', 'nameEn', 'namePt',
        'descriptionEn', 'descriptionPt', 'definitionEn', 'definitionPt',
    ];

    /** @var array<string,array> per-table descriptor cache */
    private array $cache = [];

    /**
     * Full ordered attribute descriptors for a table.
     *
     * @return array<int,array{name:string,label:string,type:string,inputKind:string,
     *   enum:?array,system:bool,mandatory:bool,mandatoryReason:?string,primary:bool}>
     */
    public function describe(string $table): array
    {
        if (isset($this->cache[$table])) {
            return $this->cache[$table];
        }
        if (!in_array($table, self::TABLES, true)) {
            throw new \InvalidArgumentException("Unknown table '{$table}'.");
        }

        $rule = self::RULE_MANDATORY[$table] ?? [];
        $out = [];
        foreach (DB::select("SHOW COLUMNS FROM `{$table}`") as $c) {
            $name = $c->Field;
            $system = in_array($name, self::SYSTEM, true);
            $isAuto = Str::contains((string) $c->Extra, 'auto_increment');
            $dbMandatory = ($c->Null === 'NO' && $c->Default === null && !$isAuto);
            $ruleMandatory = in_array($name, $rule, true);

            // User must fill it only when it is NOT auto-managed.
            $userMandatory = !$system && ($dbMandatory || $ruleMandatory);

            $out[] = [
                'name'            => $name,
                'label'           => self::LABELS[$name] ?? Str::headline($name),
                'type'            => $c->Type,
                'inputKind'       => $this->inputKind($c->Type, $name),
                'enum'            => $this->parseEnum($c->Type),
                'system'          => $system,
                'mandatory'       => $userMandatory,
                'mandatoryReason' => $userMandatory ? ($dbMandatory ? 'db' : 'rule') : null,
                'primary'         => in_array($name, self::PRIMARY, true),
            ];
        }

        return $this->cache[$table] = $out;
    }

    /** Names of the user-mandatory fields (the blocking set) for a table. */
    public function mandatory(string $table): array
    {
        return array_values(array_map(
            fn($f) => $f['name'],
            array_filter($this->describe($table), fn($f) => $f['mandatory'])
        ));
    }

    /** Names of the auto-managed system/lineage fields for a table. */
    public function systemFields(string $table): array
    {
        return array_values(array_map(
            fn($f) => $f['name'],
            array_filter($this->describe($table), fn($f) => $f['system'])
        ));
    }

    /** Names of the user-editable (non-system) fields for a table. */
    public function editable(string $table): array
    {
        return array_values(array_map(
            fn($f) => $f['name'],
            array_filter($this->describe($table), fn($f) => !$f['system'])
        ));
    }

    /**
     * Validate a value bag against the user-mandatory set. Returns the list of missing
     * mandatory field names (empty = ok). Server-side guard mirrored by the UI.
     */
    public function missingMandatory(string $table, array $values): array
    {
        $missing = [];
        foreach ($this->mandatory($table) as $name) {
            $v = $values[$name] ?? null;
            if ($v === null || (is_string($v) && trim($v) === '')) {
                $missing[] = $name;
            }
        }
        return $missing;
    }

    private function inputKind(string $type, string $name): string
    {
        $t = strtolower($type);
        if (Str::startsWith($t, 'enum(')) {
            return 'select';
        }
        if (Str::startsWith($t, ['int', 'bigint', 'smallint', 'tinyint', 'decimal', 'float', 'double'])) {
            return 'int';
        }
        if (Str::startsWith($t, ['date', 'datetime', 'timestamp'])) {
            return 'date';
        }
        if (Str::startsWith($t, 'text') || Str::contains(strtolower($name), ['description', 'definition'])) {
            return 'textarea';
        }
        return 'text';
    }

    /** Parse enum('a','b',...) into ['a','b',...] or null. */
    private function parseEnum(string $type): ?array
    {
        if (!Str::startsWith(strtolower($type), 'enum(')) {
            return null;
        }
        preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $type, $m);
        return array_map(fn($v) => str_replace("\\'", "'", $v), $m[1] ?? []);
    }
}
