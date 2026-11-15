<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\Support\OriginFixture as O;
use Tests\Support\PdtFixtures as F;

/*
 * Exports and /api responses must not change on this branch, whatever the property origin
 * filter shows on the page. The snapshots in ./snapshots were recorded from audit-admin's
 * code (e3adb7c; this branch then differed from it only in package-lock.json), with
 *
 *     SNAPSHOT_WRITE=1 php artisan test --filter=ExportSnapshotTest
 *
 * Every response is compared byte for byte. If one differs, the export changed: that is a
 * stop, not a snapshot to re-record.
 */

function snapshotResponses($test): array
{
    $out = [];
    foreach ([O::MASTER, O::PARENT, O::CHILD] as $id) {
        $out["api-{$id}.json"] = $test->get("/api/{$id}")->assertOk()->getContent();
        $out["api-{$id}-iso.json"] = $test->get("/api/{$id}/json")->assertOk()->getContent();
        $out["api-{$id}-iso.xml"] = $test->get("/api/{$id}/xml")->assertOk()->getContent();
        $out["download-{$id}.json"] = $test->post("/pdt-export/json/{$id}")->assertOk()->streamedContentOrContent();
        $out["download-{$id}.xml"] = $test->post("/pdt-export/xml/{$id}")->assertOk()->streamedContentOrContent();

        // The CSV/XLS download is built in the browser from the page's hidden flat table.
        foreach (['pt', 'en'] as $lang) {
            $html = $test->get("/pdtsdownload/{$id}?lang={$lang}")->assertOk()->getContent();
            expect(preg_match('~<table hidden id="tblpdtsh">.*?</table>~s', $html, $m))->toBe(1);
            $out["xlsx-table-{$id}-{$lang}.html"] = $m[0];
        }
    }
    foreach (['productDataTemplates', 'dataDictionary', 'groupsOfProperties', 'referenceDocuments', 'constructionObjects'] as $c) {
        $out["api-{$c}.json"] = $test->get("/api/{$c}")->assertOk()->getContent();
    }
    $out['api-dataDictionary-9201.json'] = $test->get('/api/dataDictionary/9201')->assertOk()->getContent();
    $out['api-groupsOfProperties-9103.json'] = $test->get('/api/groupsOfProperties/9103')->assertOk()->getContent();
    $out['api-referenceDocuments-rd.json'] = $test->get('/api/referenceDocuments/' . O::RD_GUID)->assertOk()->getContent();
    $out['bsdd-domain.json'] = $test->actingAs(F::admin())->post('/exportdomainbsdd-psets')->assertOk()->streamedContent();

    return $out;
}

beforeEach(function () {
    Http::preventStrayRequests();
    // The same host locally and on CI, so links in a response do not depend on .env.
    config(['app.url' => 'http://localhost']);
    URL::forceRootUrl('http://localhost');
    O::build();
});

\Illuminate\Testing\TestResponse::macro('streamedContentOrContent', function () {
    return $this->baseResponse instanceof \Symfony\Component\HttpFoundation\StreamedResponse
        ? $this->streamedContent() : $this->getContent();
});

test('exports and /api responses are byte-for-byte the same as on audit-admin', function () {
    $dir = __DIR__ . '/snapshots';
    $responses = snapshotResponses($this);

    if (getenv('SNAPSHOT_WRITE') === '1') {
        is_dir($dir) || mkdir($dir, 0777, true);
        foreach ($responses as $name => $body) {
            file_put_contents("{$dir}/{$name}", $body);
        }
        $this->markTestSkipped('Snapshots written to ' . $dir);
    }

    expect(array_map('basename', glob("{$dir}/*")))->toEqualCanonicalizing(array_keys($responses));
    foreach ($responses as $name => $body) {
        expect($body)->toBe(file_get_contents("{$dir}/{$name}"), "{$name} differs from audit-admin");
    }
});
