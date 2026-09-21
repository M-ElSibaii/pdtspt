<?php

namespace App\Http\Controllers;

use App\Services\CodeCollisionException;
use App\Services\UriService;
use Illuminate\Http\Request;

/**
 * The single entry point for every PDTs.pt identifier.
 *
 *     /uri/{dictVersion}/{entity}/{code}
 *     /uri/{dictVersion}/{entity}/{code}/v{versionNumber}
 *
 * Resolution is delegated to UriService, and rendering is delegated to the same view
 * each entity already had — a classprop URI lands on the existing class-property page,
 * reached by the new identifier rather than the old path.
 *
 * Identifiers are also dereferenceable as data: ask for JSON (Accept header, ?format=json
 * or a .json suffix) and the same URI returns the record plus its identity.
 *
 * Codes are the Portuguese name — this is a Portuguese dictionary, and that is the code
 * carried by exports, DoPCs and the canonical URI. A record's ENGLISH name also resolves,
 * as an alias: it finds the same record and 301-redirects to the canonical Portuguese
 * URI. That is a second way in, never a second identity.
 */
class UriController extends Controller
{
    public function resolve(Request $request, string $dictVersion, string $entity, string $code)
    {
        if (!UriService::isEntity($entity)) {
            abort(404, "Unknown entity segment '{$entity}'.");
        }

        // A ".json" suffix is content negotiation, not part of the code.
        $json = $this->wantsJson($request, $code);
        $code = $this->stripJsonSuffix($code);

        [$code, $version] = UriService::splitVersionSuffix($code);

        // "latest" is accepted on input but never emitted — send the caller to the
        // canonical identifier so what they bookmark is the stable form.
        if ($dictVersion === config('pdts.dictionary_version_alias', 'latest')) {
            return redirect(UriService::path($entity, $code, $version), 302);
        }

        if (UriService::normaliseDictVersion($dictVersion) === null) {
            abort(404, "Unknown dictionary version '{$dictVersion}'. This dictionary is at "
                . UriService::dictionaryVersion() . '.');
        }

        if ($version !== null && !UriService::supportsVersionPin($entity)) {
            abort(404, UriService::label($entity)
                . ' records carry no version number, so they cannot be pinned with /v' . $version . '.');
        }

        try {
            $record = UriService::resolve($entity, $code, $version);

            // Not a canonical (Portuguese) code — try the English name. It is an alias,
            // not a second identity, so a hit is sent on to the one canonical URI.
            if (!$record) {
                $byEnglishName = UriService::resolveAlias($entity, $code, $version);
                if ($byEnglishName) {
                    return $this->redirectToCanonical($request, $entity, $byEnglishName, $version);
                }
            }
        } catch (CodeCollisionException $e) {
            // Two records claim this identifier. Say so; do not pick one.
            return response()->view('errors.uri-collision', [
                'entity'  => $entity,
                'code'    => $code,
                'records' => $e->records,
            ], 409);
        }

        if (!$record) {
            abort(404, UriService::label($entity) . " '{$code}' was not found in dictionary "
                . UriService::dictionaryVersion() . '.');
        }

        // An id-prefixed code (a group, a class property) resolves on its id alone, so an
        // English — or simply outdated — name part still lands on the right record. Send
        // it to the canonical spelling rather than serving the record under two URLs.
        // An enumerated value is not a stored row and has no code of its own to compare.
        if ($entity !== UriService::ENUM_VALUE && UriService::codeFor($entity, $record) !== $code) {
            return $this->redirectToCanonical($request, $entity, $record, $version);
        }

        return $json
            ? $this->asJson($entity, $code, $version, $record)
            : $this->asPage($request, $entity, $code, $version, $record);
    }


    /**
     * Permanent redirect to a record's one canonical URI, preserving the pinned version
     * and any query string (so ?format=json still returns JSON after the hop).
     *
     * 301, not 302: the canonical Portuguese URI is the record's identity and that does
     * not change, so callers and crawlers should remember it.
     */
    private function redirectToCanonical(Request $request, string $entity, $record, ?int $version)
    {
        $target = UriService::buildLink($entity, $record, $version);
        $query = $request->getQueryString();

        return redirect($target . ($query ? '?' . $query : ''), 301);
    }

    // ------------------------------------------------------------------ pages

    private function asPage(Request $request, string $entity, string $code, ?int $version, $record)
    {
        switch ($entity) {
            case UriService::DATA_TEMPLATE:
                return (new ProductdatatemplatesController())->viewPdt($record->Id);

            case UriService::GROUP:
                return (new GroupofpropertiesController())->getGOPDataDictionary($record->Id);

            case UriService::PROPERTY:
                return (new PropertiesdatadictionariesController())->getPropertyDataDictionary($record->Id);

            case UriService::CLASS_PROPERTY:
                return (new PropertiesController())->getClassPropertyView($record->Id);

            case UriService::DOCUMENT:
                return (new ReferencedocumentsController())->getReferenceDocument($record->GUID);

            case UriService::CONSTRUCTION:
                return (new ConstructionObjectController())->view($record->GUID);

            case UriService::UNIT:
                return (new UnitsReferenceController())->unit($request, $record->code);

            case UriService::QUANTITY_KIND:
                return (new UnitsReferenceController())->quantityKind($request, $record->name);

            case UriService::DIMENSION:
                return (new UnitsReferenceController())->dimension($request, $record->canonical);

            case UriService::ENUM_VALUE:
                // An enumerated value has no page of its own: it is one entry in its
                // property's list, so it dereferences to that property, at that row.
                return redirect(
                    UriService::buildLink(UriService::PROPERTY, $record->property) . '#possible-values'
                );
        }

        abort(404);
    }

    // ------------------------------------------------------------------- data

    private function asJson(string $entity, string $code, ?int $version, $record)
    {
        if ($entity === UriService::ENUM_VALUE) {
            return response()->json([
                '@id'      => UriService::uri($entity, $code),
                'entity'   => $entity,
                'code'     => $code,
                'value'    => $record->value,
                'property' => UriService::build(UriService::PROPERTY, $record->property),
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $payload = [
            '@id'    => UriService::uri($entity, $code),
            'entity' => $entity,
            'code'   => $code,
            'label'  => UriService::label($entity),
        ];

        if (isset($record->versionNumber)) {
            $payload['versionNumber']  = (int) $record->versionNumber;
            $payload['revisionNumber'] = (int) ($record->revisionNumber ?? 0);
            $payload['pinnedUri']      = UriService::uri($entity, $code, (int) $record->versionNumber);
        }

        $payload['record'] = (array) $record;

        return response()->json($payload, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function wantsJson(Request $request, string $code): bool
    {
        return $request->query('format') === 'json'
            || str_ends_with($code, '.json')
            || $request->wantsJson();
    }

    private function stripJsonSuffix(string $code): string
    {
        return str_ends_with($code, '.json') ? substr($code, 0, -5) : $code;
    }
}
