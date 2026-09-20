{{--
    The canonical identifier of a record, rendered as a table row.

    Every entity page shows the same thing in the same place: the unversioned URI
    (which is what belongs in exports and declarations) and, for records that carry a
    version number, the pinned form beneath it.

    The displayed text is always the canonical https://pdts.pt/... form; the link
    points at this deployment so it is clickable on localhost too. The identifier does
    not change with the language.

    Usage:
        <x-uri-row entity="dt" :record="$pdt" />
        <x-uri-row entity="classprop" :record="$property" label="URI (class property)" />
--}}
@props(['entity', 'record', 'label' => null])
@php
    $error = null;
    $canonical = $pinned = $href = null;

    try {
        $code = \App\Services\UriService::codeFor($entity, $record);
        $canonical = \App\Services\UriService::uri($entity, $code);
        $href = \App\Services\UriService::link($entity, $code);

        $version = is_array($record) ? ($record['versionNumber'] ?? null) : ($record->versionNumber ?? null);
        if ($version !== null && \App\Services\UriService::supportsVersionPin($entity)) {
            $pinned = \App\Services\UriService::uri($entity, $code, (int) $version);
        }
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
@endphp
<tr>
    <th>{{ $label ?? 'URI' }}</th>
    <td>
        @if ($error)
            <span style="color:#b91c1c;">{{ $error }}</span>
        @else
            <a href="{{ $href }}">{{ $canonical }}</a>
            @if ($pinned)
                <br>
                <small style="color:#6b7280;">
                    {{ Lg::t('Versão') }}: <a href="{{ url(parse_url($pinned, PHP_URL_PATH)) }}">{{ $pinned }}</a>
                </small>
            @endif
        @endif
    </td>
</tr>
