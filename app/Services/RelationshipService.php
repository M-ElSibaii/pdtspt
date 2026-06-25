<?php

namespace App\Services;

use App\Models\EntityRelationship;
use Illuminate\Support\Facades\DB;

/**
 * CRUD + integrity for generic self-referential structural relationships
 * (EN ISO 23387:2025 R-23387-7). See EntityRelationship / the create migration.
 *
 * Identity is GUID-lineage based. Guards enforced here (not in the DB) because they
 * are graph-shaped (cardinality + cycles) and MySQL can't express them cleanly:
 *   - IsSubtypeOf / IsSpecializationOf are 0..1 per source.
 *   - HasPart is 0..*.
 *   - No self relation; no cycle within a relationType graph.
 *   - Both endpoints must reference an existing GUID lineage.
 */
class RelationshipService
{
    /** entity-type token => [table, guid column]. */
    private const ENTITY_TABLE = [
        EntityRelationship::TYPE_PDT        => ['productdatatemplates', 'GUID'],
        EntityRelationship::TYPE_GOP        => ['groupofproperties', 'GUID'],
        EntityRelationship::TYPE_PROPERTY   => ['propertiesdatadictionaries', 'GUID'],
        EntityRelationship::TYPE_OBJECTTYPE => ['constructionobjects', 'GUID'],
    ];

    /**
     * Create a relationship after validating all constraints.
     * Idempotent: returns the existing (non-deleted) edge if it already exists.
     */
    public function relate(
        string $sourceEntityType,
        string $sourceGuid,
        string $relationType,
        string $targetEntityType,
        string $targetGuid,
        ?int $targetVersionNumber = null,
        ?int $targetRevisionNumber = null,
        ?int $position = null,
        ?string $note = null
    ): EntityRelationship {
        $this->assertEntityType($sourceEntityType);
        $this->assertEntityType($targetEntityType);
        $this->assertRelationType($relationType);

        // No self relation (same entity type + same lineage).
        if ($sourceEntityType === $targetEntityType && $sourceGuid === $targetGuid) {
            throw new RelationshipException(
                "An entity cannot relate to itself ({$sourceEntityType}:{$sourceGuid})."
            );
        }

        $this->assertLineageExists($sourceEntityType, $sourceGuid);
        $this->assertLineageExists($targetEntityType, $targetGuid);

        // Idempotency: reuse an existing live edge.
        $existing = EntityRelationship::where([
            'sourceEntityType' => $sourceEntityType,
            'sourceGuid'       => $sourceGuid,
            'relationType'     => $relationType,
            'targetEntityType' => $targetEntityType,
            'targetGuid'       => $targetGuid,
        ])->first();
        if ($existing) {
            return $existing;
        }

        // 0..1 cardinality for subtype/specialization.
        if (in_array($relationType, EntityRelationship::SINGLE_PARENT_RELATIONS, true)) {
            $count = EntityRelationship::where([
                'sourceEntityType' => $sourceEntityType,
                'sourceGuid'       => $sourceGuid,
                'relationType'     => $relationType,
            ])->count();
            if ($count > 0) {
                throw new RelationshipException(
                    "{$relationType} is 0..1: {$sourceEntityType}:{$sourceGuid} already has one."
                );
            }
        }

        // Cycle prevention within this relationType graph: target must not already
        // reach source (adding source->target would then close a loop).
        if ($this->reaches($relationType, $targetEntityType, $targetGuid, $sourceEntityType, $sourceGuid)) {
            throw new RelationshipException(
                "Adding {$relationType} {$sourceEntityType}:{$sourceGuid} -> "
                . "{$targetEntityType}:{$targetGuid} would create a cycle."
            );
        }

        return EntityRelationship::create([
            'sourceEntityType'     => $sourceEntityType,
            'sourceGuid'           => $sourceGuid,
            'relationType'         => $relationType,
            'targetEntityType'     => $targetEntityType,
            'targetGuid'           => $targetGuid,
            'targetVersionNumber'  => $targetVersionNumber,
            'targetRevisionNumber' => $targetRevisionNumber,
            'position'             => $position,
            'note'                 => $note,
        ]);
    }

    /** Soft-delete a relationship by id. */
    public function unrelate(int $id): void
    {
        $rel = EntityRelationship::find($id);
        if ($rel) {
            $rel->delete();
        }
    }

    /** All live relations originating from a source lineage (optionally one type). */
    public function relationsFrom(string $sourceEntityType, string $sourceGuid, ?string $relationType = null)
    {
        $q = EntityRelationship::where('sourceEntityType', $sourceEntityType)
            ->where('sourceGuid', $sourceGuid);
        if ($relationType !== null) {
            $q->where('relationType', $relationType);
        }
        return $q->orderByRaw('position IS NULL, position')->get();
    }

    /** The single subtype/specialization parent of a source, or null. */
    public function parentOf(string $sourceEntityType, string $sourceGuid): ?EntityRelationship
    {
        return EntityRelationship::where('sourceEntityType', $sourceEntityType)
            ->where('sourceGuid', $sourceGuid)
            ->whereIn('relationType', EntityRelationship::SINGLE_PARENT_RELATIONS)
            ->first();
    }

    // ---------------------------------------------------------------- internals

    private function assertEntityType(string $t): void
    {
        if (!in_array($t, EntityRelationship::ENTITY_TYPES, true)) {
            throw new RelationshipException("Unknown entity type '{$t}'.");
        }
    }

    private function assertRelationType(string $t): void
    {
        $valid = [
            EntityRelationship::REL_HAS_PART,
            EntityRelationship::REL_IS_SUBTYPE_OF,
            EntityRelationship::REL_IS_SPECIALIZATION,
        ];
        if (!in_array($t, $valid, true)) {
            throw new RelationshipException("Unknown relation type '{$t}'.");
        }
    }

    private function assertLineageExists(string $entityType, string $guid): void
    {
        [$table, $col] = self::ENTITY_TABLE[$entityType];
        $exists = DB::table($table)->where($col, $guid)->exists();
        if (!$exists) {
            throw new RelationshipException(
                "No {$entityType} lineage with GUID '{$guid}' exists in {$table}."
            );
        }
    }

    /**
     * Does ($fromType:$fromGuid) reach ($goalType:$goalGuid) by following
     * $relationType edges (source -> target)? DFS over live edges.
     */
    private function reaches(
        string $relationType,
        string $fromType,
        string $fromGuid,
        string $goalType,
        string $goalGuid
    ): bool {
        $stack = [[$fromType, $fromGuid]];
        $seen = [];
        while ($stack) {
            [$t, $g] = array_pop($stack);
            $key = $t . ':' . $g;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            if ($t === $goalType && $g === $goalGuid) {
                return true;
            }
            $edges = EntityRelationship::where('relationType', $relationType)
                ->where('sourceEntityType', $t)
                ->where('sourceGuid', $g)
                ->get(['targetEntityType', 'targetGuid']);
            foreach ($edges as $e) {
                $stack[] = [$e->targetEntityType, $e->targetGuid];
            }
        }
        return false;
    }
}
