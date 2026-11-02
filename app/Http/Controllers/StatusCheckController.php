<?php

namespace App\Http\Controllers;

use App\Services\StatusHealthService;
use Illuminate\Http\Request;

/**
 * Admin status health check: review what the status rules would change and apply the
 * selected fixes (see {@see StatusHealthService}).
 */
class StatusCheckController extends Controller
{
    public function index(StatusHealthService $health)
    {
        return view('admin.status-check', ['report' => $health->report(), 'tables' => StatusHealthService::TABLES]);
    }

    public function apply(Request $request, StatusHealthService $health)
    {
        $keys = (array) $request->input('keys', []);
        if (!$keys) {
            return redirect()->route('admin.statusCheck')->with('error', 'Nothing selected — tick the fixes to apply.');
        }

        try {
            $r = $health->apply($keys);
        } catch (\Throwable $e) {
            return redirect()->route('admin.statusCheck')->with('error', 'Nothing was changed: ' . $e->getMessage());
        }

        $msg = "Applied {$r['applied']} status fix(es).";
        if ($r['skipped']) {
            $msg .= " {$r['skipped']} were no longer needed (already fixed since the page loaded).";
        }
        if ($r['backup']) {
            $msg .= " Backup: storage/app/{$r['backup']}.";
        }

        return redirect()->route('admin.statusCheck')->with('success', $msg);
    }
}
