<?php

namespace App\Services;

use App\Models\PropertyDependency;
use App\Models\PropertyDependencyTarget;
use Illuminate\Support\Facades\DB;

/**
 * CRUD + integrity + resolution for property dependencies (EN ISO 23387:2025 R-23387-8).
 * Mirrors RelationshipService's guard philosophy. Identity is GUID lineage.
 *
 * Nesting (4.4.6): a dependency target may itself be a dependent property; nesting is
 * represented by CHAINING (target -> its own dependencies). resolveChain() walks that
 * chain transitively with a cycle guard. Combination/evaluation of the chain is EVALUATION
 * (out of scope) — this layer represents and resolves structure, it does not compute.
 */
class PropertyDependencyService
{
    private const DICT = 'propertiesdatadictionaries';

    /**
     * Create a dependency for a source property.
     * @param array $targets list of ['guid'=>..,'isPreferred'=>?bool,'targetVersionNumber'=>?int,'targetRevisionNumber'=>?int]
     */
    public function addDependency(
        string $sourcePropertyGuid,
        string $dependencyKind,
        array $targets,
        ?string $expression = null,
        ?string $note = null
    ): PropertyDependency {
        if (!in_array($dependencyKind, PropertyDependency::KINDS, true)) {
            throw new RelationshipException("Unknown dependencyKind '{$dependencyKind}'.");
        }
        $this->assertPropertyExists($sourcePropertyGuid);

        $targets = array_values(array_filter($targets, fn($t) => !empty($t['guid'])));
        if (empty($targets)) {
            throw new RelationshipException('A dependency needs at least one target property.');
        }
        if ($dependencyKind === PropertyDependency::KIND_REFERENCE_PROXY && count($targets) > 1) {
            throw new RelationshipException('reference_proxy takes exactly one target.');
        }

        foreach ($targets as $t) {
            if ($t['guid'] === $sourcePropertyGuid) {
                throw new RelationshipException('A property cannot depend on itself.');
            }
            $this->assertPropertyExists($t['guid']);
            // Cycle guard: the target chain must not lead back to the source.
            if ($this->chainReaches($t['guid'], $sourcePropertyGuid)) {
                throw new RelationshipException(
                    "Adding this dependency would create a cycle ({$sourcePropertyGuid} <-> {$t['guid']})."
                );
            }
        }

        return DB::transaction(function () use ($sourcePropertyGuid, $dependencyKind, $expression, $note, $targets) {
            $dep = PropertyDependency::create([
                'sourcePropertyGuid' => $sourcePropertyGuid,
                'dependencyKind'     => $dependencyKind,
                'expression'         => $dependencyKind === PropertyDependency::KIND_FUNCTION ? $expression : null,
                'note'               => $note,
            ]);
            $pos = 0;
            foreach ($targets as $t) {
                PropertyDependencyTarget::create([
                    'dependencyId'         => $dep->id,
                    'targetPropertyGuid'   => $t['guid'],
                    'isPreferred'          => $t['isPreferred'] ?? null,
                    'position'             => $t['position'] ?? $pos++,
                    'targetVersionNumber'  => $t['targetVersionNumber'] ?? null,
                    'targetRevisionNumber' => $t['targetRevisionNumber'] ?? null,
                ]);
            }
            return $dep->load('targets');
        });
    }

    /** Soft-delete a dependency; its target rows are removed (cascade). */
    public function removeDependency(int $id): void
    {
        $dep = PropertyDependency::find($id);
        if (!$dep) return;
        DB::transaction(function () use ($dep) {
            PropertyDependencyTarget::where('dependencyId', $dep->id)->delete();
            $dep->delete(); // soft-delete header
        });
    }

    /** Live dependencies (with targets) declared directly on a source property. */
    public function dependenciesFor(string $sourcePropertyGuid)
    {
        return PropertyDependency::with('targets')
            ->where('sourcePropertyGuid', $sourcePropertyGuid)
            ->get();
    }

    /**
     * Resolve a property's dependencies transitively (4.4.6 nesting): each dependency's
     * targets are themselves resolved if they have their own dependencies. Cycle-guarded.
     * Returns a nested array structure (representation only — no evaluation).
     */
    public function resolveChain(string $sourcePropertyGuid, array $seen = []): array
    {
        if (isset($seen[$sourcePropertyGuid])) {
            return []; // cycle guard
        }
        $seen[$sourcePropertyGuid] = true;

        $out = [];
        foreach ($this->dependenciesFor($sourcePropertyGuid) as $dep) {
            $targets = [];
            foreach ($dep->targets as $t) {
                $targets[] = [
                    'guid'        => $t->targetPropertyGuid,
                    'isPreferred' => $t->isPreferred,
                    'position'    => $t->position,
                    // Nested: the target's own dependencies, resolved.
                    'dependsOn'   => $this->resolveChain($t->targetPropertyGuid, $seen),
                ];
            }
            $out[] = [
                'kind'       => $dep->dependencyKind,
                'expression' => $dep->expression,
                'targets'    => $targets,
            ];
        }
        return $out;
    }

    // ----------------------------------------------------------- internals

    private function assertPropertyExists(string $guid): void
    {
        if (!DB::table(self::DICT)->where('GUID', $guid)->exists()) {
            throw new RelationshipException("No property lineage with GUID '{$guid}' exists.");
        }
    }

    /**
     * Does the dependency chain starting at $fromGuid reach $goalGuid by following
     * dependency targets transitively? (DFS, cycle-safe.)
     */
    private function chainReaches(string $fromGuid, string $goalGuid): bool
    {
        $stack = [$fromGuid];
        $seen = [];
        while ($stack) {
            $g = array_pop($stack);
            if (isset($seen[$g])) continue;
            $seen[$g] = true;
            if ($g === $goalGuid) return true;
            $targetGuids = DB::table('property_dependency_targets as t')
                ->join('property_dependencies as d', 'd.id', '=', 't.dependencyId')
                ->whereNull('d.deleted_at')
                ->where('d.sourcePropertyGuid', $g)
                ->pluck('t.targetPropertyGuid');
            foreach ($targetGuids as $tg) {
                $stack[] = $tg;
            }
        }
        return false;
    }
}
