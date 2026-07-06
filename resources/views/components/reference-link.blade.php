{{--
    Renders a unit / physical quantity / dimension as a link to its dereferenceable
    definition page (ISO 23387 reference layer), but ONLY when the entity actually exists
    in the reference tables — otherwise it falls back to plain text / a placeholder so we
    never emit a link that would 404.

    Usage:
        <x-reference-link type="unit" :value="$property->units" placeholder="Sem unidade" />
        <x-reference-link type="quantitykind" :value="$propdd->physicalQuantity" />
        <x-reference-link type="dimension" :value="$propdd->dimension" />
--}}
@props(['type', 'value' => null, 'placeholder' => null])
@php
    $raw = trim((string) ($value ?? ''));
    $placeholderValues = ['n/a', 'na', 'sem unidade', 'without', 'none', 'nenhuma', '-', '—'];
    $isBlank = $raw === '' || in_array(mb_strtolower($raw), $placeholderValues, true);

    $url = null;
    if (!$isBlank) {
        switch ($type) {
            case 'unit':
                if (\App\Services\UnitsReference::isKnownUnit($raw)) {
                    $url = route('reference.unit', ['code' => $raw]);
                }
                break;
            case 'quantitykind':
                if (\App\Models\PhysicalQuantity::where('name', $raw)->exists()) {
                    $url = route('reference.quantitykind', ['name' => $raw]);
                }
                break;
            case 'dimension':
                if (\App\Models\Dimension::where('canonical', $raw)->exists()) {
                    $url = route('reference.dimension', ['canonical' => $raw]);
                }
                break;
        }
    }

    $display = $isBlank ? ($placeholder ?? $value) : $value;
@endphp
@if($url)
    <a href="{{ $url }}" target="_blank" rel="noopener" title="Ver definição de referência (ISO 23387)">{{ $display }}</a>
@else
    {{ $display }}
@endif
