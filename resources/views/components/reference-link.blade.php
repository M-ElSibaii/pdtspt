{{--
    Renders a unit / physical quantity / dimension as a link to its definition page
    (ISO 23387 reference layer), or as plain text when it is not in the reference tables, so
    no link ever 404s.

    Units are matched the way the dictionary typed them ("m2" → m², "N/mm2" → N/mm²) and shown
    with their proper symbol. Pass the property's unit as `unit` for a quantity or dimension:
    they are then taken from the unit, which is the source of truth (the stored free text is
    only the fallback).

    Usage:
        <x-reference-link type="unit" :value="$property->units" placeholder="Sem unidade" />
        <x-reference-link type="quantitykind" :value="$propdd->physicalQuantity" :unit="$propdd->units" />
        <x-reference-link type="dimension" :value="$propdd->dimension" :unit="$propdd->units" />
--}}
@props(['type', 'value' => null, 'placeholder' => null, 'unit' => null])
@php
    $raw = trim((string) ($value ?? ''));
    $placeholderValues = ['n/a', 'na', 'sem unidade', 'without', 'none', 'nenhuma', '-', '—'];
    $isBlank = fn ($v) => $v === '' || in_array(mb_strtolower($v), $placeholderValues, true);

    $url = null;
    $display = $value;
    $title = null;
    $ref = \App\Services\UnitsReference::describe($type === 'unit' ? $raw : $unit);

    switch ($type) {
        case 'unit':
            if ($ref) {
                $url = \App\Services\UriService::link('unit', $ref->code);
                $display = $ref->code;
                $title = $ref->name;
            }
            break;

        case 'quantitykind':
            $pq = $ref && $ref->pq ? (object) ['name' => $ref->pq, 'namePt' => $ref->pqPt]
                : ($isBlank($raw) ? null : \App\Models\PhysicalQuantity::where('name', $raw)->orWhere('namePt', $raw)->first());
            if ($pq) {
                $url = \App\Services\UriService::link('pq', $pq->name);
                $display = Lg::pick($pq->namePt ?: $pq->name, $pq->name);
            }
            break;

        case 'dimension':
            $dim = $ref && $ref->dim ? $ref->dim
                : (!$isBlank($raw) && \App\Models\Dimension::where('canonical', $raw)->exists() ? $raw : null);
            if ($dim) {
                $url = \App\Services\UriService::link('dim', $dim);
                $display = \App\Services\UnitsReference::prettyDimension($dim);
                $title = $dim;
            }
            break;
    }

    if (!$url && $isBlank($raw)) {
        $display = $placeholder !== null ? Lg::t($placeholder) : $value;
    }
@endphp
@if($url)
    <a href="{{ $url }}" class="ref-link" @if($title) title="{{ $title }}" @endif>{{ $display }}</a>
@else
    {{ $display }}
@endif
