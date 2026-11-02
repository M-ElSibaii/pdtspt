<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DraftVersionService;
use App\Services\StatusHealthService;
use App\Support\Lineage;
use Illuminate\Support\Facades\DB;

/**
 * The admin home: templates and drafts in progress, the data-health summary, the tools,
 * and user management — one page to start every admin task from.
 */
class AdminHubController extends Controller
{
    public function index(DraftVersionService $drafts, StatusHealthService $health)
    {
        $lineages = DB::table('productdatatemplates')->get()->groupBy('GUID');

        // One row per template: what the public sees, plus its draft in progress (if any).
        $templates = $lineages->map(function ($rows) {
            $head = Lineage::publicHead($rows);
            $head->draft = Lineage::versionDraft($rows);
            $head->versionCount = Lineage::publicVersions($rows)->count();

            return $head;
        })->sortBy(fn($p) => mb_strtolower($p->pdtNameEn))->values();

        $draftRows = DB::table('productdatatemplates')->where('status', 'Preview')
            ->orderByDesc('updated_at')->orderByDesc('Id')->get();
        foreach ($draftRows as $d) {
            $d->base = $drafts->baseOf($d);
        }

        return view('admin', [
            'templates' => $templates,
            'drafts' => $draftRows,
            'healthCount' => $health->count(),
            'users' => User::paginate(10),
        ]);
    }
}
