<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Preview-mode workflow: a PDT (and its GOP/property rows) with status = 'Preview'
 * is a free-editing draft. NO versioning, NO cascade, NO deprecation — edits write
 * in place; real hard-delete is allowed under a strict shared-ownership gate.
 *
 * Every action here is gated on status = 'Preview'. Active PDTs use the Phase 1
 * versioning engine ({@see VersioningService}); their Preview buttons are disabled.
 *
 * At PUBLISH (Preview -> Active) any element whose context diverged from a live
 * Active element is surfaced for a per-element decision — no silent auto-versioning.
 *
 * Field-locks: the same whitelists the existing admin editors enforce are applied
 * here, so fields the admin cannot edit stay non-editable even in Preview.
 */
class PreviewService
{
    private const DICT = 'propertiesdatadictionaries';
    private const GOP  = 'groupofproperties';
    private const PROP = 'properties';
    private const PDT  = 'productdatatemplates';

    public const ST_ACTIVE   = 'Active';
    public const ST_INACTIVE = 'InActive';
    public const ST_PREVIEW  = 'Preview';

    public function __construct(private SchemaAttributeService $schema)
    {
    }

    /**
     * Editable (non-locked) attribute set for a table = its non-system fields, read live
     * from the schema. This is the field-lock: system/lineage fields (GUID, version,
     * dates, status, parent FKs) are never in the editable set, so they can't be written
     * even if posted. Replaces the former hardcoded per-level whitelists so Preview edits
     * cover the FULL attribute model.
     */
    private function editableOf(string $table): array
    {
        return $this->schema->editable($table);
    }

    // ============================================================ guards

    public function assertPreviewPdt(int $pdtId): object
    {
        $pdt = DB::table(self::PDT)->where('Id', $pdtId)->first();
        if (!$pdt) {
            throw new \RuntimeException("PDT Id {$pdtId} not found.");
        }
        if ($pdt->status !== self::ST_PREVIEW) {
            throw new \RuntimeException("PDT Id {$pdtId} is '{$pdt->status}', not Preview — use the versioning flow.");
        }
        return $pdt;
    }

    private function gopPreviewPdt(int $gopId): object
    {
        $gop = DB::table(self::GOP)->where('Id', $gopId)->first();
        if (!$gop) {
            throw new \RuntimeException("GOP Id {$gopId} not found.");
        }
        $this->assertPreviewPdt((int) $gop->pdtId);
        return $gop;
    }

    private function only(array $attrs, array $whitelist): array
    {
        return array_intersect_key($attrs, array_flip($whitelist));
    }

    /**
     * Reject controlled-vocabulary dictionary values outside the bSDD enums. Blank is
     * always allowed (units in particular is optional). propertyValueKind has no DB column,
     * so it is not validated here.
     */
    private function assertDictEnums(array $values): void
    {
        foreach (['dataType', 'units'] as $field) {
            if (array_key_exists($field, $values) && !BsddEnums::isValid($field, $values[$field])) {
                throw new \RuntimeException("'{$values[$field]}' is not a valid {$field} (must be a bSDD {$field} value).");
            }
        }
    }

    // ============================================================ free edit (in place)

    public function editPdtAttributes(int $pdtId, array $attrs): void
    {
        $this->assertPreviewPdt($pdtId);
        $set = $this->only($attrs, $this->editableOf(self::PDT));
        if ($set) {
            DB::table(self::PDT)->where('Id', $pdtId)->update($set);
        }
    }

    public function editGopAttributes(int $gopId, array $attrs): void
    {
        $this->gopPreviewPdt($gopId);
        $set = $this->only($attrs, $this->editableOf(self::GOP));
        if ($set) {
            DB::table(self::GOP)->where('Id', $gopId)->update($set);
        }
    }

    public function editContext(int $contextId, array $attrs): void
    {
        $ctx = DB::table(self::PROP)->where('Id', $contextId)->first();
        if (!$ctx) {
            throw new \RuntimeException("Context row Id {$contextId} not found.");
        }
        $this->assertPreviewPdt((int) $ctx->pdtID);
        $set = $this->only($attrs, $this->editableOf(self::PROP));
        if ($set) {
            DB::table(self::PROP)->where('Id', $contextId)->update($set);
        }
    }

    // ============================================================ edit dictionary definition (fork-on-shared)

    /**
     * Edit the DICTIONARY definition of a property referenced by a Preview context row.
     *
     * Fork-on-edit rule (semantic, not just UI wiring): if the referenced dictionary row
     * is SHARED — any PDT other than this Preview PDT points at the exact same dict row —
     * the edit is applied to a NEW Preview fork (same GUID, Preview status) and THIS PDT's
     * context is re-pointed at the fork. The shared/Active definition is never mutated, so
     * the other PDT keeps showing the original. If the row is owned solely by this Preview
     * PDT (a brand-new property, or a fork already created earlier this session), it is
     * edited in place.
     *
     * The fork produced here is exactly what {@see planPublish}/{@see applyPublish}
     * consume: a Preview dict row whose GUID has an Active sibling. Editing the same shared
     * property twice is safe: after the first edit the context points at its own
     * Preview-owned fork, so the second edit takes the in-place branch (no double fork).
     *
     * @return array ['forked'=>bool, 'dictId'=>int, 'contextId'=>int]
     */
    public function editPreviewProperty(int $contextId, array $dictValues): array
    {
        $ctx = DB::table(self::PROP)->where('Id', $contextId)->first();
        if (!$ctx) {
            throw new \RuntimeException("Context row Id {$contextId} not found.");
        }
        $this->assertPreviewPdt((int) $ctx->pdtID);
        $this->assertDictEnums($dictValues);

        $shared = DB::table(self::PROP)
            ->where('propertyId', $ctx->propertyId)
            ->where('pdtID', '<>', $ctx->pdtID)
            ->exists();

        if ($shared) {
            return $this->forkPropertyForEdit($contextId, $dictValues);
        }

        // Owned solely by this Preview PDT -> edit the dictionary row in place.
        $set = $this->only($dictValues, $this->editableOf(self::DICT));
        if ($set) {
            DB::table(self::DICT)->where('Id', $ctx->propertyId)->update($set);
        }
        return ['forked' => false, 'dictId' => (int) $ctx->propertyId, 'contextId' => $contextId];
    }

    /**
     * Create a Preview fork of the dictionary row referenced by $contextId (clone, same
     * GUID, status Preview), apply the edited values to the fork, and re-point this PDT's
     * context at it. The original (shared/Active) dictionary row is left completely
     * untouched. Forced fork — callers that have already determined the property is shared
     * may call this directly; {@see editPreviewProperty} is the normal entry point.
     *
     * @return array ['forked'=>true, 'dictId'=>int, 'contextId'=>int]
     */
    public function forkPropertyForEdit(int $contextId, array $dictValues): array
    {
        $ctx = DB::table(self::PROP)->where('Id', $contextId)->first();
        if (!$ctx) {
            throw new \RuntimeException("Context row Id {$contextId} not found.");
        }
        $this->assertPreviewPdt((int) $ctx->pdtID);
        $this->assertDictEnums($dictValues);

        $source = DB::table(self::DICT)->where('Id', $ctx->propertyId)->first();
        if (!$source) {
            throw new \RuntimeException("Dictionary property Id {$ctx->propertyId} not found.");
        }

        return DB::transaction(function () use ($ctx, $source, $contextId, $dictValues) {
            $clone = (array) $source;
            unset($clone['Id']);
            $clone['status'] = self::ST_PREVIEW;   // fork is a Preview draft
            $clone['GUID']   = $source->GUID;       // same lineage as the Active original
            // A fresh fork carries no deprecation/lineage pointers yet; publish sets them.
            $clone['depreciationDate'] = null;
            $clone['depreciationExplanation'] = null;
            $clone = array_merge($clone, $this->only($dictValues, $this->editableOf(self::DICT)));

            $forkId = DB::table(self::DICT)->insertGetId($clone, 'Id');

            // Re-point THIS PDT's context at the fork (GUID unchanged).
            DB::table(self::PROP)->where('Id', $contextId)
                ->update(['propertyId' => $forkId, 'GUID' => $source->GUID]);

            return ['forked' => true, 'dictId' => $forkId, 'contextId' => $contextId];
        });
    }

    // ============================================================ free edit (in place) — structural

    /**
     * Add a brand-new, EMPTY group of properties to the Preview PDT. Always a fresh
     * independent lineage: new GUID (never reused from any existing GOP), new Id, fresh
     * dates, v1.0, status Preview, and NO properties copied. The full non-system attribute
     * set is accepted (e.g. names + definition pre-filled from a name-dropdown shortcut on
     * the client); the structural copy / fromGopId path is intentionally gone.
     */
    public function addGop(int $pdtId, array $attrs = [], ?string $guid = null): int
    {
        $this->assertPreviewPdt($pdtId);
        $guid = GuidGenerator::normalize($guid) ?? GuidGenerator::generateUnique();
        $today = Carbon::today()->toDateString();
        $row = array_merge($this->only($attrs, $this->editableOf(self::GOP)), [
            'GUID' => $guid, 'pdtId' => $pdtId, 'status' => self::ST_PREVIEW,
            'versionNumber' => 1, 'revisionNumber' => 0,
            'gopNameEn' => $attrs['gopNameEn'] ?? 'New group',
            'gopNamePt' => $attrs['gopNamePt'] ?? 'Novo grupo',
            'dateOfRevision' => $today, 'dateOfVersion' => $today, 'created_at' => $today, 'updated_at' => $today,
            'depreciationDate' => null, 'depreciationExplanation' => null,
        ]);
        return DB::table(self::GOP)->insertGetId($row, 'Id');
    }

    /**
     * Distinct existing GOP names with the definition of their latest instance — powers
     * the "name dropdown shortcut" that pre-fills (name + definition) a new GOP. Purely a
     * convenience default; the created GOP is still fresh and independent.
     */
    public function gopNameSuggestions(): array
    {
        return DB::table(self::GOP)
            ->orderByDesc('versionNumber')->orderByDesc('revisionNumber')->orderByDesc('Id')
            ->get(['gopNameEn', 'gopNamePt', 'definitionEn', 'definitionPt'])
            ->groupBy('gopNameEn')->map->first()->values()
            ->sortBy('gopNameEn')->values()->all();
    }

    /** Add an existing dictionary property as a context row under a Preview GOP. */
    public function addExistingProperty(int $gopId, int $dictId): int
    {
        $gop = $this->gopPreviewPdt($gopId);
        $d = DB::table(self::DICT)->where('Id', $dictId)->first();
        if (!$d) {
            throw new \RuntimeException("Dictionary property Id {$dictId} not found.");
        }
        return DB::table(self::PROP)->insertGetId([
            'GUID' => $d->GUID, 'propertyId' => $d->Id, 'gopID' => $gopId, 'pdtID' => $gop->pdtId,
            'descriptionEn' => $d->definitionEn ?? '', 'descriptionPt' => $d->definitionPt ?? '',
            'referenceDocumentGUID' => 'n/a',
        ], 'Id');
    }

    /**
     * Create a brand-new dictionary property from scratch (fresh GUID, v1.0, Active)
     * and add its context row under a Preview GOP. Manual GUID override allowed.
     *
     * @return array ['dictId'=>int, 'contextId'=>int, 'guid'=>string]
     */
    public function addNewProperty(int $gopId, array $values, ?string $guid = null): array
    {
        $gop = $this->gopPreviewPdt($gopId);
        $this->assertDictEnums($values);
        $guid = GuidGenerator::normalize($guid) ?? GuidGenerator::generateUnique();
        if (GuidGenerator::existsAnywhere($guid)) {
            throw new \RuntimeException("GUID {$guid} already exists — a new property needs a unique GUID.");
        }
        $dictVals = app(VersioningService::class)->buildNewDictValues($this->only($values, $this->editableOf(self::DICT)) + [
            'nameEn' => $values['nameEn'] ?? null, 'namePt' => $values['namePt'] ?? null,
            'definitionEn' => $values['definitionEn'] ?? null, 'definitionPt' => $values['definitionPt'] ?? null,
        ], $guid);

        return DB::transaction(function () use ($dictVals, $gop, $gopId, $guid) {
            $dictId = DB::table(self::DICT)->insertGetId($dictVals, 'Id');
            $contextId = DB::table(self::PROP)->insertGetId([
                'GUID' => $guid, 'propertyId' => $dictId, 'gopID' => $gopId, 'pdtID' => $gop->pdtId,
                'descriptionEn' => $dictVals['definitionEn'], 'descriptionPt' => $dictVals['definitionPt'],
                'referenceDocumentGUID' => 'n/a',
            ], 'Id');
            return ['dictId' => $dictId, 'contextId' => $contextId, 'guid' => $guid];
        });
    }

    public function removeProperty(int $contextId): void
    {
        $ctx = DB::table(self::PROP)->where('Id', $contextId)->first();
        if (!$ctx) {
            return;
        }
        $this->assertPreviewPdt((int) $ctx->pdtID);
        DB::table(self::PROP)->where('Id', $contextId)->delete();
    }

    public function removeGop(int $gopId): void
    {
        $this->gopPreviewPdt($gopId);
        DB::transaction(function () use ($gopId) {
            DB::table(self::PROP)->where('gopID', $gopId)->delete();
            DB::table(self::GOP)->where('Id', $gopId)->delete();
        });
    }

    // ============================================================ hard delete (gated)

    /**
     * Plan a hard delete of a Preview PDT. Determines exactly what is removed (rows
     * owned solely by this Preview PDT) vs detached/kept (shared with other PDTs).
     * Writes nothing.
     */
    public function planPreviewDelete(int $pdtId): array
    {
        $pdt = $this->assertPreviewPdt($pdtId);

        $gopIds = DB::table(self::GOP)->where('pdtId', $pdtId)->pluck('Id')->all();
        $ctx    = DB::table(self::PROP)->where('pdtID', $pdtId)->get();

        // Dictionary properties this PDT references; decide delete vs keep-shared.
        $dictDelete = [];
        $dictKeptShared = [];
        foreach ($ctx->pluck('propertyId')->unique()->filter() as $dictId) {
            $otherRefs = DB::table(self::PROP)
                ->where('propertyId', $dictId)->where('pdtID', '<>', $pdtId)->count();
            $d = DB::table(self::DICT)->where('Id', $dictId)->first();
            if (!$d) {
                continue;
            }
            if ($otherRefs > 0) {
                $sharingPdts = DB::table(self::PROP)->where('propertyId', $dictId)->where('pdtID', '<>', $pdtId)
                    ->distinct()->pluck('pdtID')->all();
                $dictKeptShared[] = ['id' => $dictId, 'nameEn' => $d->nameEn, 'sharedWithPdtIds' => $sharingPdts];
            } else {
                // Solely owned here. Also require the whole GUID lineage to be unreferenced elsewhere.
                $lineageOtherRefs = DB::table(self::PROP . ' as p')
                    ->join(self::DICT . ' as d', 'd.Id', '=', 'p.propertyId')
                    ->where('d.GUID', $d->GUID)->where('p.pdtID', '<>', $pdtId)->count();
                if ($lineageOtherRefs === 0) {
                    $dictDelete[] = ['id' => $dictId, 'nameEn' => $d->nameEn, 'guid' => $d->GUID];
                } else {
                    $dictKeptShared[] = ['id' => $dictId, 'nameEn' => $d->nameEn, 'sharedWithPdtIds' => ['(other version in use)']];
                }
            }
        }

        $summary = [
            "HARD-DELETE Preview PDT '{$pdt->pdtNameEn}' Id={$pdtId}",
            "  + {$ctx->count()} context row(s) (owned by this PDT)",
            "  + " . count($gopIds) . " GOP row(s) (owned by this PDT)",
            "  + " . count($dictDelete) . " dictionary property row(s) solely owned here",
        ];
        foreach ($dictDelete as $d) {
            $summary[] = "      delete dict '{$d['nameEn']}' (Id={$d['id']})";
        }
        if ($dictKeptShared) {
            $summary[] = "  KEPT (shared — detached only):";
            foreach ($dictKeptShared as $d) {
                $summary[] = "      keep dict '{$d['nameEn']}' (Id={$d['id']}) — used by PDT(s) " . implode(',', $d['sharedWithPdtIds']);
            }
        }

        return [
            'pdtId'           => $pdtId,
            'pdtName'         => $pdt->pdtNameEn,
            'deleteContextIds' => $ctx->pluck('Id')->all(),
            'deleteGopIds'    => $gopIds,
            'deleteDictIds'   => array_column($dictDelete, 'id'),
            'keptShared'      => $dictKeptShared,
            'summary'         => $summary,
        ];
    }

    /**
     * Apply a hard delete: JSON backup first, then a single transaction. Deletes in
     * FK-safe order (context -> GOPs -> PDT -> solely-owned dict rows). Never touches
     * shared dictionary rows or other PDTs.
     */
    public function applyPreviewDelete(int $pdtId): array
    {
        $plan = $this->planPreviewDelete($pdtId);

        $backup = [
            'generated_at' => now()->toIso8601String(),
            'summary'      => $plan['summary'],
            'pdt'          => DB::table(self::PDT)->where('Id', $pdtId)->first(),
            'gops'         => DB::table(self::GOP)->whereIn('Id', $plan['deleteGopIds'] ?: [0])->get(),
            'context'      => DB::table(self::PROP)->whereIn('Id', $plan['deleteContextIds'] ?: [0])->get(),
            'dict'         => DB::table(self::DICT)->whereIn('Id', $plan['deleteDictIds'] ?: [0])->get(),
        ];
        $path = storage_path('app/preview_delete_backup_' . now()->format('Ymd_His') . '.json');
        file_put_contents($path, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        DB::transaction(function () use ($plan, $pdtId) {
            DB::table(self::PROP)->whereIn('Id', $plan['deleteContextIds'] ?: [0])->delete();
            DB::table(self::GOP)->whereIn('Id', $plan['deleteGopIds'] ?: [0])->delete();
            DB::table(self::PDT)->where('Id', $pdtId)->delete();
            if (!empty($plan['deleteDictIds'])) {
                DB::table(self::DICT)->whereIn('Id', $plan['deleteDictIds'])->delete();
            }
        });

        return [
            'deleted' => [
                'pdt' => 1, 'gops' => count($plan['deleteGopIds']),
                'context' => count($plan['deleteContextIds']), 'dict' => count($plan['deleteDictIds']),
            ],
            'keptShared' => $plan['keptShared'],
            'backup'     => basename($path),
            'summary'    => $plan['summary'],
        ];
    }

    // ============================================================ publish

    /**
     * Plan publishing a Preview PDT -> Active. Detects DIVERGENCE: context rows that
     * reference a Preview-forked dictionary property (same GUID as an Active row but a
     * Preview-status edited copy). Each divergence is returned for a per-element
     * decision; nothing is auto-versioned. Writes nothing.
     */
    public function planPublish(int $pdtId): array
    {
        $pdt = $this->assertPreviewPdt($pdtId);
        $ctx = DB::table(self::PROP)->where('pdtID', $pdtId)->get();

        $divergences = [];
        foreach ($ctx as $c) {
            $d = DB::table(self::DICT)->where('Id', $c->propertyId)->first();
            if (!$d || $d->status !== self::ST_PREVIEW) {
                continue; // only Preview-status dict rows can be forks/drafts
            }
            // Is there an Active row in the same GUID lineage? -> this is a fork of a live element.
            $activeSibling = DB::table(self::DICT)->where('GUID', $d->GUID)->where('status', self::ST_ACTIVE)
                ->orderByDesc('versionNumber')->orderByDesc('revisionNumber')->first();
            if ($activeSibling) {
                $divergences[] = [
                    'contextId'    => $c->Id,
                    'previewDictId' => $d->Id,
                    'activeDictId' => $activeSibling->Id,
                    'guid'         => $d->GUID,
                    'nameEn'       => $d->nameEn,
                    'old'          => ['nameEn' => $activeSibling->nameEn, 'definitionEn' => $activeSibling->definitionEn],
                    'new'          => ['nameEn' => $d->nameEn, 'definitionEn' => $d->definitionEn],
                ];
            }
        }

        $summary = ["PUBLISH Preview PDT '{$pdt->pdtNameEn}' Id={$pdtId} -> Active"];
        if ($divergences) {
            $summary[] = count($divergences) . " divergence(s) need a decision (version vs keep):";
            foreach ($divergences as $dv) {
                $summary[] = "   '{$dv['nameEn']}' (GUID {$dv['guid']}): old='{$dv['old']['nameEn']}' new='{$dv['new']['nameEn']}'";
            }
        } else {
            $summary[] = "No divergences — straight publish.";
        }

        return ['pdtId' => $pdtId, 'divergences' => $divergences, 'summary' => $summary];
    }

    /**
     * Apply publish. For each divergence, $decisions[contextId] is 'version' (spawn a
     * new version of the live element via the engine, adopting the Preview values) or
     * 'keep' (re-point the context to the existing Active element; discard the fork).
     * Flips the PDT, its GOPs, and its Preview-owned (non-fork) dict rows to Active.
     */
    public function applyPublish(int $pdtId, array $decisions = []): array
    {
        $plan = $this->planPublish($pdtId);
        $versioning = app(VersioningService::class);

        $backup = ['generated_at' => now()->toIso8601String(), 'summary' => $plan['summary'],
            'pdt' => DB::table(self::PDT)->where('Id', $pdtId)->first()];
        $path = storage_path('app/publish_backup_' . now()->format('Ymd_His') . '.json');
        file_put_contents($path, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $applied = [];
        DB::transaction(function () use ($plan, $pdtId, $decisions, $versioning, &$applied) {
            $forkContextIds = array_column($plan['divergences'], 'contextId');

            foreach ($plan['divergences'] as $dv) {
                $decision = $decisions[$dv['contextId']] ?? 'keep';
                if ($decision === 'version') {
                    // PROMOTE the Preview fork into a real new version of the live element:
                    // it already has the edited values and the same GUID, and this PDT's
                    // context already points at it — so we just renumber it Active and
                    // deprecate the old original per the use-gate. (No PDT clone: the PDT
                    // is being published, not versioned.)
                    $fork = DB::table(self::DICT)->where('Id', $dv['previewDictId'])->first();
                    $active = DB::table(self::DICT)->where('Id', $dv['activeDictId'])->first();
                    $forkVals = array_intersect_key((array) $fork, array_flip($this->editableOf(self::DICT)));
                    $bump = $versioning->decidePropertyBump($active, $forkVals);
                    $bump = $bump === 'version' ? 'version' : 'revision';
                    $head = DB::table(self::DICT)->where('GUID', $dv['guid'])->where('status', self::ST_ACTIVE)
                        ->orderByDesc('versionNumber')->orderByDesc('revisionNumber')->first();
                    $nv = $bump === 'version' ? (int) $head->versionNumber + 1 : (int) $head->versionNumber;
                    $nr = $bump === 'version' ? 0 : (int) $head->revisionNumber + 1;
                    $today = Carbon::today()->toDateString();
                    DB::table(self::DICT)->where('Id', $fork->Id)->update([
                        'versionNumber' => $nv, 'revisionNumber' => $nr, 'status' => self::ST_ACTIVE,
                        'listOfReplacedProperties' => "v{$active->versionNumber}.{$active->revisionNumber}",
                        'dateOfVersion' => $today, 'dateOfRevision' => $today,
                        'depreciationDate' => null, 'depreciationExplanation' => null,
                    ]);
                    // Use-gate: deprecate the old original only if no OTHER active PDT uses it.
                    $others = DB::table(self::PROP . ' as p')->join(self::PDT . ' as pdt', 'pdt.Id', '=', 'p.pdtID')
                        ->where('p.propertyId', $dv['activeDictId'])->where('pdt.status', self::ST_ACTIVE)
                        ->where('p.pdtID', '<>', $pdtId)->count();
                    if ($others === 0) {
                        DB::table(self::DICT)->where('Id', $dv['activeDictId'])->update([
                            'status' => self::ST_INACTIVE, 'depreciationDate' => $today,
                            'depreciationExplanation' => "Superseded by version {$nv}.{$nr}",
                            'listOfReplacingProperties' => "v{$nv}.{$nr}",
                        ]);
                    }
                    $applied[] = "versioned '{$dv['nameEn']}' -> v{$nv}.{$nr}";
                } else {
                    // Keep: point the context at the existing Active element; drop the fork.
                    DB::table(self::PROP)->where('Id', $dv['contextId'])
                        ->update(['propertyId' => $dv['activeDictId'], 'GUID' => $dv['guid']]);
                    if (DB::table(self::PROP)->where('propertyId', $dv['previewDictId'])->count() === 0) {
                        $this->deleteDictRowFkSafe((int) $dv['previewDictId']);
                    }
                    $applied[] = "kept existing '{$dv['nameEn']}'";
                }
            }

            // Flip the PDT + its GOPs to Active.
            DB::table(self::PDT)->where('Id', $pdtId)->update(['status' => self::ST_ACTIVE]);
            DB::table(self::GOP)->where('pdtId', $pdtId)->update(['status' => self::ST_ACTIVE]);

            // Preview-owned, non-fork dict rows referenced by this PDT become Active.
            $dictIds = DB::table(self::PROP)->where('pdtID', $pdtId)->pluck('propertyId')->unique()->filter();
            foreach ($dictIds as $did) {
                $d = DB::table(self::DICT)->where('Id', $did)->first();
                if ($d && $d->status === self::ST_PREVIEW) {
                    DB::table(self::DICT)->where('Id', $did)->update(['status' => self::ST_ACTIVE]);
                }
            }
        });

        return ['published' => $pdtId, 'actions' => $applied, 'backup' => basename($path), 'summary' => $plan['summary']];
    }

    /**
     * Delete one dictionary row whose GUID is still supplied by sibling rows (so the
     * non-unique properties.GUID FK is satisfied). InnoDB's non-unique-FK check would
     * otherwise block the delete; we disable FK checks for just this statement.
     * Caller must ensure the row is orphan-free (an Active sibling keeps the GUID).
     */
    private function deleteDictRowFkSafe(int $dictId): void
    {
        $conn = DB::connection();
        $conn->statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::table(self::DICT)->where('Id', $dictId)->delete();
        } finally {
            $conn->statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
