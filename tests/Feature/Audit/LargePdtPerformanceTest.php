<?php

use Illuminate\Support\Facades\DB;
use Tests\Support\PdtFixtures as F;

/*
 * Measures (does not gate) one large template on the public and export pages: wall time
 * and number of SQL queries. The figures are printed for the audit report; the test only
 * asserts that every page answers.
 */

test('a 300-property template: time and queries per page', function () {
    $tpl = F::pdt(array_fill(0, 12, 25), ['pdtNameEn' => 'Large template', 'pdtNamePt' => 'Modelo grande']);
    $id = $tpl['pdt']->Id;
    $admin = F::admin();

    $pages = [
        'GET /api/{id}' => fn() => $this->getJson("/api/{$id}"),
        'GET /api/{id}/json' => fn() => $this->getJson("/api/{$id}/json"),
        'GET /api/{id}/xml' => fn() => $this->get("/api/{$id}/xml"),
        'GET /pdtsdownload/{id} (PDT page)' => fn() => $this->get(route('pdtsdownload', ['pdtID' => $id])),
        'POST /pdt-export/json/{id}' => fn() => $this->post(route('pdt.export.json', ['pdtId' => $id])),
        'GET /uri/0.1/dt/ModeloGrande' => fn() => $this->get('/uri/0.1/dt/ModeloGrande'),
        'POST /exportdomainbsdd-psets (admin)' => fn() => $this->actingAs($admin)->post('/exportdomainbsdd-psets'),
    ];

    $lines = [];
    foreach ($pages as $label => $call) {
        $call(); // warm-up (autoload, caches)
        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });
        $t = microtime(true);
        $res = $call();
        $ms = (microtime(true) - $t) * 1000;
        if ($res->baseResponse instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            $res->streamedContent();
        }
        DB::getEventDispatcher()->forget(\Illuminate\Database\Events\QueryExecuted::class);
        expect($res->baseResponse->getStatusCode())->toBe(200, $label);
        $lines[] = sprintf('%-40s %7.0f ms %6d queries', $label, $ms, $queries);
    }

    fwrite(STDERR, "\n[perf] 1 template, 12 groups, 300 properties\n[perf] " . implode("\n[perf] ", $lines) . "\n");
});
