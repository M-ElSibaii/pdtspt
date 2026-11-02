<?php

namespace App\Http\Controllers;

use App\Services\DraftVersionService;
use App\Support\Lineage;
use Illuminate\Support\Facades\DB;

/**
 * "Create new version" of a published PDT. Nothing goes live from here: the published
 * version is copied into an offline draft ({@see DraftVersionService::startDraft()}) that
 * is edited in the draft editor, reviewed against the live version, and only replaces it
 * when the admin publishes from the review page.
 */
class PdtVersioningController extends Controller
{
    /** Confirm page — or straight to the draft when one is already in progress. */
    public function start(int $pdt, DraftVersionService $drafts)
    {
        $row = DB::table('productdatatemplates')->where('Id', $pdt)->first();
        if (!$row) {
            abort(404);
        }

        $draft = $row->status === 'Preview' ? $row : $drafts->draftFor($row->GUID);
        if ($draft) {
            return redirect()->route('admin.previews.editor', ['pdt' => $draft->Id])
                ->with('success', 'A draft of this template is already in progress — continue editing it here.');
        }

        $current = DB::table('productdatatemplates')->where('GUID', $row->GUID)->orderByRaw(Lineage::PREFER_PUBLISHED)->first();

        return view('admin.new-version-start', [
            'pdt' => $row,
            'current' => $current,
            'counts' => [
                'gops' => DB::table('groupofproperties')->where('pdtId', $current->Id)->count(),
                'properties' => DB::table('properties')->where('pdtID', $current->Id)->count(),
            ],
        ]);
    }

    /** Create the draft (or reuse the existing one) and open the editor. */
    public function create(int $pdt, DraftVersionService $drafts)
    {
        try {
            $r = $drafts->startDraft($pdt);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.previews.editor', ['pdt' => $r['draftId']])
            ->with('success', $r['created']
                ? 'Draft created. The live version is untouched — edit freely, then use “Review & publish”.'
                : 'A draft of this template was already in progress — continue editing it here.');
    }
}
