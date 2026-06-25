<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Generic self-referential structural relationship (EN ISO 23387:2025 R-23387-7).
 * See migration 2026_06_25_120000_create_entity_relationships_table for the model rationale.
 *
 * Relations are keyed by GUID lineage (sourceGuid / targetGuid), never row Id.
 */
class EntityRelationship extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'entity_relationships';

    // Entity-type tokens. 'objecttype' aligns with EN ISO 23387:2025 terminology
    // (the underlying table is still `constructionobjects` — terminology-only rename).
    public const TYPE_PDT        = 'pdt';
    public const TYPE_GOP        = 'gop';
    public const TYPE_PROPERTY   = 'property';
    public const TYPE_OBJECTTYPE = 'objecttype';

    public const ENTITY_TYPES = [
        self::TYPE_PDT, self::TYPE_GOP, self::TYPE_PROPERTY, self::TYPE_OBJECTTYPE,
    ];

    // Relation types. IsSubtypeOf (pdt/gop/objecttype) and IsSpecializationOf (property)
    // are the 0..1 subtype relations; HasPart is 0..*. Stored as VARCHAR (not a DB enum)
    // so future types (e.g. IsDependentOn, R-23387-8) need no migration; valid values are
    // gated in RelationshipService::assertRelationType().
    public const REL_HAS_PART          = 'HasPart';
    public const REL_IS_SUBTYPE_OF     = 'IsSubtypeOf';
    public const REL_IS_SPECIALIZATION = 'IsSpecializationOf';

    /** Relation types that are max-one (0..1) per source. */
    public const SINGLE_PARENT_RELATIONS = [
        self::REL_IS_SUBTYPE_OF, self::REL_IS_SPECIALIZATION,
    ];

    protected $fillable = [
        'sourceEntityType', 'sourceGuid', 'relationType',
        'targetEntityType', 'targetGuid',
        'targetVersionNumber', 'targetRevisionNumber',
        'position', 'note',
    ];

    protected $casts = [
        'targetVersionNumber'  => 'integer',
        'targetRevisionNumber' => 'integer',
        'position'             => 'integer',
    ];
}
