<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Ordering rules for picking "the" row of a version lineage (rows sharing a GUID).
 *
 * A lineage can hold a Preview draft next to its published versions (a new-version draft
 * keeps the lineage GUID and, until it is published, the version numbers of the version it
 * was copied from). Public pages, exports and the API must never land on that draft, so
 * every "latest of lineage" lookup ranks by status first: Active, then InActive history,
 * then Preview — and only then by version/revision.
 */
class Lineage
{
    /** SQL ORDER BY fragment: published rows before drafts, newest first. */
    public const PREFER_PUBLISHED = "CASE status WHEN 'Active' THEN 0 WHEN 'Preview' THEN 2 ELSE 1 END, versionNumber DESC, revisionNumber DESC";

    /** Rank for PHP-side sorting, matching PREFER_PUBLISHED (lower wins). */
    public static function statusRank($row): int
    {
        $status = is_array($row) ? ($row['status'] ?? null) : ($row->status ?? null);

        return match ($status) {
            'Active'  => 0,
            'Preview' => 2,
            default   => 1,
        };
    }

    /**
     * The row public pages should show for one lineage: the Active head, else the newest
     * InActive version, else (a lineage that was never published) its Preview draft.
     */
    public static function publicHead($rows)
    {
        return collect($rows)->sort(fn($a, $b) => [self::statusRank($a), -(int) $a->versionNumber, -(int) $a->revisionNumber]
            <=> [self::statusRank($b), -(int) $b->versionNumber, -(int) $b->revisionNumber])->first();
    }

    /**
     * The new-version draft of a lineage: a Preview row in a lineage that also has
     * published (non-Preview) rows. A lineage made only of Preview rows is a brand-new
     * PDT, not a version draft, and returns null.
     */
    public static function versionDraft($rows)
    {
        $rows = collect($rows);
        if (!$rows->contains(fn($r) => $r->status !== 'Preview')) {
            return null;
        }

        return $rows->first(fn($r) => $r->status === 'Preview');
    }

    /**
     * 404 for anyone but an admin when $pdtId is a new-version draft. Such a draft is
     * offline until published; only its lineage's published rows are public. (A brand-new
     * template's draft keeps its existing visibility.)
     */
    public static function abortIfHiddenDraft($pdtId): void
    {
        if (!is_numeric($pdtId)) {
            return; // a GUID resolves to the published head, never the draft
        }
        $pdt = DB::table('productdatatemplates')->where('Id', (int) $pdtId)->first(['Id', 'GUID', 'status']);
        if (!$pdt || $pdt->status !== 'Preview' || ((int) (Auth::user()->isAdmin ?? 0)) === 1) {
            return;
        }
        if (DB::table('productdatatemplates')->where('GUID', $pdt->GUID)->where('status', '<>', 'Preview')->exists()) {
            abort(404);
        }
    }

    /** Rows a public version list may show: drafts are hidden once anything is published. */
    public static function publicVersions($rows)
    {
        $rows = collect($rows);
        $published = $rows->reject(fn($r) => $r->status === 'Preview');

        return $published->isNotEmpty() ? $published->values() : $rows->values();
    }
}
